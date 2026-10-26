<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages;

use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\Concerns\ValidatesUniqueReportingPeriod;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\TourismReportResource;
use App\Filament\Concerns\WithoutPageHeading;
use App\Filament\Resources\Pages\CreateRecord;

class CreateTourismReport extends CreateRecord
{
    use ValidatesUniqueReportingPeriod, WithoutPageHeading;

    protected static string $resource = TourismReportResource::class;

    protected function beforeCreate(): void
    {
        $this->validateUniqueReportingPeriod();
    }
}
