<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals;

use App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages\CreatePaymentTerminal;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages\EditPaymentTerminal;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages\ListPaymentTerminals;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\Schemas\PaymentTerminalForm;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\Tables\PaymentTerminalsTable;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PaymentTerminalResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = PaymentTerminal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 6;

    protected static function navigationModule(): string
    {
        return 'terminals';
    }

    public static function getNavigationLabel(): string
    {
        return __('terminals::messages.management.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('terminals::messages.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('terminals::messages.management.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('terminals::messages.management.feature');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentTerminalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentTerminalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTerminals::route('/'),
            'create' => CreatePaymentTerminal::route('/create'),
            'edit' => EditPaymentTerminal::route('/{record}/edit'),
        ];
    }
}
