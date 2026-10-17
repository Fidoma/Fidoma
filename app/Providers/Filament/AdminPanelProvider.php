<?php

namespace App\Providers\Filament;

use App\Features\Authentication\Filament\Pages\Login;
use App\Features\Contracts\Filament\Pages\Contract;
use App\Features\Dashboard\Filament\Pages\Dashboard;
use App\Features\Documents\Filament\Pages\Documents;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\Invoices\Filament\Pages\InvoiceLayout;
use App\Features\Reports\Filament\Pages\Reports;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
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
                fn () => view('features.authentication.login-story'),
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
                NavigationItem::make('Dokumente')
                    ->group('Externe Dienste')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->sort(3)
                    ->url(fn (): string => rtrim((string) config('services.paperless.url'), '/'))
                    ->visible(fn (): bool => filter_var(config('services.paperless.url'), FILTER_VALIDATE_URL) !== false)
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
                    'logout' => fn (Action $action): Action => $action->sort(0),
                ],
                [
                    Action::make('settings')
                        ->label('Settings')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->url(fn (): string => SettingsCluster::getUrl())
                        ->visible(fn (): bool => SettingsCluster::canAccessClusteredComponents())
                        ->sort(0),
                ],
            ])
            ->discoverResources(in: app_path('Features'), for: 'App\Features')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Features/Products/Filament/Clusters'), for: 'App\\Features\\Products\\Filament\\Clusters')
            ->discoverClusters(in: app_path('Features/Settings/Filament/Clusters'), for: 'App\\Features\\Settings\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
                Contract::class,
                Documents::class,
                InvoiceLayout::class,
                Reports::class,
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
