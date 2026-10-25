@livewire(
    \App\Features\Invoices\Livewire\InvoiceDetails::class,
    ['invoiceId' => $invoiceId],
    key('invoice-details-'.$invoiceId)
)
