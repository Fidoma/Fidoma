<?php

namespace App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts;

use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\Pages\ListTerminalPaymentAttempts;
use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\Tables\TerminalPaymentAttemptsTable;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TerminalPaymentAttemptResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = TerminalPaymentAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 6;

    protected static function navigationModule(): string
    {
        return 'terminals';
    }

    public static function getNavigationLabel(): string
    {
        return __('terminals::messages.history.heading');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('terminals::messages.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('terminals::messages.history.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('terminals::messages.history.heading');
    }

    public static function table(Table $table): Table
    {
        return TerminalPaymentAttemptsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTerminalPaymentAttempts::route('/'),
        ];
    }
}
