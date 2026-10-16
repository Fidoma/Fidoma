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

            if ($url === '/admin/invoices/create') {
                $expectedActions[] = 'Zeile hinzufügen';
            }

            $expectedActions[] = 'Preis inklusive Steuer';
            $expectedActions[] = 'Position löschen';

            $response = $this->get($url)->assertOk()->assertSeeTextInOrder($expectedActions);

            if ($url === '/admin/invoices/create') {
                $response
                    ->assertSee('fi-fo-summary-actions-in-table', escape: false)
                    ->assertSee('fi-fo-summary-table-actions', escape: false)
                    ->assertSee('fi-fo-summary-leading-component', escape: false)
                    ->assertSeeText('Zahlungsbedingungen');
            }
        }
    }
}
