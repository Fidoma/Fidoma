@livewire(
    \App\Features\Invoices\Livewire\AssignInvoiceBankTransaction::class,
    ['invoiceId' => $invoiceId, 'paymentId' => $paymentId ?? null],
    key('invoice-assignment-'.$invoiceId.'-payment-'.($paymentId ?? 'new'))
)
