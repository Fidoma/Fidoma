<?php

namespace App\Features\Contracts\Actions;

use App\Features\Contracts\Models\Contract;
use App\Features\Contracts\Models\ContractTemplate;
use App\Features\Contracts\Services\ContractDefaults;
use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CreateContract
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
        private readonly ContractDefaults $defaults,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data, int $issuerId): Contract
    {
        return DB::transaction(function () use ($data, $issuerId): Contract {
            $template = ContractTemplate::query()
                ->with('fields')
                ->findOrFail($data['template_id']);
            $invoiceGroupId = $this->defaults->invoiceGroupId();

            if ($invoiceGroupId === null) {
                throw new LogicException('No number group is configured for contracts.');
            }

            $number = trim((string) ($data['number'] ?? ''));

            $contract = Contract::query()->create([
                'template_id' => $template->getKey(),
                'client_id' => $data['client_id'],
                'issuer_id' => $issuerId,
                'invoice_group_id' => $invoiceGroupId,
                'number' => $number !== '' ? $number : $this->generateNumber->handle($invoiceGroupId),
                'contract_create_date' => $data['contract_create_date'],
                'contract_start_date' => $data['contract_start_date'],
                'contract_end_date' => $data['contract_end_date'],
                'contract_location' => $data['contract_location'],
                'status' => 'draft',
            ]);

            foreach ($template->fields as $field) {
                $value = data_get($data, "custom_fields.{$field->field_key}");

                $contract->fieldValues()->create([
                    'template_field_id' => $field->getKey(),
                    'value' => is_bool($value) ? (string) (int) $value : $value,
                ]);
            }

            return $contract->refresh();
        });
    }
}
