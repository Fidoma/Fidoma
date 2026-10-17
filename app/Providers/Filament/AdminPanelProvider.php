<?php

namespace App\Providers\Filament;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use App\Features\Users\Filament\Resources\Users\UserResource;
use App\Features\Webmail\Filament\Pages\Webmail;
use App\Filament\Pages\InvoiceLayout;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->breadcrumbs(false)
            ->globalSearch(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->userMenuItems([
                [
                    'logout' => fn (Action $action): Action => $action->sort(0),
                ],
                [
                    Action::make('pdfLayouts')
                        ->label(InvoiceLayout::getNavigationLabel())
                        ->icon(InvoiceLayout::getNavigationIcon())
                        ->url(fn (): string => InvoiceLayout::getUrl())
                        ->visible(fn (): bool => InvoiceLayout::canAccess())
                        ->sort(0),
                    Action::make('emailTemplates')
                        ->label(EmailTemplateResource::getNavigationLabel())
                        ->icon(EmailTemplateResource::getNavigationIcon())
                        ->url(fn (): string => EmailTemplateResource::getUrl())
                        ->visible(fn (): bool => EmailTemplateResource::canAccess())
                        ->sort(1),
                    Action::make('invoiceGroups')
                        ->label(InvoiceGroupResource::getNavigationLabel())
                        ->icon(InvoiceGroupResource::getNavigationIcon())
                        ->url(fn (): string => InvoiceGroupResource::getUrl())
                        ->visible(fn (): bool => InvoiceGroupResource::canAccess())
                        ->sort(2),
                    Action::make('taxRates')
                        ->label(TaxRateResource::getNavigationLabel())
                        ->icon(TaxRateResource::getNavigationIcon())
                        ->url(fn (): string => TaxRateResource::getUrl())
                        ->visible(fn (): bool => TaxRateResource::canAccess())
                        ->sort(3),
                    Action::make('users')
                        ->label(UserResource::getNavigationLabel())
                        ->icon(UserResource::getNavigationIcon())
                        ->url(fn (): string => UserResource::getUrl())
                        ->visible(fn (): bool => UserResource::canAccess())
                        ->sort(4),
                ],
            ])
            ->discoverResources(in: app_path('Features'), for: 'App\Features')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Webmail::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
