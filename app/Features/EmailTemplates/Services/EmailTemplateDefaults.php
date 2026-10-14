<?php

namespace App\Features\EmailTemplates\Services;

use App\Features\EmailTemplates\Models\EmailTemplate;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class EmailTemplateDefaults
{
    /** @var array<string, string> */
    private const SETTINGS = [
        'invoice' => 'email_invoice_template',
        'paid' => 'email_invoice_template_paid',
        'overdue' => 'email_invoice_template_overdue',
    ];

    public function forInvoice(Invoice $invoice): ?EmailTemplate
    {
        $context = match (true) {
            $invoice->isOverdue() => 'overdue',
            $invoice->invoice_status_id->value === 4 => 'paid',
            default => 'invoice',
        };

        $template = $this->findBySetting($context);

        return $template ?? EmailTemplate::query()
            ->where('email_template_type', 'invoice')
            ->orderBy('email_template_title')
            ->first();
    }

    public function contextFor(EmailTemplate $template): string
    {
        foreach (self::SETTINGS as $context => $key) {
            if ((int) $this->setting($key) === (int) $template->getKey()) {
                return $context;
            }
        }

        return 'none';
    }

    public function assign(EmailTemplate $template, string $context): void
    {
        foreach (self::SETTINGS as $candidate => $key) {
            if ($candidate === $context) {
                $this->putSetting($key, (string) $template->getKey());
            } elseif ((int) $this->setting($key) === (int) $template->getKey()) {
                $this->putSetting($key, '');
            }
        }
    }

    private function findBySetting(string $context): ?EmailTemplate
    {
        $id = (int) $this->setting(self::SETTINGS[$context]);

        return $id > 0 ? EmailTemplate::query()->find($id) : null;
    }

    private function setting(string $key): ?string
    {
        return DB::table('ip_settings')->where('setting_key', $key)->value('setting_value');
    }

    private function putSetting(string $key, string $value): void
    {
        DB::table('ip_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => $value],
        );
    }
}
