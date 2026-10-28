<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Tables;

use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class AiTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->recordUrl(fn ($record): string => AiTemplateResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label(__('artificial-intelligence::messages.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area')
                    ->label(__('artificial-intelligence::messages.fields.area'))
                    ->badge()
                    ->formatStateUsing(fn (AiTemplateArea $state): string => $state->label())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('artificial-intelligence::messages.fields.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('artificial-intelligence::messages.fields.sort_order'))
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('area')
                    ->label(__('artificial-intelligence::messages.fields.area'))
                    ->options(AiTemplateArea::options()),
                TernaryFilter::make('is_active')
                    ->label(__('artificial-intelligence::messages.fields.active')),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label(__('artificial-intelligence::messages.create_template'))
                    ->icon(Heroicon::Plus)
                    ->url(AiTemplateResource::getUrl('create')),
            ]);
    }
}
