<?php

namespace App\Features\TourismStatistics\Filament\Actions;

use App\Features\TourismStatistics\Models\TourismReport;
use App\Features\TourismStatistics\Services\HotelStatisticsCsvExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportHotelStatisticsCsvAction
{
    public static function make(): Action
    {
        return Action::make('exportCsv')
            ->label(__('tourism-statistics::messages.actions.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->schema([
                Toggle::make('include_header')
                    ->label(__('tourism-statistics::messages.export.include_header'))
                    ->helperText(__('tourism-statistics::messages.export.header_hint'))
                    ->default(false),
            ])
            ->modalHeading(__('tourism-statistics::messages.export.heading'))
            ->modalSubmitActionLabel(__('tourism-statistics::messages.actions.download'))
            ->action(fn (TourismReport $record, array $data): StreamedResponse => app(HotelStatisticsCsvExporter::class)
                ->download($record, (bool) ($data['include_header'] ?? false)));
    }
}
