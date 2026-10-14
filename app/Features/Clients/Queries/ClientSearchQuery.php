<?php

namespace App\Features\Clients\Queries;

use App\Features\Clients\Models\Client;
use Illuminate\Database\Eloquent\Builder;

final class ClientSearchQuery
{
    /**
     * @return Builder<Client>
     */
    public function query(?string $search = null): Builder
    {
        return Client::query()
            ->when($search, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
                    ->orWhere('client_mobile', 'like', "%{$search}%");
            }));
    }
}
