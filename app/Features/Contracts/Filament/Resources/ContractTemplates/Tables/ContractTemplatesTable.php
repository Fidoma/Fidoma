<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Tables;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Models\ContractTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(fn ($record): string => ContractTemplateResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label(__('contracts::messages.template.fields.title'))->searchable()->sortable(),
                TextColumn::make('status')->label(__('contracts::messages.template.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('contracts::messages.template.statuses.'.$state))
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('version')->label(__('contracts::messages.template.fields.version'))->sortable(),
                TextColumn::make('updated_at')->label(__('contracts::messages.template.fields.modified'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('contracts::messages.template.fields.status'))
                    ->options([
                        'active' => __('contracts::messages.template.statuses.active'),
                        'draft' => __('contracts::messages.template.statuses.draft'),
                    ]),
            ])
            ->toolbarActions([
                CreateAction::make()->label(__('contracts::messages.template.singular'))->icon(Heroicon::Plus),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->disabled(fn (ContractTemplate $record): bool => $record->contracts()->exists())
                        ->tooltip(fn (ContractTemplate $record): ?string => $record->contracts()->exists()
                            ? __('contracts::messages.template.delete.in_use')
                            : null),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}
