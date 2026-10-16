<?php

namespace App\Features\Documents\Queries;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Data\PaperlessDocument;
use Illuminate\Database\Eloquent\Builder;

final class BankTransactionCandidateQuery
{
    /**
     * @param  array{search?: string, amount?: string, booking_date?: string, account_iban?: string, status?: string}  $filters
     * @return Builder<BankTransaction>
     */
    public function query(PaperlessDocument $document, array $filters = []): Builder
    {
        $query = BankTransaction::query()
            ->where('amount', '<', 0)
            ->select('ip_bank_transactions.*')
            ->with('mappings')
            ->withCount('mappings')
            ->withExists([
                'mappings as mapped_to_current_document' => fn (Builder $query): Builder => $query->where('document_id', $document->id),
            ]);

        $scoreSql = '0';
        $bindings = [];

        if ($document->amount !== null) {
            $scoreSql .= ' + CASE WHEN ABS(ABS(amount) - ?) < 0.005 THEN 50 ELSE 0 END';
            $bindings[] = $document->amount;
        }

        if (filled($document->supplier)) {
            $scoreSql .= ' + CASE WHEN LOWER(COALESCE(counterparty_name, \'\')) LIKE ? THEN 30 ELSE 0 END';
            $bindings[] = '%'.mb_strtolower($document->supplier).'%';
        }

        if (filled($document->invoiceNumber)) {
            $scoreSql .= ' + CASE WHEN LOWER(COALESCE(purpose, \'\')) LIKE ? OR LOWER(COALESCE(transaction_reference, \'\')) LIKE ? OR LOWER(COALESCE(bank_reference, \'\')) LIKE ? OR LOWER(COALESCE(end_to_end_id, \'\')) LIKE ? THEN 20 ELSE 0 END';
            $invoicePattern = '%'.mb_strtolower($document->invoiceNumber).'%';
            array_push($bindings, $invoicePattern, $invoicePattern, $invoicePattern, $invoicePattern);
        }

        $query->selectRaw("{$scoreSql} AS match_score", $bindings)
            ->orderByDesc('match_score')
            ->orderByDesc('booking_date')
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $pattern = "%{$search}%";
                $query->where('counterparty_name', 'like', $pattern)
                    ->orWhere('counterparty_iban', 'like', $pattern)
                    ->orWhere('purpose', 'like', $pattern)
                    ->orWhere('transaction_reference', 'like', $pattern)
                    ->orWhere('bank_reference', 'like', $pattern)
                    ->orWhere('end_to_end_id', 'like', $pattern);

                if (is_numeric(str_replace(',', '.', $search))) {
                    $query->orWhereRaw('ABS(amount) = ?', [(float) str_replace(',', '.', $search)]);
                }
            });
        });

        $amount = str_replace(',', '.', trim((string) ($filters['amount'] ?? '')));
        $query->when($amount !== '' && is_numeric($amount), fn (Builder $query): Builder => $query->whereRaw('ABS(amount) = ?', [(float) $amount]));
        $query->when(filled($filters['booking_date'] ?? null), fn (Builder $query): Builder => $query->whereDate('booking_date', $filters['booking_date']));
        $query->when(filled($filters['account_iban'] ?? null), fn (Builder $query): Builder => $query->where('account_iban', $filters['account_iban']));

        return match ($filters['status'] ?? 'all') {
            'unassigned' => $query->doesntHave('mappings'),
            'assigned' => $query->has('mappings'),
            default => $query,
        };
    }
}
