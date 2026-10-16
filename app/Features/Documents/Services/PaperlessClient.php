<?php

namespace App\Features\Documents\Services;

use App\Features\Documents\Data\PaperlessDocument;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PaperlessClient
{
    public const DOCUMENT_PREVIEW_WINDOW = 'paperless-document-preview';

    /** @return array{count: int, documents: list<PaperlessDocument>} */
    public function documents(string $search = '', int $page = 1, int $perPage = 20, bool $cacheDocuments = true): array
    {
        $payload = $this->request()->get('api/documents/', array_filter([
            'page' => max(1, $page),
            'page_size' => min(50, max(1, $perPage)),
            'ordering' => '-document_date',
            'query' => trim($search) ?: null,
            'tags__id' => config('services.paperless.filter_tag_id') ?: null,
            'storage_path__id' => config('services.paperless.storage_path_id') ?: null,
            'document_type__id' => config('services.paperless.document_type_id') ?: null,
        ]))->throw()->json();

        $correspondents = $this->correspondents();
        $documents = collect($payload['results'] ?? [])
            ->filter(fn (mixed $document): bool => is_array($document))
            ->map(fn (array $document): PaperlessDocument => PaperlessDocument::fromPaperless($document, $correspondents))
            ->filter(fn (PaperlessDocument $document): bool => $document->id > 0)
            ->values()
            ->all();

        if ($cacheDocuments) {
            foreach ($documents as $document) {
                Cache::put($this->documentCacheKey($document->id), $document->toArray(), now()->addMinutes(5));
            }
        }

        return [
            'count' => max(0, (int) ($payload['count'] ?? count($documents))),
            'documents' => $documents,
        ];
    }

    /** @return list<PaperlessDocument> */
    public function allDocuments(): array
    {
        $page = 1;
        $perPage = 50;
        $documents = [];

        do {
            $result = $this->documents(page: $page, perPage: $perPage, cacheDocuments: false);
            $documents = [...$documents, ...$result['documents']];
            $page++;
        } while ($result['documents'] !== [] && count($documents) < $result['count']);

        return $documents;
    }

    /** @return list<PaperlessDocument> */
    public function cachedDocuments(): array
    {
        $documents = Cache::get($this->documentsCacheKey(), []);

        if (! is_array($documents)) {
            return [];
        }

        return collect($documents)
            ->filter(fn (mixed $document): bool => is_array($document))
            ->map(fn (array $document): PaperlessDocument => new PaperlessDocument(...$document))
            ->values()
            ->all();
    }

    /** @return list<PaperlessDocument> */
    public function refreshCachedDocuments(): array
    {
        $documents = $this->allDocuments();

        Cache::forever(
            $this->documentsCacheKey(),
            collect($documents)->map(fn (PaperlessDocument $document): array => $document->toArray())->all(),
        );

        return $documents;
    }

    public function document(int $documentId, bool $fresh = false): PaperlessDocument
    {
        if ($documentId < 1) {
            throw new RuntimeException('Die Paperless-Dokument-ID ist ungültig.');
        }

        $cacheKey = $this->documentCacheKey($documentId);
        if (! $fresh && is_array($cached = Cache::get($cacheKey))) {
            return new PaperlessDocument(...$cached);
        }

        $payload = $this->request()->get("api/documents/{$documentId}/")->throw()->json();
        $document = PaperlessDocument::fromPaperless($payload, $this->correspondents());

        if ($document->id !== $documentId) {
            throw new RuntimeException('Paperless hat ein unerwartetes Dokument zurückgegeben.');
        }

        Cache::put($cacheKey, $document->toArray(), now()->addMinutes(5));

        return $document;
    }

    public function documentDetailsUrl(int $documentId): ?string
    {
        $url = rtrim((string) config('services.paperless.url'), '/');

        if ($url === '' || $documentId < 1) {
            return null;
        }

        return "{$url}/documents/{$documentId}/details";
    }

    /** @return array<int, string> */
    private function correspondents(): array
    {
        return Cache::remember($this->cachePrefix().':correspondents', now()->addMinutes(15), function (): array {
            $payload = $this->request()->get('api/correspondents/', [
                'page_size' => 1000,
                'ordering' => 'name',
            ])->throw()->json();

            return collect($payload['results'] ?? [])
                ->filter(fn (mixed $correspondent): bool => is_array($correspondent))
                ->mapWithKeys(fn (array $correspondent): array => [
                    (int) ($correspondent['id'] ?? 0) => trim((string) ($correspondent['name'] ?? '')),
                ])
                ->filter()
                ->all();
        });
    }

    private function request(): PendingRequest
    {
        $url = rtrim((string) config('services.paperless.url'), '/');
        $token = trim((string) config('services.paperless.token'));

        if ($url === '' || $token === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Paperless-URL und API-Token müssen konfiguriert sein.');
        }

        return Http::baseUrl($url.'/')
            ->withToken($token, 'Token')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 200);
    }

    private function cachePrefix(): string
    {
        return 'paperless:'.hash('sha256', (string) config('services.paperless.url'));
    }

    private function documentCacheKey(int $documentId): string
    {
        return $this->cachePrefix().":document:{$documentId}";
    }

    private function documentsCacheKey(): string
    {
        $filters = implode('|', [
            (string) config('services.paperless.filter_tag_id'),
            (string) config('services.paperless.storage_path_id'),
            (string) config('services.paperless.document_type_id'),
        ]);

        return $this->cachePrefix().':documents:'.hash('sha256', $filters);
    }
}
