<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentItemActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_position_menus_offer_duplication(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/admin/invoices/create', '/admin/quotes/create', '/admin/reservations/create'] as $url) {
            $expectedActions = ['Position duplizieren'];

            $expectedActions[] = 'Zeile hinzufügen';

            $expectedActions[] = 'Preis inklusive Steuer';
            $expectedActions[] = 'Position löschen';

            $response = $this->get($url)->assertOk()->assertSeeTextInOrder($expectedActions);

            $response
                ->assertSee('fi-fo-table-repeater', escape: false)
                ->assertSee('fi-fixed-positioning-context', escape: false)
                ->assertSee('x-float.placement.bottom-end.flip.teleport.offset=', escape: false)
                ->assertDontSee('fi-fo-summary-actions-in-table', escape: false);
        }
    }
}
