<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Pages;

use App\Features\Quotes\Actions\CreateQuote as CreateQuoteAction;
use App\Features\Quotes\Actions\RecalculateQuote;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateQuoteAction::class)->handle($data);
    }

    protected function afterCreate(): void
    {
        app(RecalculateQuote::class)->handle($this->record);
    }
}
