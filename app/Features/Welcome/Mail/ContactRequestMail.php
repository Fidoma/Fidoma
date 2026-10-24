<?php

namespace App\Features\Welcome\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $contactEmail,
        public readonly string $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('website.contact_from_email'), 'Fidoma Website'),
            replyTo: [new Address($this->contactEmail)],
            subject: 'Kontaktanfrage über Fidoma',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'welcome::emails.contact-request');
    }
}
