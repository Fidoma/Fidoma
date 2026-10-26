<?php

namespace App\Features\TourismStatistics\Models;

use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourismReport extends Model
{
    protected $fillable = [
        'reporting_year',
        'reporting_month',
        'beds_offered',
        'available_guest_room_days',
        'occupied_guest_room_days',
        'closure_day',
        'reopening_date',
        'deregistration_day',
        'remarks',
        'data_source',
    ];

    protected function casts(): array
    {
        return [
            'reporting_year' => 'integer',
            'reporting_month' => 'integer',
            'beds_offered' => 'integer',
            'available_guest_room_days' => 'integer',
            'occupied_guest_room_days' => 'integer',
            'closure_day' => 'integer',
            'reopening_date' => 'date',
            'deregistration_day' => 'integer',
        ];
    }

    public function countryValues(): HasMany
    {
        $countries = ResidenceCountryCatalog::sortedByCode();
        $orderCases = collect($countries)
            ->keys()
            ->map(fn (int $position): string => 'WHEN ? THEN '.$position)
            ->implode(' ');
        $countryKeys = array_column($countries, 'key');

        return $this->hasMany(TourismReportCountryValue::class, 'tourism_report_id')
            ->orderByRaw("CASE country_key {$orderCases} ELSE ? END", [...$countryKeys, count($countries)]);
    }

    public function totalArrivals(): int
    {
        return (int) $this->countryValues->sum('arrivals');
    }

    public function totalOvernightStays(): int
    {
        return (int) $this->countryValues->sum('overnight_stays');
    }
}
