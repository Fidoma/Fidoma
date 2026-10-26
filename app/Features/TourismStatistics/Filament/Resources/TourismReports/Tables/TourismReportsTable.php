<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports\Tables;

use App\Features\TourismStatistics\Filament\Actions\ExportHotelStatisticsCsvAction;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\TourismReportResource;
use App\Features\TourismStatistics\Models\TourismReport;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class TourismReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('countryValues'))
            ->defaultSort('reporting_year', 'desc')
            ->recordUrl(fn (TourismReport $record): string => TourismReportResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('reporting_period')
                    ->label(__('tourism-statistics::messages.fields.reporting_period'))
                    ->state(fn (TourismReport $record): string => sprintf('%02d/%04d', $record->reporting_month, $record->reporting_year))
                    ->sortable(['reporting_year', 'reporting_month']),
                TextColumn::make('beds_offered')
                    ->label(__('tourism-statistics::messages.fields.beds_offered'))
                    ->numeric(locale: 'de')
                    ->sortable(),
                TextColumn::make('arrivals_total')
                    ->label(__('tourism-statistics::messages.fields.arrivals_total'))
                    ->state(fn (TourismReport $record): int => $record->totalArrivals())
                    ->numeric(locale: 'de'),
                TextColumn::make('overnight_stays_total')
                    ->label(__('tourism-statistics::messages.fields.overnight_stays_total'))
                    ->state(fn (TourismReport $record): int => $record->totalOvernightStays())
                    ->numeric(locale: 'de'),
                TextColumn::make('updated_at')
                    ->label(__('tourism-statistics::messages.fields.updated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('reporting_year')
                    ->label(__('tourism-statistics::messages.fields.year'))
                    ->options(fn (): array => TourismReport::query()
                        ->distinct()
                        ->orderByDesc('reporting_year')
                        ->pluck('reporting_year', 'reporting_year')
                        ->all()),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label(__('tourism-statistics::messages.actions.create'))
                    ->icon(Heroicon::Plus),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ExportHotelStatisticsCsvAction::make(),
                    DeleteAction::make(),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}
