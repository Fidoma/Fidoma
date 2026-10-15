<?php

namespace App\Features\Payments\Filament\Resources\Payments\Pages;

use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Filament\Resources\Payments\PaymentResource;
use App\Features\Payments\Models\Payment;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = PaymentResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Alle')
                ->badge(Payment::query()->count())
                ->badgeColor('gray'),
        ];

        $paymentMethods = PaymentMethod::query()
            ->withCount('payments')
            ->orderBy('payment_method_name')
            ->get();

        foreach ($paymentMethods as $paymentMethod) {
            $tabs['method-'.$paymentMethod->getKey()] = Tab::make($paymentMethod->payment_method_name ?: 'Ohne Bezeichnung')
                ->badge($paymentMethod->payments_count)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('payment_method_id', $paymentMethod->getKey()));
        }

        return $tabs;
    }
}
