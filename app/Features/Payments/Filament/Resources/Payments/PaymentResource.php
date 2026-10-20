<?php

namespace App\Features\Payments\Filament\Resources\Payments;

use App\Features\Payments\Filament\Resources\Payments\Pages\ListPayments;
use App\Features\Payments\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Features\Payments\Models\Payment;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = Payment::class;

    protected static function navigationModule(): string
    {
        return 'payments';
    }

    protected static ?string $modelLabel = 'Zahlung';

    protected static ?string $pluralModelLabel = 'Zahlungen';

    protected static ?string $navigationLabel = 'Zahlungen';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getNavigationLabel(): string
    {
        return __('payments::messages.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('payments::messages.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('payments::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payments::messages.feature');
    }

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
