<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Pages;

use App\Features\Quotes\Actions\RecalculateQuote;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->disabled(fn (): bool => $this->record->is_archived);
    }

    protected function beforeSave(): void
    {
        abort_if($this->record->is_archived, 403, __('quotes::messages.archive.edit_forbidden'));
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        app(RecalculateQuote::class)->handle($this->record);
    }
}
