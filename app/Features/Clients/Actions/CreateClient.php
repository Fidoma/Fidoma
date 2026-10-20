<?php

namespace App\Features\Clients\Actions;

use App\Features\Clients\Models\Client;
use App\Features\Clients\Services\ClientDefaults;
use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CreateClient
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
        private readonly ClientDefaults $defaults,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Client
    {
        return DB::transaction(function () use ($attributes): Client {
            $invoiceGroupId = (int) $this->defaults->invoiceGroupId();

            if ($invoiceGroupId === 0) {
                throw new LogicException('No number group is configured for clients.');
            }

            return Client::query()->create([
                ...$attributes,
                'invoice_group_id' => $invoiceGroupId,
                'client_number' => $this->generateNumber->handle($invoiceGroupId),
            ]);
        });
    }
}
