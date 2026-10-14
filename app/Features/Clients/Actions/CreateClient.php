<?php

namespace App\Features\Clients\Actions;

use App\Features\Clients\Models\Client;

final class CreateClient
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Client
    {
        return Client::query()->create($attributes);
    }
}
