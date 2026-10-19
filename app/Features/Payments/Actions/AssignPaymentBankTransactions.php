<?php

namespace App\Features\Payments\Actions;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Payments\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssignPaymentBankTransactions
{
    /**
     * @param  array<int, string>  $expectedFingerprints
     * @return Collection<int, BankTransactionMapping>
     */
    public function handleMany(int $paymentId, array $expectedFingerprints): Collection
    {
        if ($expectedFingerprints === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Bitte wählen Sie mindestens eine Banktransaktion aus.',
            ]);
        }

        $transactionIds = array_map('intval', array_keys($expectedFingerprints));

        return DB::transaction(function () use ($paymentId, $expectedFingerprints, $transactionIds): Collection {
            $payment = Payment::query()->with('invoice')->lockForUpdate()->findOrFail($paymentId);

            if ($payment->invoice?->invoice_type !== InvoiceType::Invoice || $payment->invoice->is_archived) {
                throw ValidationException::withMessages([
                    'payment' => 'Diese Zahlung kann nicht zugeordnet werden.',
                ]);
            }

            $transactions = BankTransaction::query()
                ->whereKey($transactionIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy->getKey();

            foreach ($transactionIds as $transactionId) {
                /** @var BankTransaction|null $transaction */
                $transaction = $transactions->get($transactionId);

                if (! $transaction || (float) $transaction->amount <= 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Zahlungen können nur positiven Banktransaktionen zugeordnet werden.',
                    ]);
                }

                Gate::authorize('update', $transaction);

                if (! hash_equals($expectedFingerprints[$transactionId], $transaction->assignmentFingerprint())) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Mindestens eine Banktransaktion wurde zwischenzeitlich geändert. Bitte wählen Sie die Transaktionen erneut aus.',
                    ]);
                }

                if ($transaction->mappings()->exists()) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Mindestens eine ausgewählte Banktransaktion ist bereits zugeordnet.',
                    ]);
                }
            }

            try {
                return collect($transactionIds)->map(function (int $transactionId) use ($payment, $transactions): BankTransactionMapping {
                    /** @var BankTransaction $transaction */
                    $transaction = $transactions->get($transactionId);
                    $mapping = $transaction->mappings()->create([
                        'payment_id' => $payment->getKey(),
                        'payment_created_by_mapping' => false,
                        'document_id' => null,
                    ]);
                    $transaction->update(['validated' => true]);

                    return $mapping;
                });
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'transaction' => 'Mindestens eine Zuordnung wurde bereits parallel gespeichert.',
                ]);
            }
        }, attempts: 3);
    }
}
