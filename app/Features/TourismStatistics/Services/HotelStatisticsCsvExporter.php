<?php

namespace App\Features\TourismStatistics\Services;

use App\Features\TourismStatistics\Models\TourismReport;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HotelStatisticsCsvExporter
{
    public const FIELD_COUNT = 116;

    /** @return list<string> */
    public function headings(): array
    {
        $countries = ResidenceCountryCatalog::all();

        return [
            'Satzart',
            'Anzahl_Betten',
            'angebotene_Gästezimmertage',
            'belegte_Gästezimmertage',
            'Schliessung',
            'Wiedereröffnung',
            'Abmeldung',
            ...array_column($countries, 'arrivals_heading'),
            'Bemerkungen',
            ...array_column($countries, 'overnight_heading'),
        ];
    }

    /** @return list<int|string|null> */
    public function row(TourismReport $report): array
    {
        $report->loadMissing('countryValues');
        $this->ensurePlausible($report);
        $values = $report->countryValues->keyBy('country_key');

        $row = [
            '1',
            $report->beds_offered,
            $report->available_guest_room_days,
            $report->occupied_guest_room_days,
            $report->closure_day,
            $report->reopening_date?->format('d.m.Y'),
            $report->deregistration_day,
        ];

        foreach (ResidenceCountryCatalog::all() as $country) {
            $row[] = $values->get($country['key'])?->arrivals;
        }

        $row[] = $report->remarks;

        foreach (ResidenceCountryCatalog::all() as $country) {
            $row[] = $values->get($country['key'])?->overnight_stays;
        }

        if (count($row) !== self::FIELD_COUNT) {
            throw new RuntimeException('The HOTE export row must contain exactly 116 fields.');
        }

        return $row;
    }

    public function content(TourismReport $report, bool $includeHeader = false): string
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Unable to create the HOTE export stream.');
        }

        if ($includeHeader) {
            fputcsv($stream, $this->headings(), ';', '"', '', "\r\n");
        }

        fputcsv($stream, $this->row($report), ';', '"', '', "\r\n");
        rewind($stream);
        $utf8 = stream_get_contents($stream);
        fclose($stream);

        if ($utf8 === false || ($encoded = iconv('UTF-8', 'Windows-1252', $utf8)) === false) {
            throw new RuntimeException('The HOTE export could not be encoded as Windows-1252.');
        }

        return $encoded;
    }

    public function download(TourismReport $report, bool $includeHeader = false): StreamedResponse
    {
        $content = $this->content($report, $includeHeader);
        $user = auth()->user();
        $businessName = $user instanceof User ? ($user->company_name ?: $user->name) : null;
        $business = Str::slug($businessName ?: config('app.name', 'Fidoma'));
        $filename = sprintf('HOTE_%s_%04d-%02d.csv', $business, $report->reporting_year, $report->reporting_month);

        return response()->streamDownload(
            static fn () => print $content,
            $filename,
            ['Content-Type' => 'text/csv; charset=windows-1252'],
        );
    }

    private function ensurePlausible(TourismReport $report): void
    {
        $errors = app(TourismReportPlausibility::class)->validate($report);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }
    }
}
