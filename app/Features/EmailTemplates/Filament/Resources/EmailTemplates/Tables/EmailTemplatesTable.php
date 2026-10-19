<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Tables;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('email_template_title')
            ->recordUrl(fn ($record): string => EmailTemplateResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('email_template_title')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email_template_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invoice' => 'Rechnung',
                        'quote' => 'Angebot',
                        default => $state,
                    }),
                TextColumn::make('email_template_subject')
                    ->label('Betreff')
                    ->searchable(),
                TextColumn::make('email_template_from_email')
                    ->label('Absender'),
            ])
            ->filters([
                SelectFilter::make('email_template_type')
                    ->label('Typ')
                    ->options([
                        'invoice' => 'Rechnung',
                        'quote' => 'Angebot',
                    ]),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('E-Mail-Vorlage')
                    ->icon(Heroicon::Plus)
                    ->url(EmailTemplateResource::getUrl('create')),
            ]);
    }
}
