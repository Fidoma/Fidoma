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
            $this->get($url)
                ->assertOk()
                ->assertSeeTextInOrder([
                    'Position duplizieren',
                    'Preis inklusive Steuer',
                    'Position löschen',
                ]);
        }
    }
}
