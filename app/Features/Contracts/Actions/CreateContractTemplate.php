<?php

namespace App\Features\Contracts\Actions;

use App\Features\Contracts\Models\ContractTemplate;

final class CreateContractTemplate
{
    /** @param array{name: string, content: string} $data */
    public function handle(array $data): ContractTemplate
    {
        return ContractTemplate::query()->create([
            'name' => $data['name'],
            'content' => $data['content'],
            'status' => 'draft',
            'version' => 1,
        ]);
    }
}
