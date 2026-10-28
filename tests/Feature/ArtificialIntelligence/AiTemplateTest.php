<?php

namespace Tests\Feature\ArtificialIntelligence;

use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\CreateAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\EditAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\ListAiTemplates;
use App\Features\ArtificialIntelligence\Models\AiTemplate;
use App\Features\ArtificialIntelligence\Queries\AvailableAiTemplates;
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

    public function test_template_resource_is_available_in_the_ai_navigation_group(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertSame('KI', AiTemplateResource::getNavigationGroup());

        $this->actingAs($user)
            ->get(AiTemplateResource::getUrl())
            ->assertOk()
            ->assertSeeText('Vorlagen');

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
                'prompt' => 'Lies die Reservierungsdaten aus der Nachricht.',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $template = AiTemplate::query()->where('title', 'Reservierung erstellen')->firstOrFail();

        $this->assertSame(AiTemplateArea::EmailMessage, $template->area);

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
            'prompt' => 'Geheimer Prompt 2',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $first = AiTemplate::query()->create([
            'title' => 'Erste Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'prompt' => 'Geheimer Prompt 1',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        AiTemplate::query()->create([
            'title' => 'Inaktive Aktion',
            'area' => AiTemplateArea::EmailMessage,
            'prompt' => 'Darf nicht erscheinen',
            'is_active' => false,
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

    public function test_selecting_a_template_only_prepares_the_context_without_calling_ai(): void
    {
        $user = User::factory()->create();
        $template = AiTemplate::query()->create([
            'title' => 'Reservierung erstellen',
            'area' => AiTemplateArea::EmailMessage,
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
            ->assertHasNoErrors();

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_templates', 1);
    }
}
