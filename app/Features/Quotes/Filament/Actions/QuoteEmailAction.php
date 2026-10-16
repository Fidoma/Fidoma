<?php

namespace App\Features\Quotes\Filament\Actions;

use App\Features\EmailTemplates\Models\EmailTemplate;
use App\Features\EmailTemplates\Services\QuoteEmailTemplateRenderer;
use App\Features\EmailTemplates\Services\SmtpConfiguration;
use App\Features\Quotes\Actions\SendQuoteEmail;
use App\Features\Quotes\Models\Quote;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

final class QuoteEmailAction
{
    public static function make(): Action
    {
        return Action::make('sendEmail')
            ->label('E-Mail versenden')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Quote $record): bool => ! $record->is_archived)
            ->disabled(fn (): bool => ! app(SmtpConfiguration::class)->isReady())
            ->tooltip(fn (): ?string => app(SmtpConfiguration::class)->isReady()
                ? 'Angebot per E-Mail senden'
                : 'SMTP ist nicht konfiguriert')
            ->modalHeading(fn (Quote $record): string => 'Angebot '.$record->quote_number.' versenden')
            ->modalDescription('Versendet wird der zuletzt gespeicherte Stand des Angebots.')
            ->modalSubmitActionLabel('E-Mail senden')
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(self::schema())
            ->action(function (Quote $record, array $data): void {
                try {
                    app(SendQuoteEmail::class)->handle($record, $data);

                    Notification::make()
                        ->success()
                        ->title('Der SMTP-Server hat die E-Mail angenommen.')
                        ->body('Damit ist die endgültige Zustellung an den Empfänger noch nicht bestätigt.')
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('Die E-Mail konnte nicht versendet werden.')
                        ->body('Bitte prüfen Sie die SMTP-Konfiguration und versuchen Sie es erneut.')
                        ->send();
                }
            });
    }

    /** @return array<Section|Placeholder> */
    private static function schema(): array
    {
        return [
            Placeholder::make('mail_configuration')
                ->label('SMTP-Status')
                ->content(fn (): string => app(SmtpConfiguration::class)->statusText()),
            Section::make('Nachricht')
                ->columns(2)
                ->schema([
                    Select::make('email_template_id')
                        ->label('E-Mail-Vorlage')
                        ->options(fn () => EmailTemplate::query()
                            ->where('email_template_type', 'quote')
                            ->orderBy('email_template_title')
                            ->pluck('email_template_title', 'email_template_id'))
                        ->default(fn (): ?int => self::defaultTemplate()?->getKey())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set, Quote $record): void {
                            $template = EmailTemplate::query()->find($state);
                            if ($template === null) {
                                return;
                            }

                            foreach (self::emailData($record, $template) as $field => $value) {
                                if ($field !== 'to') {
                                    $set($field, $value);
                                }
                            }
                        }),
                    TextInput::make('to')
                        ->label('An')
                        ->email()
                        ->default(fn (Quote $record): ?string => $record->client?->client_email)
                        ->required(),
                    TextInput::make('from_name')
                        ->label('Absendername')
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['from_name'])
                        ->required(),
                    TextInput::make('from_email')
                        ->label('Absenderadresse')
                        ->email()
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['from_email'])
                        ->required(),
                    TextInput::make('cc')
                        ->label('CC')
                        ->email()
                        ->default(fn (Quote $record): ?string => self::defaultEmailData($record)['cc']),
                    TextInput::make('bcc')
                        ->label('BCC')
                        ->email()
                        ->default(fn (Quote $record): ?string => self::defaultEmailData($record)['bcc']),
                    TextInput::make('subject')
                        ->label('Betreff')
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['subject'])
                        ->required()
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label('Nachricht')
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['body'])
                        ->required()
                        ->columnSpanFull(),
                    Toggle::make('attach_pdf')
                        ->label('PDF-Angebot anhängen')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** @return array{to: ?string, from_name: string, from_email: string, cc: ?string, bcc: ?string, subject: string, body: string, attach_pdf: bool} */
    private static function defaultEmailData(Quote $quote): array
    {
        return self::emailData($quote, self::defaultTemplate());
    }

    /** @return array{to: ?string, from_name: string, from_email: string, cc: ?string, bcc: ?string, subject: string, body: string, attach_pdf: bool} */
    private static function emailData(Quote $quote, ?EmailTemplate $template): array
    {
        $renderer = app(QuoteEmailTemplateRenderer::class);

        return [
            'to' => $quote->client?->client_email,
            'from_name' => $renderer->render($quote, $template?->email_template_from_name ?: config('mail.from.name')),
            'from_email' => $renderer->render($quote, $template?->email_template_from_email ?: config('mail.from.address')),
            'cc' => filled($template?->email_template_cc) ? $renderer->render($quote, $template->email_template_cc) : null,
            'bcc' => filled($template?->email_template_bcc) ? $renderer->render($quote, $template->email_template_bcc) : null,
            'subject' => $renderer->render($quote, $template?->email_template_subject ?: 'Angebot {{{quote_number}}}'),
            'body' => $renderer->render(
                $quote,
                $template?->email_template_body ?: '<p>Guten Tag {{{client_name}}},</p><p>hiermit erhalten Sie unser Angebot {{{quote_number}}} über {{{quote_total}}}.</p><p>Freundliche Grüße</p>',
                escapeValues: true,
            ),
            'attach_pdf' => true,
        ];
    }

    private static function defaultTemplate(): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->where('email_template_type', 'quote')
            ->orderBy('email_template_title')
            ->first();
    }
}
