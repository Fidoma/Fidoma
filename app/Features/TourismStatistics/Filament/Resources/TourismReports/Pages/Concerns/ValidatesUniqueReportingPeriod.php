<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\Concerns;

use App\Features\TourismStatistics\Models\TourismReport;
use Illuminate\Validation\ValidationException;

trait ValidatesUniqueReportingPeriod
{
    protected function validateUniqueReportingPeriod(?int $ignoreReportId = null): void
    {
        $year = (int) ($this->data['reporting_year'] ?? 0);
        $month = (int) ($this->data['reporting_month'] ?? 0);

        $exists = TourismReport::query()
            ->where('reporting_year', $year)
            ->where('reporting_month', $month)
            ->when($ignoreReportId, fn ($query) => $query->whereKeyNot($ignoreReportId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.reporting_month' => __('tourism-statistics::messages.validation.period_unique'),
            ]);
        }
    }
}
