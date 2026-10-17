<?php

namespace App\Features\Invoices\Actions;

use App\Features\EmailTemplates\Services\SmtpConfiguration;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LogicException;

final class SendInvoiceEmail
{
    public function __construct(
        private readonly GenerateInvoicePdf $generateInvoicePdf,
        private readonly SmtpConfiguration $smtpConfiguration,
        private readonly IssueInvoice $issueInvoice,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(Invoice $invoice, array $data): void
    {
        if (! $this->smtpConfiguration->isReady()) {
            throw new LogicException('SMTP ist nicht konfiguriert.');
        }

        $invoice = $this->issueInvoice->handle($invoice);

        $pdf = ! empty($data['attach_pdf'])
            ? $this->generateInvoicePdf->handle($invoice)->output()
            : null;

        try {
            Mail::html((string) $data['body'], function (Message $message) use ($data, $invoice, $pdf): void {
                $message
                    ->to((string) $data['to'])
                    ->from((string) $data['from_email'], (string) $data['from_name'])
                    ->subject((string) $data['subject']);

                if (! empty($data['cc'])) {
                    $message->cc((string) $data['cc']);
                }

                if (! empty($data['bcc'])) {
                    $message->bcc((string) $data['bcc']);
                }

                if ($pdf !== null) {
                    $message->attachData(
                        $pdf,
                        $this->generateInvoicePdf->filename($invoice),
                        ['mime' => 'application/pdf'],
                    );
                }
            });

            $this->audit(
                $invoice,
                'invoice_smtp_accepted',
                'success',
                'Die E-Mail für Rechnung '.$invoice->invoice_number.' wurde vom SMTP-Server zur Zustellung angenommen.',
            );
        } catch (\Throwable $exception) {
            $this->audit($invoice, 'invoice_send_failed', 'failed', 'Rechnung '.$invoice->invoice_number.' konnte nicht per E-Mail versendet werden.');

            throw $exception;
        }
    }

    private function audit(Invoice $invoice, string $event, string $status, string $message): void
    {
        DB::table('ip_audit_logs')->insert([
            'created_at' => now(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? '',
            'category' => 'email',
            'event_type' => $event,
            'status' => $status,
            'entity_type' => 'invoice',
            'entity_id' => $invoice->getKey(),
            'message' => $message,
        ]);
    }
}
