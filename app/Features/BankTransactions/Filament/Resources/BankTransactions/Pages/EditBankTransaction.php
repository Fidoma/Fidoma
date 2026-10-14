<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditBankTransaction extends EditRecord
{
    protected static string $resource = BankTransactionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
