<?php

namespace Tests\Feature\Contracts;

use App\Features\Contracts\Filament\Pages\Contract;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSeeText('Vertragstext')
            ->assertSeeText('1. Vertragsparteien')
            ->assertSeeText('2. Mietobjekt (§1)')
            ->assertSeeText('3. Mietkosten (§2–§5)')
            ->assertSeeText('4. Vertragslaufzeit und Unterschrift')
            ->assertSeeText('{{text:vermieter_name}}')
            ->assertSeeText('{{amount:gesamtmiete}}')
            ->assertSeeText('{{date:vertragsdatum}}')
            ->assertSee('run-rich-editor-commands', false)
            ->assertSee('insertContent', false)
            ->assertSee('fi-fo-rich-editor', false);
    }
}
