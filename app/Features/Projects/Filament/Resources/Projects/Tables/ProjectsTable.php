<?php

namespace App\Features\Projects\Filament\Resources\Projects\Tables;

use App\Features\Projects\Enums\ProjectStatus;
use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record): string => ProjectResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('project_number')
                    ->label(__('projects::messages.fields.number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project_name')
                    ->label(__('projects::messages.fields.name'))
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label(__('projects::messages.fields.client'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project_status')
                    ->label(__('projects::messages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->label())
                    ->color(fn (ProjectStatus $state): string => $state->color()),
                TextColumn::make('contract_amount')
                    ->label(__('projects::messages.fields.contract_amount'))
                    ->money('EUR', locale: 'de')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label(__('projects::messages.fields.invoices'))
                    ->counts('invoices'),
            ])
            ->filters([
                SelectFilter::make('project_status')
                    ->label(__('projects::messages.fields.status'))
                    ->options(collect(ProjectStatus::cases())->mapWithKeys(
                        fn (ProjectStatus $status): array => [$status->value => $status->label()],
                    )->all()),
            ])
            ->toolbarActions([
                CreateAction::make()->label(__('projects::messages.actions.create'))->icon(Heroicon::Plus),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()->disabled(fn ($record): bool => $record->invoices()->exists()),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}
