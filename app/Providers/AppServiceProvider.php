<?php

namespace App\Providers;

use App\Features\Documents\Livewire\AssignBankTransaction;
use Filament\Forms\Components\Select;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Select::configureUsing(function (Select $select): void {
            $select
                ->selectablePlaceholder(fn (Select $component): bool => self::isLinkedSelect($component))
                ->default(fn (Select $component): mixed => self::isLinkedSelect($component)
                    ? null
                    : self::firstOptionValue($component->getOptions()));
        });

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_AFTER,
            function () {
                $livewire = Livewire::current();

                return $livewire instanceof AssignBankTransaction
                    ? $livewire->assignmentStatusToggle->toHtml()
                    : '';
            },
            scopes: AssignBankTransaction::class,
        );
    }

    private static function isLinkedSelect(Select $select): bool
    {
        return $select->hasRelationship()
            || $select->isMultiple()
            || $select->getName() === 'value'
            || str_ends_with($select->getName(), '_id')
            || str_ends_with($select->getName(), '_ids');
    }

    /** @param array<string|int, mixed> $options */
    private static function firstOptionValue(array $options): string|int|null
    {
        $firstKey = array_key_first($options);

        if ($firstKey === null) {
            return null;
        }

        $firstOption = $options[$firstKey];

        return is_array($firstOption)
            ? self::firstOptionValue($firstOption)
            : $firstKey;
    }
}
