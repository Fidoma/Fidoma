<?php

namespace App\Features\EmailTemplates\Services;

final class SmtpConfiguration
{
    public function isReady(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function statusText(): string
    {
        if (! $this->isReady()) {
            return 'SMTP ist nicht konfiguriert. Tragen Sie die Zugangsdaten in der .env-Datei ein und leeren Sie anschließend den Konfigurationscache.';
        }

        return 'Ein Erfolg bestätigt nur, dass der SMTP-Server die E-Mail zur Zustellung angenommen hat. Die endgültige Zustellung kann erst über Rückläufer oder einen Provider-Webhook bestätigt werden.';
    }
}
