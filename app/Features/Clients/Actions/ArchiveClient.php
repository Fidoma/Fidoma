<?php

namespace App\Features\Clients\Actions;

use App\Features\Clients\Models\Client;

final class ArchiveClient
{
    public function handle(Client $client): Client
    {
        $client->update(['client_active' => false]);

        return $client;
    }
}
