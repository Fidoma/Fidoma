<?php

namespace App\Providers;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Services\AiClientManager;
use App\Features\Documents\Livewire\AssignBankTransaction;
use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Services\InternalApiTerminalGateway;
use App\Features\TradeFairs\Livewire\UpcomingTradeFairsTable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
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
        $this->app->singleton(AiClientManager::class);
        $this->app->bind(AiClient::class, fn (): AiClient => $this->app->make(AiClientManager::class)->client());
        $this->app->bind(PaymentTerminalInterface::class, InternalApiTerminalGateway::class);
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
                    : self::firstOptionValue($component->getOptions()))
                ->createOptionAction(fn (Action $action): Action => $action
                    ->extraModalFooterActions(fn (Action $action, Select $component): array => $component->isMultiple() ? [
                        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                            ->label(__('filament-forms::components.select.actions.create_option.modal.actions.create.label'))
                            ->icon(Heroicon::ArrowPath),
                    ] : []));
        });

        CreateAction::configureUsing(fn (CreateAction $action): CreateAction => $action
            ->createAnotherAction(fn (Action $action): Action => $action
                ->label(__('filament-actions::create.single.modal.actions.create.label'))
                ->icon(Heroicon::ArrowPath)));

        Column::configureUsing(fn (Column $column): Column => $column->toggleable());

        Table::configureUsing(fn (Table $table): Table => $table
            ->persistInSession()
            ->columnManager());

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

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            function () {
                $livewire = Livewire::current();

                return $livewire instanceof UpcomingTradeFairsTable
                    ? $livewire->importTradeFairsAction->toHtml()
                    : '';
            },
            scopes: UpcomingTradeFairsTable::class,
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
