<?php

namespace App\Features\Documents\Actions;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UnassignPaperlessDocument
{
    /**
     * @param  array<int, string>  $expectedFingerprints
     * @return Collection<int, BankTransactionMapping>
     */
    public function handleMany(int $documentId, array $expectedFingerprints): Collection
    {
        if ($documentId < 1 || $expectedFingerprints === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Bitte wählen Sie mindestens eine verknüpfte Banktransaktion aus.',
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

        return DB::transaction(function () use ($documentId, $expectedFingerprints, $transactionIds): Collection {
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
                        'transaction' => count($transactionIds) === 1
                            ? 'Die Banktransaktion wurde zwischenzeitlich geändert. Bitte wählen Sie sie erneut aus.'
                            : 'Mindestens eine Banktransaktion wurde zwischenzeitlich geändert. Bitte wählen Sie die Transaktionen erneut aus.',
                    ]);
                }
            }

            $mappings = BankTransactionMapping::query()
                ->where('document_id', $documentId)
                ->whereIn('bank_transaction_id', $transactionIds)
                ->orderBy('bank_transaction_mapping_id')
                ->lockForUpdate()
                ->get();

            if ($mappings->count() !== count($transactionIds)) {
                throw ValidationException::withMessages([
                    'transaction' => count($transactionIds) === 1
                        ? 'Diese Verknüpfung wurde bereits entfernt.'
                        : 'Mindestens eine Verknüpfung wurde bereits entfernt.',
                ]);
            }

            BankTransactionMapping::query()
                ->whereKey($mappings->modelKeys())
                ->delete();

            return $mappings;
        }, attempts: 3);
    }
}
