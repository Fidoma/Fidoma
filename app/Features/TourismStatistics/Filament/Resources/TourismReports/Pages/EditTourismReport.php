<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages;

use App\Features\TourismStatistics\Filament\Actions\ExportHotelStatisticsCsvAction;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\Concerns\ValidatesUniqueReportingPeriod;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\TourismReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTourismReport extends EditRecord
{
    use ValidatesUniqueReportingPeriod;

    protected static string $resource = TourismReportResource::class;

    protected function beforeSave(): void
    {
        $this->validateUniqueReportingPeriod((int) $this->record->getKey());
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportHotelStatisticsCsvAction::make(),
            DeleteAction::make(),
        ];
    }
}
