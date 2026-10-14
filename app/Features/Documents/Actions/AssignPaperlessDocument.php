<?php

namespace App\Features\Documents\Actions;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Services\PaperlessClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssignPaperlessDocument
{
    public function __construct(private readonly PaperlessClient $paperless) {}

    public function handle(int $documentId, int $bankTransactionId, string $expectedFingerprint): BankTransactionMapping
    {
        /** @var BankTransactionMapping $mapping */
        $mapping = $this->handleMany($documentId, [
            $bankTransactionId => $expectedFingerprint,
        ])->firstOrFail();

        return $mapping;
    }

    /**
     * @param  array<int, string>  $expectedFingerprints
     * @return Collection<int, BankTransactionMapping>
     */
    public function handleMany(int $documentId, array $expectedFingerprints): Collection
    {
        if ($expectedFingerprints === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Bitte wählen Sie mindestens eine Banktransaktion aus.',
            ]);
        }

        $transactionIds = array_keys($expectedFingerprints);
        $transactions = BankTransaction::query()->whereKey($transactionIds)->get()->keyBy->getKey();

        foreach ($transactionIds as $transactionId) {
            $transaction = $transactions->get($transactionId) ?? BankTransaction::query()->findOrFail($transactionId);
            Gate::authorize('update', $transaction);
        }

        $this->paperless->document($documentId, fresh: true);

        return DB::transaction(function () use ($documentId, $expectedFingerprints, $transactionIds): Collection {
            $transactions = BankTransaction::query()
                ->whereKey($transactionIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy->getKey();

            foreach ($transactionIds as $transactionId) {
                $transaction = $transactions->get($transactionId) ?? BankTransaction::query()->findOrFail($transactionId);
                Gate::authorize('update', $transaction);

                if ((float) $transaction->amount >= 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Paperless-Dokumente können nur negativen Banktransaktionen zugeordnet werden.',
                    ]);
                }

                if (! hash_equals($expectedFingerprints[$transactionId], $transaction->assignmentFingerprint())) {
                    throw ValidationException::withMessages([
                        'transaction' => count($transactionIds) === 1
                            ? 'Die Banktransaktion wurde zwischenzeitlich geändert. Bitte wählen Sie sie erneut aus.'
                            : 'Mindestens eine Banktransaktion wurde zwischenzeitlich geändert. Bitte wählen Sie die Transaktionen erneut aus.',
                    ]);
                }

                if ($transaction->mappings()->where('document_id', $documentId)->exists()) {
                    throw ValidationException::withMessages([
                        'transaction' => count($transactionIds) === 1
                            ? 'Diese Zuordnung besteht bereits.'
                            : 'Mindestens eine ausgewählte Zuordnung besteht bereits.',
                    ]);
                }
            }

            try {
                $mappings = collect($transactionIds)->map(function (int $transactionId) use ($documentId, $transactions): BankTransactionMapping {
                    /** @var BankTransaction $transaction */
                    $transaction = $transactions->get($transactionId);
                    $mapping = $transaction->mappings()->create([
                        'payment_id' => null,
                        'document_id' => $documentId,
                    ]);
                    $transaction->update(['validated' => true]);

                    return $mapping;
                });
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'transaction' => count($transactionIds) === 1
                        ? 'Diese Zuordnung wurde bereits parallel gespeichert.'
                        : 'Mindestens eine Zuordnung wurde bereits parallel gespeichert.',
                ]);
            }

            return $mappings;
        }, attempts: 3);
    }
}
