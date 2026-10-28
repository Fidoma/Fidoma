<?php

namespace App\Features\Webmail\Filament\Pages;

use App\Features\ArtificialIntelligence\Actions\PrepareAiTemplateExecution;
use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Queries\AvailableAiTemplates;
use App\Features\Webmail\Services\RoundcubeInstaller;
use App\Filament\Concerns\HasConfigurableNavigation;
use App\Models\User;
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

    /** @return array<int, array{id: int, title: string}> */
    public function emailMessageAiTemplates(AvailableAiTemplates $templates): array
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $templates->summariesForUserAndArea($user, AiTemplateArea::EmailMessage);
    }

    /** @param array<string, mixed> $messageContext */
    public function prepareAiTemplate(
        int $templateId,
        array $messageContext,
        PrepareAiTemplateExecution $prepareExecution,
    ): void {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $selection = $prepareExecution->execute($user, $templateId, $messageContext);

        Notification::make()
            ->title(__('artificial-intelligence::messages.roundcube.selection_prepared'))
            ->body(__('artificial-intelligence::messages.roundcube.selection_prepared_body', [
                'template' => $selection['template']->title,
            ]))
            ->success()
            ->send();
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
