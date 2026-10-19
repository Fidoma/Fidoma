<?php

namespace App\Features\Invoices\Actions;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UnassignInvoiceBankTransactions
{
    public function __construct(private readonly RecalculateInvoice $recalculate) {}

    /**
     * @param  array<int, string>  $expectedFingerprints
     * @return Collection<int, BankTransactionMapping>
     */
    public function handleMany(int $invoiceId, array $expectedFingerprints): Collection
    {
        if ($expectedFingerprints === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Bitte wählen Sie mindestens eine verknüpfte Banktransaktion aus.',
            ]);
        }

        $transactionIds = array_map('intval', array_keys($expectedFingerprints));

        return DB::transaction(function () use ($invoiceId, $expectedFingerprints, $transactionIds): Collection {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
            $transactions = BankTransaction::query()
                ->whereKey($transactionIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy->getKey();

            foreach ($transactionIds as $transactionId) {
                $transaction = $transactions->get($transactionId);

                if (! $transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Mindestens eine Banktransaktion ist nicht mehr vorhanden.',
                    ]);
                }

                Gate::authorize('update', $transaction);

                if (! hash_equals($expectedFingerprints[$transactionId], $transaction->assignmentFingerprint())) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Mindestens eine Banktransaktion wurde zwischenzeitlich geändert.',
                    ]);
                }
            }

            $mappings = BankTransactionMapping::query()
                ->whereIn('bank_transaction_id', $transactionIds)
                ->whereHas('payment', fn ($query) => $query->where('invoice_id', $invoiceId))
                ->lockForUpdate()
                ->get();

            if ($mappings->count() !== count($transactionIds)) {
                throw ValidationException::withMessages([
                    'transaction' => 'Mindestens eine Verknüpfung wurde bereits entfernt.',
                ]);
            }

            $paymentIds = $mappings
                ->where('payment_created_by_mapping', true)
                ->pluck('payment_id')
                ->filter()
                ->all();
            BankTransactionMapping::query()->whereKey($mappings->modelKeys())->delete();
            Payment::query()
                ->whereKey($paymentIds)
                ->delete();

            foreach ($transactions as $transaction) {
                if (! $transaction->mappings()->exists()) {
                    $transaction->update(['validated' => false]);
                }
            }

            $this->recalculate->handle($invoice);

            return $mappings;
        }, attempts: 3);
    }
}
