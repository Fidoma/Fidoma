<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateId = DB::table('ip_email_templates')
            ->where('email_template_type', 'invoice')
            ->orderBy('email_template_id')
            ->value('email_template_id');

        if ($templateId === null) {
            $templateId = DB::table('ip_email_templates')->insertGetId([
                'email_template_title' => 'Standardrechnung',
                'email_template_type' => 'invoice',
                'email_template_subject' => '{{{invoice_type}}} {{{invoice_number}}}',
                'email_template_from_name' => config('mail.from.name'),
                'email_template_from_email' => config('mail.from.address'),
                'email_template_cc' => null,
                'email_template_bcc' => null,
                'email_template_pdf_template' => null,
                'email_template_body' => '<p>Guten Tag {{{client_name}}},</p><p>anbei erhalten Sie Ihre {{{invoice_type}}} {{{invoice_number}}} über {{{invoice_total}}}.</p><p>Das Zahlungsziel ist der {{{invoice_date_due}}}.</p><p>Freundliche Grüße</p>',
            ]);
        }

        $currentDefault = DB::table('ip_settings')
            ->where('setting_key', 'email_invoice_template')
            ->value('setting_value');

        if (blank($currentDefault)) {
            DB::table('ip_settings')->updateOrInsert(
                ['setting_key' => 'email_invoice_template'],
                ['setting_value' => (string) $templateId],
            );
        }
    }

    public function down(): void
    {
        // Existing templates and user selections are deliberately preserved.
    }
};
