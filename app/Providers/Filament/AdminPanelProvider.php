<?php

namespace App\Providers\Filament;

use App\Features\Authentication\Filament\Pages\Login;
use App\Features\Authentication\Http\Middleware\SetLocale;
use App\Features\Dashboard\Filament\Pages\Dashboard;
use App\Features\Documents\Filament\Pages\Documents;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\Imports\Filament\Pages\DataImport;
use App\Features\Invoices\Filament\Pages\InvoiceLayout;
use App\Features\Reports\Filament\Pages\Reports;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\Settings\Filament\Pages\Modules;
use App\Features\Settings\Services\ModuleSettings;
use App\Features\TradeFairs\Filament\Pages\TradeFairs;
use App\Features\Webmail\Filament\Pages\Webmail;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('Fidoma')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<span class="fidoma-brand">'
                .'<img src="'.e(asset('images/brand/fidoma-mark.png')).'" alt="" class="fidoma-brand-mark">'
                .'<span class="fidoma-brand-name">Fidoma</span>'
                .'</span>',
            ))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/brand/fidoma-mark.png'))
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn () => view('authentication::login-story'),
                scopes: Login::class,
            )
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->breadcrumbs(false)
            ->globalSearch(false)
            ->colors([
                'primary' => Color::hex('#FF0092'),
            ])
            ->navigationItems([
                NavigationItem::make(fn (): string => __('documents::messages.feature'))
                    ->group(fn (): string => __('webmail::messages.navigation_group'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->sort(3)
                    ->url(fn (): string => rtrim((string) config('services.paperless.url'), '/'))
                    ->visible(fn (): bool => app(ModuleSettings::class)->isEnabled('paperless')
                        && filter_var(config('services.paperless.url'), FILTER_VALIDATE_URL) !== false)
                    ->extraAttributes(function (): array {
                        $url = Js::from(rtrim((string) config('services.paperless.url'), '/'));

                        return [
                            'x-on:click.prevent' => "window.paperlessDocumentPreview && !window.paperlessDocumentPreview.closed
                                ? window.paperlessDocumentPreview.focus()
                                : (window.paperlessDocumentPreview = window.open({$url}, '".PaperlessClient::DOCUMENT_PREVIEW_WINDOW."'))?.focus()",
                        ];
                    }),
            ])
            ->userMenuItems([
                [
                    Action::make('language')
                        ->view('authentication::user-menu-language-switcher')
                        ->sort(1),
                ],
                [
                    Action::make('settings')
                        ->label(fn (): string => __('authentication::messages.user_menu.settings'))
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->url(fn (): string => SettingsCluster::getUrl())
                        ->visible(fn (): bool => SettingsCluster::canAccessClusteredComponents())
                        ->sort(0),
                    'logout' => fn (Action $action): Action => $action->sort(PHP_INT_MAX),
                ],
            ])
            ->discoverResources(in: app_path('Features'), for: 'App\Features')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Features/Products/Filament/Clusters'), for: 'App\\Features\\Products\\Filament\\Clusters')
            ->discoverClusters(in: app_path('Features/Settings/Filament/Clusters'), for: 'App\\Features\\Settings\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
                Documents::class,
                DataImport::class,
                Modules::class,
                InvoiceLayout::class,
                Reports::class,
                TradeFairs::class,
                Webmail::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
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
