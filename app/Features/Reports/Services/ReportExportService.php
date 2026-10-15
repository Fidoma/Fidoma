<?php

namespace App\Features\Reports\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    /** @param list<string> $headings @param iterable<list<bool|float|int|string|null>> $rows */
    public function xlsx(string $filename, array $headings, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'fidoma-report-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headings));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return response()->download($path, $filename.'.xlsx')->deleteFileAfterSend();
    }
}
