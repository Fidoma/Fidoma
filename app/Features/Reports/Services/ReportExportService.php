<?php

namespace App\Features\Reports\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportService
{
    /** @param list<string> $headings @param iterable<list<bool|float|int|string|null>> $rows */
    public function csv(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings, ';');

            foreach ($rows as $row) {
                fputcsv($output, $row, ';');
            }

            fclose($output);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
