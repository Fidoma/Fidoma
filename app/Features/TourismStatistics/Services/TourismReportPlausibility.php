<?php

namespace App\Features\TourismStatistics\Services;

use App\Features\TourismStatistics\Models\TourismReport;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use Illuminate\Support\MessageBag;

final class TourismReportPlausibility
{
    public function validate(TourismReport $report): MessageBag
    {
        $errors = new MessageBag;

        if ($report->available_guest_room_days !== null
            && $report->occupied_guest_room_days !== null
            && $report->occupied_guest_room_days > $report->available_guest_room_days) {
            $errors->add('occupied_guest_room_days', __('tourism-statistics::messages.validation.occupied_exceeds_available'));
        }

        if ($report->remarks !== null && iconv('UTF-8', 'Windows-1252', $report->remarks) === false) {
            $errors->add('remarks', __('tourism-statistics::messages.validation.windows_1252'));
        }

        $values = $report->countryValues->keyBy('country_key');

        foreach (ResidenceCountryCatalog::all() as $country) {
            $value = $values->get($country['key']);

            if ($value === null) {
                $errors->add('country_values', __('tourism-statistics::messages.validation.missing_country', [
                    'country' => $country['label'],
                ]));

                continue;
            }

            $maximum = ResidenceCountryCatalog::maximum($country['key']);

            if ($value->arrivals !== null && ($value->arrivals < 0 || $value->arrivals > $maximum)) {
                $errors->add("country_values.{$country['key']}.arrivals", __('tourism-statistics::messages.validation.country_maximum', [
                    'country' => $country['label'],
                    'maximum' => number_format($maximum, 0, ',', '.'),
                ]));
            }

            if ($value->overnight_stays !== null && ($value->overnight_stays < 0 || $value->overnight_stays > $maximum)) {
                $errors->add("country_values.{$country['key']}.overnight_stays", __('tourism-statistics::messages.validation.country_maximum', [
                    'country' => $country['label'],
                    'maximum' => number_format($maximum, 0, ',', '.'),
                ]));
            }

            if ($value->arrivals !== null
                && $value->overnight_stays !== null
                && $value->overnight_stays < $value->arrivals) {
                $errors->add("country_values.{$country['key']}.overnight_stays", __('tourism-statistics::messages.validation.overnights_below_arrivals'));
            }
        }

        return $errors;
    }
}
