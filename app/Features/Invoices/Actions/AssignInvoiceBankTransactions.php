<?php

namespace App\Features\Invoices\Actions;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssignInvoiceBankTransactions
{
    public function __construct(
        private readonly RecalculateInvoice $recalculate,
        private readonly InvoiceDefaults $defaults,
    ) {}

    /**
     * @param  array<int, string>  $expectedFingerprints
     * @return Collection<int, BankTransactionMapping>
     */
    public function handleMany(int $invoiceId, array $expectedFingerprints): Collection
    {
        if ($expectedFingerprints === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Bitte wählen Sie mindestens eine Banktransaktion aus.',
            ]);
        }

        $transactionIds = array_map('intval', array_keys($expectedFingerprints));
        $transactions = BankTransaction::query()->whereKey($transactionIds)->get()->keyBy->getKey();

        foreach ($transactionIds as $transactionId) {
            $transaction = $transactions->get($transactionId);

            if (! $transaction) {
                throw ValidationException::withMessages([
                    'transaction' => 'Mindestens eine Banktransaktion ist nicht mehr vorhanden.',
                ]);
            }

            Gate::authorize('update', $transaction);
        }

        return DB::transaction(function () use ($invoiceId, $expectedFingerprints, $transactionIds): Collection {
            $invoice = Invoice::query()->with('amount')->lockForUpdate()->findOrFail($invoiceId);

            if ($invoice->invoice_type !== InvoiceType::Invoice || $invoice->is_archived) {
                throw ValidationException::withMessages([
                    'invoice' => 'Diese Rechnung kann nicht zugeordnet werden.',
                ]);
            }

            if ($invoice->invoice_status_id === InvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => 'Entwürfe können noch keinen Banktransaktionen zugeordnet werden.',
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
                        'transaction' => 'Rechnungen können nur positiven Banktransaktionen zugeordnet werden.',
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

            $balance = round((float) ($invoice->amount?->invoice_balance ?? 0), 2);

            if ($balance <= 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Die Rechnung hat keinen offenen Betrag.',
                ]);
            }

            $paymentMethodId = $invoice->payment_method ?: $this->defaults->paymentMethodId();

            if (! $paymentMethodId) {
                throw ValidationException::withMessages([
                    'invoice' => 'Es ist keine Zahlungsart vorhanden.',
                ]);
            }

            try {
                $mappings = collect($transactionIds)->map(function (int $transactionId) use ($invoice, $paymentMethodId, $transactions): BankTransactionMapping {
                    /** @var BankTransaction $transaction */
                    $transaction = $transactions->get($transactionId);
                    $payment = $invoice->payments()->create([
                        'payment_external_id' => 'bank-transaction:'.$transaction->getKey(),
                        'payment_method_id' => $paymentMethodId,
                        'payment_date' => $transaction->booking_date,
                        'payment_amount' => round((float) $transaction->amount, 2),
                        'payment_note' => 'Banktransaktion '.$transaction->getKey().': '.trim((string) $transaction->purpose),
                    ]);
                    $mapping = $transaction->mappings()->create([
                        'payment_id' => $payment->getKey(),
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

            $this->recalculate->handle($invoice);

            return $mappings;
        }, attempts: 3);
    }
}
