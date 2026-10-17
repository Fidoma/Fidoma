<?php

namespace App\Features\Invoices\Filament\Pages;

use App\Features\Invoices\Models\InvoiceLayout as InvoiceLayoutModel;
use App\Features\Invoices\Services\InvoiceLayoutDefaults;
use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Renderless;

class InvoiceLayout extends Page
{
    use WithoutPageHeading;

    protected static ?string $title = 'PDF-Layouts';

    protected static ?string $navigationLabel = 'PDF-Layout';

    protected static ?string $slug = 'pdf-layouts';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.invoice-layout';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $html = '';

    public string $css = '';

    public string $previewHtml = '';

    public ?string $pdfPreviewUrl = null;

    public string $activeType = 'invoice';

    /** @var array<int, array{key: string, value: string}> */
    public array $placeholders = [];

    public function mount(InvoiceLayoutDefaults $defaults, InvoiceTemplateData $templateData): void
    {
        $this->activeType = in_array(request()->query('type'), ['invoice', 'quote', 'reservation'], true)
            ? request()->query('type')
            : 'invoice';
        $layout = InvoiceLayoutModel::query()->where('type', $this->activeType)->first();
        $this->html = $layout?->html ?? $defaults->html($this->activeType);
        $this->css = $layout?->css ?? $defaults->css();
        $this->placeholders = $this->placeholderRows($templateData->sample($this->activeType));
        $this->refreshHtmlPreview();
    }

    #[Renderless]
    public function updatePreview(string $html, string $css): void
    {
        $this->setDraft($html, $css);
        $this->previewHtml = app(InvoiceTemplateRenderer::class)->render(
            $this->html,
            $this->css,
            app(InvoiceTemplateData::class)->sample($this->activeType),
        );

        $this->dispatch('invoice-layout-preview-updated', html: $this->previewHtml);
    }

    public function saveTemplate(string $html, string $css): void
    {
        $this->setDraft($html, $css);

        InvoiceLayoutModel::query()->updateOrCreate(
            ['type' => $this->activeType],
            ['html' => $this->html, 'css' => $this->css],
        );

        $this->refreshHtmlPreview();
        Notification::make()->title('PDF-Layout gespeichert')->success()->send();
    }

    public function showPdfPreview(string $html, string $css): void
    {
        $this->setDraft($html, $css);
        session()->put('pdf-layout-preview', [
            'type' => $this->activeType,
            'html' => $this->html,
            'css' => $this->css,
        ]);
        $this->pdfPreviewUrl = route('pdf-layouts.pdf-preview', ['v' => now()->getTimestampMs()]);
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
            app(InvoiceTemplateData::class)->sample($this->activeType),
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
            'issuer.address' => $data['issuer']['address'],
            'issuer.city' => $data['issuer']['city'],
            'issuer.company' => $data['issuer']['company'],
            'issuer.email' => $data['issuer']['email'],
            'issuer.iban' => $data['issuer']['iban'],
            'issuer.name' => $data['issuer']['name'],
            'issuer.vat_id' => $data['issuer']['vat_id'],
            'items' => count($data['items']).' Beispielpositionen',
            'tax_rows' => count($data['tax_rows']).' Steuerzeilen',
            'totals.subtotal' => $this->formatMoney($data['totals']['subtotal']),
            'totals.tax' => $this->formatMoney($data['totals']['tax']),
            'totals.total' => $this->formatMoney($data['totals']['total']),
        ];

        $documentData = $data[$this->activeType];

        foreach ($documentData as $key => $value) {
            $values[$this->activeType.'.'.$key] = $value;
        }

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
