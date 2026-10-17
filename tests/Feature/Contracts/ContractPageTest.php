<?php

namespace Tests\Feature\Contracts;

use App\Features\Contracts\Filament\Pages\Contract;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_page_contains_the_tiptap_rich_editor(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($user)
            ->get(Contract::getUrl())
            ->assertOk()
            ->assertSeeText('Contract')
            ->assertDontSeeText('Vertragstext')
            ->assertSeeText('Vertrag erstellen')
            ->assertSee('placeholder="Vorlage"', false)
            ->assertSeeText('Platzhalter')
            ->assertSeeText('{{text:client_name}}')
            ->assertSeeText('{{text:issuer_name}}')
            ->assertSeeText('{{date:contract_create_date}}')
            ->assertSeeText('{{date:contract_start_date}}')
            ->assertSeeText('{{date:contract_end_date}}')
            ->assertSeeText('{{text:contract_location}}')
            ->assertDontSeeText('{{text:vermieter_name}}')
            ->assertDontSeeText('{{amount:gesamtmiete}}')
            ->assertDontSee('run-rich-editor-commands', false)
            ->assertDontSee('insertContent', false)
            ->assertSee('navigator.clipboard.writeText', false)
            ->assertSee('Platzhalter kopieren')
            ->assertSee('fi-fo-rich-editor', false);
    }

    public function test_template_and_contract_content_are_required(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(Contract::class)
            ->assertFormFieldExists('template', fn ($field): bool => $field->isRequired())
            ->assertFormFieldExists('content', fn ($field): bool => $field->isRequired())
            ->call('createContract')
            ->assertHasFormErrors([
                'template' => 'required',
                'content',
            ]);
    }
}
