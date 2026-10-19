<?php

namespace App\Features\TradeFairs\Console\Commands;

use App\Features\TradeFairs\Services\TradeFairCsvImporter;
use Illuminate\Console\Command;

class ImportTradeFairs extends Command
{
    protected $signature = 'trade-fairs:import {file : Pfad zur CSV-Datei}';

    protected $description = 'Importiert Messetermine aus einer CSV-Datei';

    public function handle(TradeFairCsvImporter $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("CSV-Datei ist nicht lesbar: {$path}");

            return self::FAILURE;
        }

        $result = $importer->import(
            $path,
            fn (int $line, string $reason) => $this->warn("Zeile {$line}: {$reason}"),
        );

        $this->table(
            ['Neu', 'Aktualisiert', 'Unverändert', 'Fehlerhaft'],
            [[$result['created'], $result['updated'], $result['unchanged'], $result['failed']]],
        );

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
