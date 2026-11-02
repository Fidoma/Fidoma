<?php

namespace App\Features\Webmail\Filament\Pages;

use App\Features\ArtificialIntelligence\Actions\SimulateAiTemplateExecution;
use App\Features\ArtificialIntelligence\Enums\AiTemplateAction;
use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Queries\AvailableAiTemplates;
use App\Features\Clients\Filament\Actions\CreateClientModalAction;
use App\Features\Clients\Models\Client;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Filament\Actions\CreateReservationModalAction;
use App\Features\Reservations\Models\Room;
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
        SimulateAiTemplateExecution $simulateExecution,
    ): void {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $simulation = $simulateExecution->execute($user, $templateId, $messageContext);
        $draft = match ($simulation['action']) {
            AiTemplateAction::CreateClient => $simulation['data'],
            AiTemplateAction::CreateReservation => $this->reservationDraft($simulation['data']),
        };

        $this->mountAction($simulation['action']->modalAction(), [
            'draft' => $draft,
            'template' => $simulation['template_title'],
        ]);
    }

    public function createClientFromAiAction(): Action
    {
        return CreateClientModalAction::make('createClientFromAi', rejectDuplicateEmail: true)
            ->label(__('artificial-intelligence::messages.actions.create_client'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalHeading(__('artificial-intelligence::messages.simulation.create_client_heading'))
            ->modalDescription(__('artificial-intelligence::messages.simulation.badge'))
            ->modalSubmitActionLabel(__('artificial-intelligence::messages.actions.create_client'))
            ->fillForm(fn (array $arguments): array => $arguments['draft'] ?? [])
            ->successNotificationTitle(__('artificial-intelligence::messages.simulation.client_created'));
    }

    public function createReservationFromAiAction(): Action
    {
        return CreateReservationModalAction::make('createReservationFromAi', redirectAfterCreate: false)
            ->label(__('artificial-intelligence::messages.actions.create_reservation'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->modalHeading(__('artificial-intelligence::messages.simulation.create_reservation_heading'))
            ->modalDescription(__('artificial-intelligence::messages.simulation.badge'))
            ->modalSubmitActionLabel(__('artificial-intelligence::messages.actions.create_reservation'));
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function reservationDraft(array $data): array
    {
        $client = Client::query()
            ->when(
                filled($data['client_email'] ?? null),
                fn ($query) => $query->where('client_email', $data['client_email']),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->first();

        return [
            'client_id' => $client?->getKey(),
            'product_id' => Product::query()->where('product_name', $data['product_name'] ?? '')->value('product_id'),
            'room_id' => Room::query()->where('room_number', $data['room_number'] ?? '')->value('room_id'),
            'arrival_date' => $data['arrival_date'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
        ];
    }
}
