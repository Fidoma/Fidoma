<?php

namespace Tests\Feature\Features\Documents;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Actions\AssignPaperlessDocument;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignPaperlessDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_assigns_a_paperless_document_to_a_negative_transaction(): void
    {
        $this->fakePaperlessDocuments(101);
        $this->actingAs(User::factory()->create());
        $transaction = $this->createTransaction('-120.0000');

        $mapping = app(AssignPaperlessDocument::class)->handle(101, $transaction->getKey(), $transaction->assignmentFingerprint());

        $this->assertDatabaseHas('ip_bank_transaction_mappings', [
            'bank_transaction_mapping_id' => $mapping->getKey(),
            'bank_transaction_id' => $transaction->getKey(),
            'payment_id' => null,
            'document_id' => 101,
        ]);
        $this->assertTrue($transaction->refresh()->validated);
    }

    public function test_one_document_can_be_assigned_to_multiple_transactions(): void
    {
        $this->fakePaperlessDocuments(201);
        $this->actingAs(User::factory()->create());
        $first = $this->createTransaction('-40.0000');
        $second = $this->createTransaction('-60.0000');
        $assign = app(AssignPaperlessDocument::class);

        $assign->handle(201, $first->getKey(), $first->assignmentFingerprint());
        $assign->handle(201, $second->getKey(), $second->assignmentFingerprint());

        $this->assertDatabaseCount('ip_bank_transaction_mappings', 2);
        $this->assertSame(1, $first->mappings()->where('document_id', 201)->count());
        $this->assertSame(1, $second->mappings()->where('document_id', 201)->count());
    }

    public function test_multiple_selected_transactions_are_assigned_atomically(): void
    {
        $this->fakePaperlessDocuments(251);
        $this->actingAs(User::factory()->create());
        $first = $this->createTransaction('-40.0000');
        $second = $this->createTransaction('-60.0000');

        $mappings = app(AssignPaperlessDocument::class)->handleMany(251, [
            $first->getKey() => $first->assignmentFingerprint(),
            $second->getKey() => $second->assignmentFingerprint(),
        ]);

        $this->assertCount(2, $mappings);
        $this->assertDatabaseHas('ip_bank_transaction_mappings', [
            'bank_transaction_id' => $first->getKey(),
            'document_id' => 251,
        ]);
        $this->assertDatabaseHas('ip_bank_transaction_mappings', [
            'bank_transaction_id' => $second->getKey(),
            'document_id' => 251,
        ]);
        $this->assertTrue($first->refresh()->validated);
        $this->assertTrue($second->refresh()->validated);
    }

    public function test_bulk_assignment_rolls_back_all_new_mappings_when_one_assignment_is_invalid(): void
    {
        $this->fakePaperlessDocuments(252);
        $this->actingAs(User::factory()->create());
        $first = $this->createTransaction('-40.0000');
        $duplicate = $this->createTransaction('-60.0000');
        BankTransactionMapping::query()->create([
            'bank_transaction_id' => $duplicate->getKey(),
            'payment_id' => null,
            'document_id' => 252,
        ]);

        try {
            app(AssignPaperlessDocument::class)->handleMany(252, [
                $first->getKey() => $first->assignmentFingerprint(),
                $duplicate->getKey() => $duplicate->assignmentFingerprint(),
            ]);
            $this->fail('The invalid bulk assignment was not rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('besteht bereits', $exception->errors()['transaction'][0]);
        }

        $this->assertDatabaseMissing('ip_bank_transaction_mappings', [
            'bank_transaction_id' => $first->getKey(),
            'document_id' => 252,
        ]);
        $this->assertFalse($first->refresh()->validated);
        $this->assertDatabaseCount('ip_bank_transaction_mappings', 1);
    }

    public function test_one_transaction_can_be_assigned_to_multiple_documents(): void
    {
        $this->fakePaperlessDocuments(301, 302);
        $this->actingAs(User::factory()->create());
        $transaction = $this->createTransaction('-100.0000');
        $assign = app(AssignPaperlessDocument::class);

        $assign->handle(301, $transaction->getKey(), $transaction->assignmentFingerprint());
        $transaction->refresh();
        $assign->handle(302, $transaction->getKey(), $transaction->assignmentFingerprint());

        $this->assertSame([301, 302], $transaction->mappings()->orderBy('document_id')->pluck('document_id')->all());
    }

    public function test_identical_assignment_cannot_be_saved_twice(): void
    {
        $this->fakePaperlessDocuments(401);
        $this->actingAs(User::factory()->create());
        $transaction = $this->createTransaction('-75.0000');
        $assign = app(AssignPaperlessDocument::class);
        $assign->handle(401, $transaction->getKey(), $transaction->assignmentFingerprint());
        $transaction->refresh();

        try {
            $assign->handle(401, $transaction->getKey(), $transaction->assignmentFingerprint());
            $this->fail('The duplicate assignment was not rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame('Diese Zuordnung besteht bereits.', $exception->errors()['transaction'][0]);
        }

        $this->assertDatabaseCount('ip_bank_transaction_mappings', 1);
    }

    public function test_changed_transaction_is_rejected_without_partial_write(): void
    {
        $this->fakePaperlessDocuments(501);
        $this->actingAs(User::factory()->create());
        $transaction = $this->createTransaction('-75.0000');
        $fingerprint = $transaction->assignmentFingerprint();
        $transaction->update(['pending' => true]);

        try {
            app(AssignPaperlessDocument::class)->handle(501, $transaction->getKey(), $fingerprint);
            $this->fail('The stale transaction was not rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('zwischenzeitlich geändert', $exception->errors()['transaction'][0]);
        }

        $this->assertDatabaseCount('ip_bank_transaction_mappings', 0);
    }

    public function test_database_constraint_rejects_a_concurrent_identical_mapping(): void
    {
        $transaction = $this->createTransaction('-75.0000');
        $attributes = [
            'bank_transaction_id' => $transaction->getKey(),
            'payment_id' => null,
            'document_id' => 551,
        ];
        BankTransactionMapping::query()->create($attributes);

        try {
            BankTransactionMapping::query()->create($attributes);
            $this->fail('The database accepted an identical mapping.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertDatabaseCount('ip_bank_transaction_mappings', 1);
    }

    public function test_missing_paperless_document_does_not_create_a_mapping(): void
    {
        Config::set('services.paperless.url', 'https://paperless.test');
        Config::set('services.paperless.token', 'test-token');
        Http::preventStrayRequests();
        Http::fake([
            'paperless.test/api/documents/999/' => Http::response([], 404),
        ]);
        $this->actingAs(User::factory()->create());
        $transaction = $this->createTransaction('-25.0000');

        try {
            app(AssignPaperlessDocument::class)->handle(999, $transaction->getKey(), $transaction->assignmentFingerprint());
            $this->fail('The missing Paperless document was accepted.');
        } catch (RequestException $exception) {
            $this->assertSame(404, $exception->response->status());
        }

        $this->assertDatabaseCount('ip_bank_transaction_mappings', 0);
    }

    public function test_unauthenticated_assignment_is_forbidden_before_paperless_is_called(): void
    {
        $this->fakePaperlessDocuments(601);
        $transaction = $this->createTransaction('-25.0000');

        try {
            app(AssignPaperlessDocument::class)->handle(601, $transaction->getKey(), $transaction->assignmentFingerprint());
            $this->fail('The unauthenticated assignment was not rejected.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('This action is unauthorized.', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('ip_bank_transaction_mappings', 0);
    }

    private function createTransaction(string $amount): BankTransaction
    {
        return BankTransaction::query()->create([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-14',
            'amount' => $amount,
            'currency' => 'EUR',
            'counterparty_name' => 'Muster Lieferant GmbH',
            'purpose' => 'Rechnung R-2026',
            'transaction_reference' => hash('sha256', fake()->unique()->uuid()),
            'pending' => false,
            'validated' => false,
        ]);
    }

    private function fakePaperlessDocuments(int ...$documentIds): void
    {
        Config::set('services.paperless.url', 'https://paperless.test');
        Config::set('services.paperless.token', 'test-token');
        Http::preventStrayRequests();
        $responses = ['paperless.test/api/correspondents/*' => Http::response(['results' => []])];

        foreach ($documentIds as $documentId) {
            $responses["paperless.test/api/documents/{$documentId}/"] = Http::response([
                'id' => $documentId,
                'title' => "Dokument {$documentId}",
                'document_date' => '2026-10-14',
                'custom_fields' => [],
            ]);
        }

        Http::fake($responses);
    }
}
