<?php

namespace App\Features\Documents\Filament\Tables\Columns;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Services\PaperlessClient;
use Filament\Tables\Columns\TextColumn;

final class PaperlessDocumentIdsColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('paperless_document_ids')
            ->label('Paperless-ID')
            ->state(function (BankTransaction $record): array {
                $documentIds = $record->mappings
                    ->pluck('document_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return $documentIds === [] ? [0] : $documentIds;
            })
            ->formatStateUsing(fn (int|string $state): string => (int) $state > 0
                ? '#'.(int) $state
                : 'Nicht zugeordnet')
            ->badge()
            ->color(fn (int|string $state): string => (int) $state > 0 ? 'success' : 'gray')
            ->url(fn (int|string $state): ?string => (int) $state > 0
                ? app(PaperlessClient::class)->documentDetailsUrl((int) $state)
                : null)
            ->extraAttributes([
                'x-on:click.capture' => "const link = \$event.target.closest('a'); if (link) { \$event.preventDefault(); \$event.stopPropagation(); window.paperlessDocumentPreview = window.open(link.href, '".PaperlessClient::DOCUMENT_PREVIEW_WINDOW."'); }",
            ])
            ->grow(false);
    }
}
