<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_demo_user_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $demoUser = User::query()
            ->where('email', 'demo@fidoma.de')
            ->sole();

        $this->assertSame('Demo User', $demoUser->name);
        $this->assertTrue(Hash::check('demo', $demoUser->password));
        $this->assertSame(1, User::query()->where('email', 'demo@fidoma.de')->count());
    }
}
