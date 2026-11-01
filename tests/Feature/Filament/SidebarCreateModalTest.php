<?php

namespace Tests\Feature\Filament;

use App\Filament\Livewire\SidebarCreateModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SidebarCreateModalTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('modalActions')]
    public function test_sidebar_event_opens_create_modal_without_navigation(string $action): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(SidebarCreateModal::class)
            ->dispatch('open-sidebar-create-modal', action: $action)
            ->assertActionMounted($action);
    }

    /** @return array<string, array{string}> */
    public static function modalActions(): array
    {
        return [
            'client' => ['createClient'],
            'payment' => ['createPayment'],
            'reservation' => ['createReservation'],
            'room' => ['createRoom'],
            'project' => ['createProject'],
            'contract' => ['createContract'],
            'tourism report' => ['createTourismReport'],
        ];
    }
}
