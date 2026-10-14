<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Schemas;

use App\Features\EmailTemplates\Services\InvoiceEmailTemplateRenderer;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Vorlage')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email_template_title')
                            ->label('Bezeichnung')
                            ->required()
                            ->maxLength(255),
                        Select::make('email_template_type')
                            ->label('Typ')
                            ->options([
                                'invoice' => 'Rechnung',
                                'quote' => 'Angebot',
                            ])
                            ->default('invoice')
                            ->required(),
                        Select::make('default_usage')
                            ->label('Standardverwendung')
                            ->options([
                                'none' => 'Keine',
                                'invoice' => 'Standardrechnung',
                                'paid' => 'Bezahlte Rechnung',
                                'overdue' => 'Überfällige Rechnung',
                            ])
                            ->default('none')
                            ->dehydrated(),
                    ]),
                Section::make('Absender und Empfänger')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email_template_from_name')
                            ->label('Absendername')
                            ->maxLength(255),
                        TextInput::make('email_template_from_email')
                            ->label('Absenderadresse')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('email_template_cc')
                            ->label('CC')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('email_template_bcc')
                            ->label('BCC')
                            ->email()
                            ->maxLength(255),
                    ]),
                Section::make('Inhalt')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email_template_subject')
                            ->label('Betreff')
                            ->required()
                            ->maxLength(255),
                        Placeholder::make('available_placeholders')
                            ->label('Verfügbare Platzhalter')
                            ->content(new HtmlString(collect(InvoiceEmailTemplateRenderer::placeholders())
                                ->map(fn (string $placeholder): string => '<code>{{{'.$placeholder.'}}}</code>')
                                ->implode(' &nbsp; '))),
                        RichEditor::make('email_template_body')
                            ->label('Nachricht')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
