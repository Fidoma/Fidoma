<?php

namespace App\Features\Webmail\Filament\Pages;

use App\Features\Webmail\Services\RoundcubeInstaller;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use UnitEnum;

class Webmail extends Page
{
    use HasConfigurableNavigation;

    protected static function navigationModule(): string
    {
        return 'webmail';
    }

    protected static ?string $title = 'Webmail';

    protected static ?string $navigationLabel = 'Webmail';

    protected static string|UnitEnum|null $navigationGroup = 'Externe Dienste';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected string $view = 'webmail::pages.webmail';

    protected Width|string|null $maxContentWidth = Width::Full;

    public bool $installed = false;

    public static function getNavigationLabel(): string
    {
        return __('webmail::messages.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('webmail::messages.navigation_group');
    }

    public function mount(RoundcubeInstaller $installer): void
    {
        $this->installed = $installer->installed();
    }

    /** @return array<string> */
    public function getPageClasses(): array
    {
        return $this->installed
            ? ['webmail-page', 'webmail-page-installed']
            : ['webmail-page'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('install')
                ->label(__('webmail::messages.install'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->requiresConfirmation()
                ->modalHeading(__('webmail::messages.install'))
                ->modalDescription(__('webmail::messages.install_description'))
                ->visible(fn (): bool => ! $this->installed)
                ->action(function (RoundcubeInstaller $installer): void {
                    try {
                        $installer->install();
                        $this->installed = true;
                        Notification::make()->title(__('webmail::messages.installed'))->success()->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->title(__('webmail::messages.install_failed'))
                            ->body(__('webmail::messages.'.$exception->getMessage()))
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
