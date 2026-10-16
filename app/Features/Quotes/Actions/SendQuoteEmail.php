<?php

namespace App\Features\Quotes\Actions;

use App\Features\EmailTemplates\Services\SmtpConfiguration;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\Quote;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LogicException;

final class SendQuoteEmail
{
    public function __construct(
        private readonly GenerateQuotePdf $generateQuotePdf,
        private readonly SmtpConfiguration $smtpConfiguration,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(Quote $quote, array $data): void
    {
        if (! $this->smtpConfiguration->isReady()) {
            throw new LogicException('SMTP ist nicht konfiguriert.');
        }

        $pdf = ! empty($data['attach_pdf'])
            ? $this->generateQuotePdf->handle($quote)->output()
            : null;

        try {
            Mail::html((string) $data['body'], function (Message $message) use ($data, $pdf, $quote): void {
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
                        $this->generateQuotePdf->filename($quote),
                        ['mime' => 'application/pdf'],
                    );
                }
            });

            if ($quote->quote_status_id === QuoteStatus::Draft) {
                $quote->update(['quote_status_id' => QuoteStatus::Sent]);
            }

            $this->audit(
                $quote,
                'quote_smtp_accepted',
                'success',
                'Die E-Mail für Angebot '.$quote->quote_number.' wurde vom SMTP-Server zur Zustellung angenommen.',
            );
        } catch (\Throwable $exception) {
            $this->audit($quote, 'quote_send_failed', 'failed', 'Angebot '.$quote->quote_number.' konnte nicht per E-Mail versendet werden.');

            throw $exception;
        }
    }

    private function audit(Quote $quote, string $event, string $status, string $message): void
    {
        DB::table('ip_audit_logs')->insert([
            'created_at' => now(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? '',
            'category' => 'email',
            'event_type' => $event,
            'status' => $status,
            'entity_type' => 'quote',
            'entity_id' => $quote->getKey(),
            'message' => $message,
        ]);
    }
}
