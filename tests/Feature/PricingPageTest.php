<?php

namespace Tests\Feature;

use App\Features\Welcome\Mail\ContactRequestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    public function test_pricing_page_links_requests_to_the_contact_form(): void
    {
        config()->set('website.links.donation', 'https://example.test/donation');
        config()->set('website.paypal.donation', 'https://paypal.test/donation');

        $this->get('/preise')
            ->assertOk()
            ->assertSee('https://paypal.test/donation', escape: false)
            ->assertSee('href="#kontakt"', escape: false)
            ->assertSee('name="email"', escape: false)
            ->assertSee('name="message"', escape: false)
            ->assertSee('Kontakt aufnehmen')
            ->assertSee('Senden');
    }

    public function test_contact_request_is_validated_and_sent(): void
    {
        Mail::fake();
        config()->set('website.contact_email', 'contact@example.test');

        $this->post('/kontakt', [
            'email' => 'customer@example.test',
            'message' => 'Ich interessiere mich für Fidoma Cloud.',
        ])->assertRedirect(route('pricing').'#kontakt')
            ->assertSessionHas('contact_sent', true);

        Mail::assertSent(ContactRequestMail::class, function (ContactRequestMail $mail): bool {
            return $mail->hasTo('contact@example.test')
                && $mail->hasReplyTo('customer@example.test')
                && $mail->usesMailer('contact')
                && $mail->contactMessage === 'Ich interessiere mich für Fidoma Cloud.';
        });
    }

    public function test_contact_request_requires_a_valid_email_and_message(): void
    {
        Mail::fake();

        $this->from('/preise#kontakt')->post('/kontakt', [
            'email' => 'keine-email',
            'message' => '',
        ])->assertRedirect('/preise#kontakt')
            ->assertSessionHasErrors(['email', 'message']);

        Mail::assertNothingSent();
    }
}
