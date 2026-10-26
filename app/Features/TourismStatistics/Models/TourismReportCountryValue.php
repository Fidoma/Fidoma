<?php

namespace App\Features\TourismStatistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourismReportCountryValue extends Model
{
    protected $fillable = ['country_key', 'arrivals', 'overnight_stays'];

    protected function casts(): array
    {
        return [
            'arrivals' => 'integer',
            'overnight_stays' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TourismReport::class, 'tourism_report_id');
    }
}
