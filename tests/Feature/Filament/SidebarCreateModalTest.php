<?php

namespace Tests\Feature\Filament;

use App\Features\Products\Models\Product;
use App\Features\Reservations\Services\LastSelectedReservationProduct;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
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

    public function test_reservation_modal_prefills_the_last_selected_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $taxRate = TaxRate::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();
        $product = Product::query()->create([
            'product_sku' => 'ROOM-NIGHT',
            'product_name' => 'Übernachtung mit Frühstück',
            'product_description' => 'Zimmer und Frühstück',
            'product_price' => 129,
            'product_tax_included' => true,
            'tax_rate_id' => $taxRate->getKey(),
            'unit_id' => $unit->getKey(),
        ]);

        $component = Livewire::test(SidebarCreateModal::class)
            ->dispatch('open-sidebar-create-modal', action: 'createReservation')
            ->assertActionMounted('createReservation');

        $component
            ->set('mountedActions.0.data.product_id', $product->getKey())
            ->call('unmountAction')
            ->dispatch('open-sidebar-create-modal', action: 'createReservation')
            ->assertActionDataSet(['product_id' => $product->getKey()]);

        $this->assertSame($product->getKey(), app(LastSelectedReservationProduct::class)->currentId());
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
        ];
    }
}
