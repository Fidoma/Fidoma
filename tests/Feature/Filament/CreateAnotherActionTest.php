<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Filament\Resources\Clients\Pages\CreateClient;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class CreateAnotherActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pages_use_a_compact_create_another_action(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(CreateClient::class)->instance();
        $method = new ReflectionMethod($component, 'getCreateAnotherFormAction');
        $action = $method->invoke($component);

        $this->assertSame('Erstellen', $action->getLabel());
        $this->assertSame(Heroicon::ArrowPath, $action->getIcon());
    }

    public function test_create_modals_use_a_compact_create_another_action(): void
    {
        $action = CreateAction::make()->getCreateAnotherAction();

        $this->assertSame('Erstellen', $action->getLabel());
        $this->assertSame(Heroicon::ArrowPath, $action->getIcon());
    }
}
