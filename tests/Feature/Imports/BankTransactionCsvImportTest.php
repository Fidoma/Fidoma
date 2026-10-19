<?php

namespace Tests\Feature\Imports;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Imports\Filament\Pages\DataImport;
use App\Features\Imports\Livewire\BankTransactionCsvPreview;
use App\Features\Imports\Services\BankTransactionCsvImporter;
use App\Features\Imports\Services\CsvReader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BankTransactionCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_data_import_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(DataImport::getUrl())
            ->assertOk()
            ->assertSeeText('Banktransaktionen importieren');

        $component = Livewire::actingAs(User::factory()->create())
            ->test(DataImport::class);

        $this->assertNull($component->instance()->getHeading());
    }

    public function test_it_imports_a_mapped_german_bank_csv_with_a_sign_column(): void
    {
        $file = UploadedFile::fake()->createWithContent('bank.csv', implode("\n", [
            'Konto;Buchungstag;Betrag;Währung;Verwendungszweck;Auftraggeber;Externe ID;Soll/Haben',
            'DE12 3456 7890;17.10.2026;1.234,56;eur;Rechnung 42;Beispiel GmbH;bank-1;CREDIT',
            'DE12 3456 7890;18.10.2026;25,40;EUR;Gebühr;Bank;bank-2;DEBIT',
        ]));
        $reader = app(CsvReader::class);
        $importer = app(BankTransactionCsvImporter::class);
        $inspection = $reader->inspect($file->getPathname());
        $mapping = $importer->suggestMapping($inspection['headers']);
        $mapping['transaction_reference'] = 'Externe ID';

        $count = $importer->import($file->getPathname(), $mapping, [
            'pending' => '0',
            'validated' => '0',
        ], [
            'column' => 'Soll/Haben',
            'negative_value' => 'DEBIT',
            'positive_value' => 'CREDIT',
        ]);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('ip_bank_transactions', [
            'account_iban' => 'DE1234567890',
            'booking_date' => '2026-10-17',
            'amount' => '1234.5600',
            'currency' => 'EUR',
            'counterparty_name' => 'Beispiel GmbH',
            'transaction_reference' => hash('sha256', 'DE1234567890|bank-1'),
        ]);
        $this->assertDatabaseHas('ip_bank_transactions', [
            'booking_date' => '2026-10-18',
            'amount' => '-25.4000',
            'transaction_reference' => hash('sha256', 'DE1234567890|bank-2'),
        ]);
    }

    public function test_it_recognizes_and_imports_the_csv_camt_v8_columns(): void
    {
        $file = UploadedFile::fake()->createWithContent('camt-v8.csv', implode("\n", [
            '"Auftragskonto";"Buchungstag";"Valutadatum";"Buchungstext";"Verwendungszweck";"Glaeubiger ID";"Mandatsreferenz";"Kundenreferenz (End-to-End)";"Sammlerreferenz";"Lastschrift Ursprungsbetrag";"Auslagenersatz Ruecklastschrift";"Beguenstigter/Zahlungspflichtiger";"Kontonummer/IBAN";"BIC (SWIFT-Code)";"Betrag";"Waehrung";"Info"',
            '"DE001234567890";"16.10.26";"19.10.26";"SEPA-ELV-LASTSCHRIFT";"Beispieleinkauf";"DE00ZZZ00000000000";"MANDAT-1";"END-TO-END-1";"";"";"";"Beispielmarkt";"DE009876543210";"TESTDE00";"-31,89";"EUR";"Umsatz vorgemerkt"',
        ]));
        $importer = app(BankTransactionCsvImporter::class);
        $headers = app(CsvReader::class)->inspect($file->getPathname())['headers'];
        $mapping = $importer->suggestMapping($headers);

        $this->assertSame('Auftragskonto', $mapping['account_iban']);
        $this->assertSame('Beguenstigter/Zahlungspflichtiger', $mapping['counterparty_name']);
        $this->assertSame('Kontonummer/IBAN', $mapping['counterparty_iban']);
        $this->assertSame('Kundenreferenz (End-to-End)', $mapping['end_to_end_id']);
        $this->assertSame('Info', $mapping['pending']);

        $this->assertSame(1, $importer->import($file->getPathname(), $mapping, ['validated' => '0']));

        $transaction = BankTransaction::query()->sole();
        $this->assertSame('DE001234567890', $transaction->account_iban);
        $this->assertSame('2026-10-16', $transaction->booking_date->toDateString());
        $this->assertSame('-31.8900', $transaction->amount);
        $this->assertSame('Beispielmarkt', $transaction->counterparty_name);
        $this->assertSame('DE009876543210', $transaction->counterparty_iban);
        $this->assertSame('END-TO-END-1', $transaction->end_to_end_id);
        $this->assertTrue($transaction->pending);
    }

    public function test_the_page_previews_and_imports_an_uploaded_csv(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('imports/bank-transactions/page.csv', implode("\n", [
            'account_iban;booking_date;amount;currency;purpose',
            'DE1234567890;2026-10-18;49,90;EUR;Testimport',
        ]));

        Livewire::actingAs(User::factory()->create())
            ->test(DataImport::class)
            ->set('data.csv_file', ['imports/bank-transactions/page.csv'])
            ->call('inspectCsv')
            ->assertHasNoErrors()
            ->assertSet('csvHeaders.0', 'account_iban')
            ->assertSet('data.mapping.amount', 'amount')
            ->assertSeeText('Datenbankspalte')
            ->assertSeeText('Erkannte CSV-Spalte')
            ->assertSeeText('account_iban')
            ->assertSeeHtml('aria-label="CSV-Spalte für Konto-IBAN"')
            ->assertSeeText('CSV-Vorschau')
            ->assertSeeText('Testimport')
            ->call('importCsv')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ip_bank_transactions', [
            'amount' => '49.9000',
            'purpose' => 'Testimport',
        ]);
        Storage::disk('local')->assertMissing('imports/bank-transactions/page.csv');
    }

    public function test_a_duplicate_rolls_back_the_complete_csv_import(): void
    {
        $file = UploadedFile::fake()->createWithContent('duplicates.csv', implode("\n", [
            'account_iban,booking_date,amount,currency,external_id',
            'DE1234567890,2026-10-17,10.00,EUR,same-reference',
            'DE1234567890,2026-10-18,20.00,EUR,same-reference',
        ]));
        $importer = app(BankTransactionCsvImporter::class);
        $headers = app(CsvReader::class)->inspect($file->getPathname())['headers'];
        $mapping = $importer->suggestMapping($headers);

        try {
            $importer->import($file->getPathname(), $mapping, [
                'pending' => '0',
                'validated' => '0',
            ]);
            $this->fail('The duplicate import did not fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('bereits importiert', $exception->errors()['data.csv_file'][0]);
        }

        $this->assertSame(0, BankTransaction::query()->count());
    }

    public function test_the_preview_uses_paginated_status_tabs_for_new_and_possible_duplicate_rows(): void
    {
        Storage::fake('local');
        $rows = ['account_iban;booking_date;amount;currency;purpose'];

        for ($index = 1; $index <= 12; $index++) {
            $rows[] = sprintf('DE1234567890;2026-10-%02d;%d,00;EUR;Vorschau %02d', $index, $index, $index);
        }

        Storage::disk('local')->put('imports/bank-transactions/preview.csv', implode("\n", $rows));
        BankTransaction::query()->create([
            'account_iban' => 'DE1234567890',
            'booking_date' => '2026-10-01',
            'amount' => '1.0000',
            'currency' => 'EUR',
            'transaction_reference' => hash('sha256', 'existing-transaction'),
            'pending' => false,
            'validated' => false,
        ]);

        Livewire::test(BankTransactionCsvPreview::class, [
            'path' => 'imports/bank-transactions/preview.csv',
            'mapping' => [
                'account_iban' => 'account_iban',
                'booking_date' => 'booking_date',
                'amount' => 'amount',
                'currency' => 'currency',
                'purpose' => 'purpose',
            ],
            'defaults' => ['pending' => '0', 'validated' => '0'],
            'signRule' => [],
        ])
            ->assertSeeText('Alle')
            ->assertSeeText('Neu')
            ->assertSeeText('Mögliche Duplikate')
            ->assertSeeText('Vorschau 10')
            ->assertDontSeeText('Vorschau 11')
            ->call('showTab', 'duplicates')
            ->assertSet('activeTab', 'duplicates')
            ->assertSeeText('Vorschau 01')
            ->assertSeeText('Mögliches Duplikat')
            ->assertDontSeeText('Vorschau 02')
            ->call('showTab', 'new')
            ->assertSet('activeTab', 'new')
            ->assertDontSeeText('Vorschau 01')
            ->assertSeeText('Vorschau 02');
    }

    public function test_selected_preview_rows_can_be_removed_and_are_skipped_during_import(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('imports/bank-transactions/remove.csv', implode("\n", [
            'account_iban;booking_date;amount;currency;purpose',
            'DE1234567890;2026-10-17;10,00;EUR;Entfernen',
            'DE1234567890;2026-10-18;20,00;EUR;Importieren',
        ]));
        $mapping = [
            'account_iban' => 'account_iban',
            'booking_date' => 'booking_date',
            'amount' => 'amount',
            'currency' => 'currency',
            'purpose' => 'purpose',
        ];

        $component = Livewire::test(BankTransactionCsvPreview::class, [
            'path' => 'imports/bank-transactions/remove.csv',
            'mapping' => $mapping,
            'defaults' => ['pending' => '0', 'validated' => '0'],
            'signRule' => [],
        ]);
        $records = $component->get('previewRecords');
        $firstRecordId = $records[0]['id'];
        $secondRecordId = $records[1]['id'];

        $this->assertNotSame($firstRecordId, $secondRecordId);
        $this->assertSame($firstRecordId, $records[0]['__key']);
        $this->assertSame($secondRecordId, $records[1]['__key']);
        $this->assertSame(
            [$firstRecordId, $secondRecordId],
            $component->instance()->getTableRecords()->getCollection()->keys()->all(),
        );

        $component
            ->assertTableBulkActionExists('removeSelected')
            ->callTableBulkAction('removeSelected', [$firstRecordId])
            ->assertSet('excludedLines', [2])
            ->assertSet('previewRecords.0.id', $secondRecordId)
            ->assertSet('previewRecords.0.line', 3)
            ->assertSet('selectedTableRecords', [])
            ->assertSet('deselectedTableRecords', [])
            ->assertSet('isTrackingDeselectedTableRecords', false)
            ->assertDispatched('csv-preview-rows-removed')
            ->assertDontSeeText('Entfernen')
            ->assertSeeText('Importieren')
            ->callTableBulkAction('removeSelected', [$secondRecordId])
            ->assertSet('excludedLines', [2, 3])
            ->assertSet('previewRecords', [])
            ->assertDontSeeText('Importieren');

        $count = app(BankTransactionCsvImporter::class)->import(
            Storage::disk('local')->path('imports/bank-transactions/remove.csv'),
            $mapping,
            ['pending' => '0', 'validated' => '0'],
            [],
            [2],
        );

        $this->assertSame(1, $count);
        $this->assertDatabaseMissing('ip_bank_transactions', ['purpose' => 'Entfernen']);
        $this->assertDatabaseHas('ip_bank_transactions', ['purpose' => 'Importieren']);
    }

    public function test_the_import_page_keeps_rows_removed_by_the_preview_excluded(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(DataImport::class)
            ->dispatch('csv-preview-rows-removed', lines: [4, 2, 4])
            ->assertSet('excludedLines', [2, 4]);
    }

    public function test_select_all_can_remove_multiple_consecutive_pages(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('imports/bank-transactions/select-all.csv', implode("\n", [
            'account_iban;booking_date;amount;currency;purpose',
            'DE1234567890;2026-10-17;10,00;EUR;Zeile 1',
            'DE1234567890;2026-10-18;20,00;EUR;Zeile 2',
            'DE1234567890;2026-10-19;30,00;EUR;Zeile 3',
            'DE1234567890;2026-10-20;40,00;EUR;Zeile 4',
        ]));
        $mapping = [
            'account_iban' => 'account_iban',
            'booking_date' => 'booking_date',
            'amount' => 'amount',
            'currency' => 'currency',
            'purpose' => 'purpose',
        ];

        $component = Livewire::test(BankTransactionCsvPreview::class, [
            'path' => 'imports/bank-transactions/select-all.csv',
            'mapping' => $mapping,
            'defaults' => ['pending' => '0', 'validated' => '0'],
            'signRule' => [],
        ]);
        $recordIds = collect($component->get('previewRecords'))->pluck('id')->all();

        $component
            ->set('isTrackingDeselectedTableRecords', true)
            ->callTableBulkAction('removeSelected', array_slice($recordIds, 0, 2))
            ->assertSet('excludedLines', [2, 3])
            ->assertSet('isTrackingDeselectedTableRecords', false)
            ->assertSet('deselectedTableRecords', [])
            ->callTableBulkAction('removeSelected', array_slice($recordIds, 2, 2))
            ->assertSet('excludedLines', [2, 3, 4, 5])
            ->assertSeeText('Keine CSV-Zeilen in diesem Bereich')
            ->assertDontSeeText('0 Transaktionen wurden entfernt.');
    }
}
