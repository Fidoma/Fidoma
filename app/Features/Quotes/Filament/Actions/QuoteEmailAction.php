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
            ->label(__('quotes::messages.email.send'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Quote $record): bool => ! $record->is_archived)
            ->disabled(fn (): bool => ! app(SmtpConfiguration::class)->isReady())
            ->tooltip(fn (): ?string => app(SmtpConfiguration::class)->isReady()
                ? __('quotes::messages.email.tooltip')
                : __('quotes::messages.email.smtp_missing'))
            ->modalHeading(fn (Quote $record): string => __('quotes::messages.email.heading', ['number' => $record->quote_number]))
            ->modalDescription(__('quotes::messages.email.description'))
            ->modalSubmitActionLabel(__('quotes::messages.email.submit'))
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(self::schema())
            ->action(function (Quote $record, array $data): void {
                try {
                    app(SendQuoteEmail::class)->handle($record, $data);

                    Notification::make()
                        ->success()
                        ->title(__('quotes::messages.email.success'))
                        ->body(__('quotes::messages.email.success_body'))
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title(__('quotes::messages.email.failure'))
                        ->body(__('quotes::messages.email.failure_body'))
                        ->send();
                }
            });
    }

    /** @return array<Section|Placeholder> */
    private static function schema(): array
    {
        return [
            Placeholder::make('mail_configuration')
                ->label(__('quotes::messages.email.smtp_status'))
                ->content(fn (): string => app(SmtpConfiguration::class)->statusText()),
            Section::make(__('quotes::messages.email.message'))
                ->columns(2)
                ->schema([
                    Select::make('email_template_id')
                        ->label(__('quotes::messages.email.template'))
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
                        ->label(__('quotes::messages.email.to'))
                        ->email()
                        ->default(fn (Quote $record): ?string => $record->client?->client_email)
                        ->required(),
                    TextInput::make('from_name')
                        ->label(__('quotes::messages.email.sender_name'))
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['from_name'])
                        ->required(),
                    TextInput::make('from_email')
                        ->label(__('quotes::messages.email.sender_address'))
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
                        ->label(__('quotes::messages.email.subject'))
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['subject'])
                        ->required()
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label(__('quotes::messages.email.message'))
                        ->default(fn (Quote $record): string => self::defaultEmailData($record)['body'])
                        ->required()
                        ->columnSpanFull(),
                    Toggle::make('attach_pdf')
                        ->label(__('quotes::messages.email.attach_pdf'))
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
            'subject' => $renderer->render($quote, $template?->email_template_subject ?: __('quotes::messages.email.default_subject')),
            'body' => $renderer->render(
                $quote,
                $template?->email_template_body ?: __('quotes::messages.email.default_body'),
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
