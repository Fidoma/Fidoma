<?php

namespace Tests\Feature\ArtificialIntelligence;

use App\Features\ArtificialIntelligence\Enums\AiTemplateAction;
use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\CreateAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\EditAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\ListAiTemplates;
use App\Features\ArtificialIntelligence\Models\AiTemplate;
use App\Features\ArtificialIntelligence\Queries\AvailableAiTemplates;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\Webmail\Filament\Pages\Webmail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AiTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_resource_is_available_in_settings(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertSame(SettingsCluster::class, AiTemplateResource::getCluster());
        $this->assertSame('KI-Vorlagen', AiTemplateResource::getNavigationLabel());
        $this->assertFalse(AiTemplateResource::shouldRegisterNavigation());
        $this->assertStringEndsWith('/admin/settings/ai-templates', AiTemplateResource::getUrl());

        $this->actingAs($user)
            ->get(AiTemplateResource::getUrl())
            ->assertOk()
            ->assertSeeTextInOrder(['E-Mail-Vorlagen', 'Vertragsvorlagen', 'KI-Vorlagen']);

        Livewire::actingAs($user)
            ->test(ListAiTemplates::class)
            ->assertTableActionVisible('create');
    }

    public function test_template_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateAiTemplate::class)
            ->fillForm([
                'title' => 'Reservierung erstellen',
                'area' => AiTemplateArea::EmailMessage->value,
                'action' => AiTemplateAction::CreateReservation->value,
                'prompt' => 'Lies die Reservierungsdaten aus der Nachricht.',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $template = AiTemplate::query()->where('title', 'Reservierung erstellen')->firstOrFail();

        $this->assertSame(AiTemplateArea::EmailMessage, $template->area);
        $this->assertSame(AiTemplateAction::CreateReservation, $template->action);

        Livewire::actingAs($user)
            ->test(EditAiTemplate::class, ['record' => $template->getRouteKey()])
            ->fillForm([
                'title' => 'Kundendaten erkennen',
                'prompt' => 'Lies die Kundendaten aus der Nachricht.',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ai_templates', [
            'id' => $template->getKey(),
            'title' => 'Kundendaten erkennen',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(EditAiTemplate::class, ['record' => $template->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('ai_templates', ['id' => $template->getKey()]);
    }

    public function test_only_active_email_templates_are_exposed_without_prompts(): void
    {
        $user = User::factory()->create();
        $second = AiTemplate::query()->create([
            'title' => 'Zweite Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'action' => AiTemplateAction::CreateReservation,
            'prompt' => 'Geheimer Prompt 2',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $first = AiTemplate::query()->create([
            'title' => 'Erste Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'action' => AiTemplateAction::CreateClient,
            'prompt' => 'Geheimer Prompt 1',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        AiTemplate::query()->create([
            'title' => 'Inaktive Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'action' => AiTemplateAction::CreateClient,
            'prompt' => 'Darf nicht erscheinen',
            'is_active' => false,
        ]);
        AiTemplate::query()->create([
            'title' => 'Vorlage ohne Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'prompt' => 'Noch nicht vollständig konfiguriert',
            'is_active' => true,
        ]);

        $available = app(AvailableAiTemplates::class)->forUserAndArea($user, AiTemplateArea::EmailMessage);

        $this->assertSame([$first->getKey(), $second->getKey()], $available->modelKeys());

        Livewire::actingAs($user)
            ->test(Webmail::class)
            ->call('emailMessageAiTemplates')
            ->assertReturned([
                ['id' => $first->getKey(), 'title' => 'Erste Aktion'],
                ['id' => $second->getKey(), 'title' => 'Zweite Aktion'],
            ]);
    }

    public function test_selecting_a_client_template_opens_a_prefilled_simulation_modal_without_external_ai(): void
    {
        $user = User::factory()->create();
        $template = AiTemplate::query()->create([
            'title' => 'Reservierung erstellen',
            'area' => AiTemplateArea::EmailMessage,
            'action' => AiTemplateAction::CreateClient,
            'prompt' => 'Extrahiere Reservierungsdaten.',
            'is_active' => true,
        ]);
        Http::fake();

        Livewire::actingAs($user)
            ->test(Webmail::class)
            ->call('prepareAiTemplate', $template->getKey(), [
                'uid' => '42',
                'mailbox' => 'INBOX',
            ])
            ->assertHasNoErrors()
            ->assertActionMounted('createClientFromAi')
            ->assertActionDataSet([
                'client_name' => 'Max',
                'client_surname' => 'Mustermann',
                'client_email' => 'max.mustermann@example.test',
            ]);

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_templates', 1);
    }

    public function test_selecting_a_reservation_template_opens_the_reservation_simulation_modal(): void
    {
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->create();
        $template = AiTemplate::query()->create([
            'title' => 'Reservierung erstellen',
            'area' => AiTemplateArea::EmailMessage,
            'action' => AiTemplateAction::CreateReservation,
            'prompt' => 'Extrahiere Reservierungsdaten.',
            'is_active' => true,
        ]);
        Http::fake();

        Livewire::actingAs($user)
            ->test(Webmail::class)
            ->call('prepareAiTemplate', $template->getKey(), [
                'uid' => '43',
                'mailbox' => 'INBOX',
            ])
            ->assertHasNoErrors()
            ->assertActionMounted('createReservationFromAi')
            ->assertActionDataSet([
                'arrival_date' => now()->addWeek()->toDateString(),
                'departure_date' => now()->addWeek()->addDays(3)->toDateString(),
            ]);

        Http::assertNothingSent();
        $this->assertDatabaseCount('ip_reservations', 0);
    }
}
