<?php

namespace App\Features\Payments\Filament\Resources\Payments;

use App\Features\Payments\Filament\Resources\Payments\Pages\ListPayments;
use App\Features\Payments\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Features\Payments\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $modelLabel = 'Zahlung';

    protected static ?string $pluralModelLabel = 'Zahlungen';

    protected static ?string $navigationLabel = 'Zahlungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
