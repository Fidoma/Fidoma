<?php

namespace App\Filament\Pages;

use App\Features\Invoices\Models\InvoiceLayout as InvoiceLayoutModel;
use App\Features\Invoices\Services\InvoiceLayoutDefaults;
use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class InvoiceLayout extends Page
{
    protected static ?string $title = 'Rechnungslayout';

    protected static ?string $navigationLabel = 'Rechnungslayout';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.invoice-layout';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $html = '';

    public string $css = '';

    public string $previewHtml = '';

    public ?string $pdfPreviewUrl = null;

    /** @var array<int, array{key: string, value: string}> */
    public array $placeholders = [];

    public function mount(InvoiceLayoutDefaults $defaults, InvoiceTemplateData $templateData): void
    {
        $layout = InvoiceLayoutModel::query()->first();
        $this->html = $layout?->html ?? $defaults->html();
        $this->css = $layout?->css ?? $defaults->css();
        $this->placeholders = $this->placeholderRows($templateData->sample());
        $this->refreshHtmlPreview();
    }

    public function updatePreview(string $html, string $css): void
    {
        $this->setDraft($html, $css);
        $this->refreshHtmlPreview();
    }

    public function saveTemplate(string $html, string $css): void
    {
        $this->setDraft($html, $css);

        InvoiceLayoutModel::query()->updateOrCreate(
            ['id' => InvoiceLayoutModel::query()->value('id') ?? 1],
            ['html' => $this->html, 'css' => $this->css],
        );

        $this->refreshHtmlPreview();
        Notification::make()->title('Rechnungslayout gespeichert')->success()->send();
    }

    public function showPdfPreview(string $html, string $css): void
    {
        $this->setDraft($html, $css);
        session()->put('invoice-layout-preview', ['html' => $this->html, 'css' => $this->css]);
        $this->pdfPreviewUrl = route('invoice-layout.pdf-preview', ['v' => now()->getTimestampMs()]);
    }

    private function setDraft(string $html, string $css): void
    {
        validator(['html' => $html, 'css' => $css], [
            'html' => ['required', 'string', 'max:250000'],
            'css' => ['nullable', 'string', 'max:100000'],
        ])->validate();

        $this->html = $html;
        $this->css = $css;
    }

    private function refreshHtmlPreview(): void
    {
        $this->pdfPreviewUrl = null;
        $this->previewHtml = app(InvoiceTemplateRenderer::class)->render(
            $this->html,
            $this->css,
            app(InvoiceTemplateData::class)->sample(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{key: string, value: string}>
     */
    private function placeholderRows(array $data): array
    {
        $values = [
            'client.address' => $data['client']['address'],
            'client.city' => $data['client']['city'],
            'client.company' => $data['client']['company'],
            'client.email' => $data['client']['email'],
            'client.name' => $data['client']['name'],
            'client.number' => $data['client']['number'],
            'invoice.date' => $data['invoice']['date'],
            'invoice.due_date' => $data['invoice']['due_date'],
            'invoice.number' => $data['invoice']['number'],
            'invoice.terms' => $data['invoice']['terms'],
            'issuer.address' => $data['issuer']['address'],
            'issuer.city' => $data['issuer']['city'],
            'issuer.company' => $data['issuer']['company'],
            'issuer.email' => $data['issuer']['email'],
            'issuer.iban' => $data['issuer']['iban'],
            'issuer.name' => $data['issuer']['name'],
            'issuer.vat_id' => $data['issuer']['vat_id'],
            'items' => count($data['items']).' Beispielpositionen',
            'totals.subtotal' => $this->formatMoney($data['totals']['subtotal']),
            'totals.tax' => $this->formatMoney($data['totals']['tax']),
            'totals.total' => $this->formatMoney($data['totals']['total']),
        ];

        ksort($values, SORT_NATURAL | SORT_FLAG_CASE);

        return collect($values)
            ->map(fn (mixed $value, string $key): array => ['key' => $key, 'value' => (string) $value])
            ->values()
            ->all();
    }

    private function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.').' €';
    }
}
