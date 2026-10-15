<?php

namespace App\Features\Invoices\Queries;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

final class InvoiceBankTransactionCandidateQuery
{
    /** @return Builder<BankTransaction> */
    public function query(Invoice $invoice): Builder
    {
        $invoice->loadMissing(['client', 'amount']);

        $query = BankTransaction::query()
            ->where('amount', '>', 0)
            ->select('ip_bank_transactions.*')
            ->withCount('mappings')
            ->withExists([
                'mappings as mapped_to_current_invoice' => fn (Builder $query): Builder => $query
                    ->whereHas('payment', fn (Builder $query): Builder => $query->where('invoice_id', $invoice->getKey())),
            ]);

        $scoreSql = '0';
        $bindings = [];
        $balance = (float) ($invoice->amount?->invoice_balance ?? 0);

        if ($balance > 0) {
            $scoreSql .= ' + CASE WHEN ABS(amount - ?) < 0.005 THEN 50 ELSE 0 END';
            $bindings[] = $balance;
        }

        if (filled($invoice->client?->client_name)) {
            $scoreSql .= ' + CASE WHEN LOWER(COALESCE(counterparty_name, \'\')) LIKE ? THEN 30 ELSE 0 END';
            $bindings[] = '%'.mb_strtolower($invoice->client->client_name).'%';
        }

        if (filled($invoice->invoice_number)) {
            $scoreSql .= ' + CASE WHEN LOWER(COALESCE(purpose, \'\')) LIKE ? OR LOWER(COALESCE(transaction_reference, \'\')) LIKE ? OR LOWER(COALESCE(bank_reference, \'\')) LIKE ? OR LOWER(COALESCE(end_to_end_id, \'\')) LIKE ? THEN 20 ELSE 0 END';
            $pattern = '%'.mb_strtolower($invoice->invoice_number).'%';
            array_push($bindings, $pattern, $pattern, $pattern, $pattern);
        }

        return $query
            ->selectRaw("{$scoreSql} AS match_score", $bindings)
            ->orderByDesc('match_score')
            ->orderByDesc('booking_date')
            ->orderByDesc('id');
    }
}
