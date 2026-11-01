<?php

namespace App\Filament\Livewire;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Contracts\Actions\CreateContract;
use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use App\Features\Contracts\Models\Contract;
use App\Features\Payments\Filament\Resources\Payments\PaymentResource;
use App\Features\Payments\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Features\Projects\Actions\CreateProject;
use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use App\Features\Projects\Models\Project;
use App\Features\Reservations\Actions\CreateReservation;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Filament\Resources\Rooms\RoomResource;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\Room;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\TourismReportResource;
use App\Features\TourismStatistics\Models\TourismReport;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SidebarCreateModal extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, class-string> */
    private const ACTION_RESOURCES = [
        'createClient' => ClientResource::class,
        'createPayment' => PaymentResource::class,
        'createReservation' => ReservationResource::class,
        'createRoom' => RoomResource::class,
        'createProject' => ProjectResource::class,
        'createContract' => ContractResource::class,
        'createTourismReport' => TourismReportResource::class,
    ];

    #[On('open-sidebar-create-modal')]
    public function open(string $action): void
    {
        $resource = self::ACTION_RESOURCES[$action] ?? null;

        abort_unless($resource !== null && $resource::canCreate(), 403);

        $this->mountAction($action);
    }

    public function createClientAction(): CreateAction
    {
        return CreateAction::make('createClient')
            ->model(Client::class)
            ->label(__('clients::messages.actions.create_client'))
            ->modalHeading(__('clients::messages.actions.create_client'))
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(fn (Schema $schema): Schema => ClientResource::form($schema))
            ->using(fn (array $data): Client => app(CreateClient::class)->handle($data))
            ->createAnother(false)
            ->successNotificationTitle(__('clients::messages.actions.create_client'));
    }

    public function createPaymentAction(): Action
    {
        return PaymentsTable::createPaymentAction();
    }

    public function createReservationAction(): CreateAction
    {
        return CreateAction::make('createReservation')
            ->model(Reservation::class)
            ->label(__('reservations::messages.singular'))
            ->modalHeading(__('reservations::messages.singular'))
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(fn (Schema $schema): Schema => ReservationResource::form($schema))
            ->using(fn (array $data): Reservation => app(CreateReservation::class)->handle($data))
            ->after(fn (Reservation $record) => app(RecalculateReservation::class)->handle($record))
            ->createAnother(false);
    }

    public function createRoomAction(): CreateAction
    {
        return CreateAction::make('createRoom')
            ->model(Room::class)
            ->label(__('reservations::messages.room_singular'))
            ->modalHeading('Zimmer hinzufügen')
            ->modalSubmitActionLabel('Zimmer erstellen')
            ->schema(fn (Schema $schema): Schema => RoomResource::form($schema))
            ->createAnother(false);
    }

    public function createProjectAction(): CreateAction
    {
        return CreateAction::make('createProject')
            ->model(Project::class)
            ->label(__('projects::messages.actions.create'))
            ->modalHeading(__('projects::messages.actions.create'))
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(fn (Schema $schema): Schema => ProjectResource::form($schema))
            ->using(fn (array $data): Project => app(CreateProject::class)->handle($data))
            ->createAnother(false);
    }

    public function createContractAction(): CreateAction
    {
        return CreateAction::make('createContract')
            ->model(Contract::class)
            ->label(__('contracts::messages.singular'))
            ->modalHeading(__('contracts::messages.singular'))
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(fn (Schema $schema): Schema => ContractResource::form($schema))
            ->using(fn (array $data): Contract => app(CreateContract::class)->handle($data, (int) auth()->id()))
            ->createAnother(false);
    }

    public function createTourismReportAction(): CreateAction
    {
        return CreateAction::make('createTourismReport')
            ->model(TourismReport::class)
            ->label(__('tourism-statistics::messages.actions.create'))
            ->modalHeading(__('tourism-statistics::messages.actions.create'))
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(fn (Schema $schema): Schema => TourismReportResource::form($schema))
            ->createAnother(false);
    }

    public function render(): View
    {
        return view('filament.sidebar-create-modal');
    }
}
