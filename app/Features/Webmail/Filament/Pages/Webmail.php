<?php

namespace App\Features\Webmail\Filament\Pages;

use App\Features\ArtificialIntelligence\Actions\SimulateAiTemplateExecution;
use App\Features\ArtificialIntelligence\Enums\AiTemplateAction;
use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Queries\AvailableAiTemplates;
use App\Features\Clients\Actions\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\Schemas\ClientForm;
use App\Features\Clients\Models\Client;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Actions\CreateReservationWithItem;
use App\Features\Reservations\Models\Room;
use App\Features\Webmail\Services\RoundcubeInstaller;
use App\Filament\Concerns\HasConfigurableNavigation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
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
        return Action::make('createClientFromAi')
            ->label(__('artificial-intelligence::messages.actions.create_client'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalHeading(__('artificial-intelligence::messages.simulation.create_client_heading'))
            ->modalDescription(__('artificial-intelligence::messages.simulation.badge'))
            ->modalSubmitActionLabel(__('artificial-intelligence::messages.actions.create_client'))
            ->modalWidth(Width::TwoExtraLarge)
            ->fillForm(fn (array $arguments): array => $arguments['draft'] ?? [])
            ->schema(fn (Schema $schema): Schema => ClientForm::configure($schema))
            ->action(function (array $data): void {
                $email = trim((string) ($data['client_email'] ?? ''));

                if ($email !== '' && Client::query()->where('client_email', $email)->exists()) {
                    throw ValidationException::withMessages([
                        'client_email' => __('artificial-intelligence::messages.simulation.duplicate_client'),
                    ]);
                }

                app(CreateClient::class)->handle($data);

                Notification::make()
                    ->title(__('artificial-intelligence::messages.simulation.client_created'))
                    ->success()
                    ->send();
            });
    }

    public function createReservationFromAiAction(): Action
    {
        return Action::make('createReservationFromAi')
            ->label(__('artificial-intelligence::messages.actions.create_reservation'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->modalHeading(__('artificial-intelligence::messages.simulation.create_reservation_heading'))
            ->modalDescription(__('artificial-intelligence::messages.simulation.badge'))
            ->modalSubmitActionLabel(__('artificial-intelligence::messages.actions.create_reservation'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => $arguments['draft'] ?? [])
            ->schema([
                Select::make('client_id')
                    ->label(__('clients::messages.singular'))
                    ->options(fn (): array => Client::query()
                        ->where('client_active', true)
                        ->orderBy('client_name')
                        ->get()
                        ->mapWithKeys(fn (Client $client): array => [
                            $client->getKey() => trim($client->client_name.' '.$client->client_surname),
                        ])->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->label(__('artificial-intelligence::messages.fields.product'))
                    ->options(fn (): array => Product::query()->orderBy('product_name')->pluck('product_name', 'product_id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('room_id')
                    ->label(__('reservations::messages.room_singular'))
                    ->options(fn (): array => Room::query()
                        ->where('room_active', true)
                        ->orderBy('room_number')
                        ->get()
                        ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('arrival_date')
                    ->label(__('artificial-intelligence::messages.fields.arrival_date'))
                    ->required(),
                DatePicker::make('departure_date')
                    ->label(__('artificial-intelligence::messages.fields.departure_date'))
                    ->after('arrival_date')
                    ->required(),
            ])
            ->action(function (array $data): void {
                app(CreateReservationWithItem::class)->handle($data);

                Notification::make()
                    ->title(__('artificial-intelligence::messages.simulation.reservation_created'))
                    ->success()
                    ->send();
            });
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
