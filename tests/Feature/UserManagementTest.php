<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_user_management_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertOk();
        $this->actingAs($user)->get('/admin/users/create')->assertOk();
        $this->actingAs($user)->get("/admin/users/{$user->getKey()}/edit")->assertOk();
    }

    public function test_company_and_billing_details_can_be_stored_on_a_user(): void
    {
        $user = User::create([
            'name' => 'Max Mustermann',
            'company_name' => 'Fidoma GmbH',
            'address_line_1' => 'Musterstraße 1',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'vat_id' => 'DE123456789',
            'bank_name' => 'Musterbank',
            'iban' => 'DE02120300000000202051',
            'bic' => 'BYLADEM1001',
            'email' => 'max@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertSame('Fidoma GmbH', $user->company_name);
        $this->assertSame('DE123456789', $user->vat_id);
        $this->assertSame('DE02120300000000202051', $user->iban);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }
}
