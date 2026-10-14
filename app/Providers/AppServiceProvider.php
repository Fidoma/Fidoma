<?php

namespace App\Providers;

use App\Features\Documents\Livewire\AssignBankTransaction;
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
}
