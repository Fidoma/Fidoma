<?php

namespace App\Features\TradeFairs\Http\Controllers;

use App\Features\TradeFairs\Models\TradeFair;
use App\Features\TradeFairs\Queries\TradeFairQuery;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeFairController extends Controller
{
    public function upcoming(TradeFairQuery $query): JsonResponse
    {
        $today = CarbonImmutable::today('Europe/Berlin');

        return response()->json([
            'data' => $query->upcoming($today)->map($this->serialize(...))->values(),
        ]);
    }

    public function range(Request $request, TradeFairQuery $query): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after:start'],
        ]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $validated['start'], 'Europe/Berlin');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $validated['end'], 'Europe/Berlin');

        abort_if($start === false || $end === false || $start->diffInDays($end) > 366, 422);

        return response()->json([
            'data' => $query->overlapping($start, $end)->map($this->serialize(...))->values(),
        ]);
    }

    /** @return array{id: int, name: string, description: ?string, start_date: string, end_date: string, location: ?string, website: ?string} */
    private function serialize(TradeFair $fair): array
    {
        return [
            'id' => $fair->getKey(),
            'name' => $fair->name,
            'description' => $fair->description,
            'start_date' => $fair->start_date->toDateString(),
            'end_date' => $fair->end_date->toDateString(),
            'location' => $fair->location,
            'website' => $fair->website,
        ];
    }
}
