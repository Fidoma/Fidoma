<?php

namespace App\Features\Welcome\Actions;

use App\Features\Welcome\Mail\ContactRequestMail;
use Illuminate\Support\Facades\Mail;

class SendContactRequest
{
    public function execute(string $email, string $content): void
    {
        Mail::mailer('contact')
            ->to((string) config('website.contact_email'))
            ->send(new ContactRequestMail($email, $content));
    }
}
