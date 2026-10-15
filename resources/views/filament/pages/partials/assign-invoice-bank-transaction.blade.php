@livewire(
    \App\Features\Invoices\Livewire\AssignInvoiceBankTransaction::class,
    ['invoiceId' => $invoiceId],
    key('invoice-assignment-'.$invoiceId)
)
