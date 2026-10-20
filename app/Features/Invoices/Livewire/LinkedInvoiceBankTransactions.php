<?php

namespace App\Features\Invoices\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Actions\UnassignInvoiceBankTransactions;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class LinkedInvoiceBankTransactions extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public int $invoiceId;

    public ?int $paymentId = null;

    public function mount(int $invoiceId, ?int $paymentId = null): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
        $this->invoiceId = $invoiceId;
        $this->paymentId = $paymentId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Verknüpfte Zahlungseingänge')
            ->description($this->paymentId
                ? 'Diese Banktransaktionen sind mit der ausgewählten Zahlung verknüpft.'
                : 'Diese Banktransaktionen wurden als Zahlungen der Rechnung verbucht.')
            ->query(fn (): Builder => BankTransaction::query()
                ->whereHas('mappings.payment', fn (Builder $query): Builder => $query
                    ->where('invoice_id', $this->invoiceId)
                    ->when($this->paymentId, fn (Builder $query, int $paymentId): Builder => $query->whereKey($paymentId)))
                ->orderByDesc('booking_date')
                ->orderByDesc('id'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('counterparty_name')
                            ->label('Gegenpartei')
                            ->placeholder('Unbekannte Gegenpartei')
                            ->weight('medium')
                            ->searchable([
                                'counterparty_name',
                                'counterparty_iban',
                                'purpose',
                                'booking_text',
                                'transaction_reference',
                                'bank_transaction_id',
                                'bank_reference',
                                'end_to_end_id',
                            ])
                            ->wrap(),
                        TextColumn::make('purpose')
                            ->label('Verwendungszweck')
                            ->placeholder('Kein Verwendungszweck')
                            ->color('gray')
                            ->limit(55)
                            ->wrap(),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Betrag')
                            ->money(fn (BankTransaction $record): string => $record->currency, locale: 'de')
                            ->color('success')
                            ->grow(false),
                        TextColumn::make('booking_date')
                            ->label('Buchungsdatum')
                            ->date('d.m.Y')
                            ->color('gray')
                            ->grow(false),
                        TextColumn::make('status')
                            ->label('Status')
                            ->state('Dieser Rechnung zugeordnet')
                            ->badge()
                            ->color('success')
                            ->grow(false),
                    ])->alignment(Alignment::End)->space(1)->grow(false),
                ]),
            ])
            ->filters([
                Filter::make('counterparty_name')
                    ->label('Gegenpartei')
                    ->schema([TextInput::make('value')->label('Gegenpartei')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('counterparty_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                BulkAction::make('unlinkSelected')
                    ->label('Verknüpfung lösen')
                    ->icon(Heroicon::OutlinedLinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ausgewählte Verknüpfungen lösen')
                    ->modalDescription(fn (Collection $records): string => $this->paymentId
                        ? $records->count().' '.($records->count() === 1
                            ? 'Verknüpfung mit der Zahlung wird entfernt.'
                            : 'Verknüpfungen mit der Zahlung werden entfernt.')
                        : $records->count().' '.($records->count() === 1
                            ? 'Zahlung und Verknüpfung werden von der Rechnung entfernt.'
                            : 'Zahlungen und Verknüpfungen werden von der Rechnung entfernt.'))
                    ->action(fn (Collection $records) => $this->unlinkSelected($records)),
            ])
            ->selectCurrentPageOnly()
            ->searchPlaceholder('Name, IBAN, Zweck oder Referenz')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Keine verknüpften Zahlungseingänge')
            ->emptyStateDescription('Dieser Rechnung ist derzeit keine Banktransaktion zugeordnet.')
            ->emptyStateIcon(Heroicon::OutlinedLinkSlash);
    }

    /** @param Collection<int, BankTransaction> $records */
    public function unlinkSelected(Collection $records): void
    {
        try {
            app(UnassignInvoiceBankTransactions::class)->handleMany(
                $this->invoiceId,
                $records->mapWithKeys(fn (BankTransaction $record): array => [
                    $record->getKey() => $record->assignmentFingerprint(),
                ])->all(),
            );
        } catch (ValidationException $exception) {
            Notification::make()->danger()
                ->title('Die Verknüpfung konnte nicht entfernt werden.')
                ->body(collect($exception->errors())->flatten()->first())
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->danger()
                ->title('Die Verknüpfung konnte nicht entfernt werden.')
                ->body('Bitte versuchen Sie es erneut.')
                ->send();

            return;
        }

        $this->deselectAllTableRecords();
        $this->resetTable();

        Notification::make()->success()
            ->title($records->count() === 1
                ? 'Verknüpfung wurde entfernt.'
                : $records->count().' Verknüpfungen wurden entfernt.')
            ->send();

        $this->dispatch('invoice-mapping-removed');
    }

    #[On('invoice-mapping-created')]
    #[On('invoice-linked-tab-opened')]
    public function refreshLinkedTransactions(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('invoices::linked-invoice-bank-transactions');
    }
}
