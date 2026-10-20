<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages;

use App\Features\BankTransactions\Actions\CreateBankTransaction as CreateBankTransactionAction;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBankTransaction extends CreateRecord
{
    protected static string $resource = BankTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBankTransactionAction::class)->handle($data);
    }
}
