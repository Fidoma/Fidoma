<?php

namespace App\Features\Projects\Actions;

use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use App\Features\Projects\Models\Project;
use App\Features\Projects\Services\ProjectDefaults;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CreateProject
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
        private readonly ProjectDefaults $defaults,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): Project
    {
        return DB::transaction(function () use ($data): Project {
            $invoiceGroupId = (int) ($data['invoice_group_id'] ?? $this->defaults->invoiceGroupId());

            if ($invoiceGroupId === 0) {
                throw new LogicException('No number group is configured for projects.');
            }

            $projectNumber = trim((string) ($data['project_number'] ?? ''));

            return Project::query()->create([
                ...$data,
                'invoice_group_id' => $invoiceGroupId,
                'project_number' => $projectNumber !== ''
                    ? $projectNumber
                    : $this->generateNumber->handle($invoiceGroupId),
            ]);
        });
    }
}
