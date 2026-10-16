<?php

namespace App\Features\Documents\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Actions\UnassignPaperlessDocument;
use App\Features\Documents\Filament\Tables\Columns\PaperlessDocumentIdsColumn;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class LinkedBankTransactions extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public int $documentId;

    public function mount(int $documentId): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
        $this->documentId = $documentId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Verknüpfte Banktransaktionen')
            ->description('Hier werden ausschließlich Banktransaktionen angezeigt, die mit diesem Dokument verknüpft sind.')
            ->query(fn (): Builder => BankTransaction::query()
                ->whereHas('mappings', fn (Builder $query): Builder => $query->where('document_id', $this->documentId))
                ->with('mappings')
                ->withCount('mappings')
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
                            ->color('danger')
                            ->grow(false),
                        TextColumn::make('booking_date')
                            ->label('Buchungsdatum')
                            ->date('d.m.Y')
                            ->color('gray')
                            ->grow(false),
                        PaperlessDocumentIdsColumn::make(),
                    ])
                        ->alignment(Alignment::End)
                        ->space(1)
                        ->grow(false),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('unlinkSelected')
                    ->label('Verknüpfung lösen')
                    ->icon(Heroicon::OutlinedLinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ausgewählte Verknüpfungen lösen')
                    ->modalDescription(fn (Collection $records): string => $records->count().' '.($records->count() === 1
                        ? 'Verknüpfung mit diesem Dokument wird entfernt.'
                        : 'Verknüpfungen mit diesem Dokument werden entfernt.'))
                    ->action(fn (Collection $records) => $this->unlinkSelected($records)),
            ])
            ->selectCurrentPageOnly()
            ->searchPlaceholder('Name, IBAN, Zweck oder Referenz')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Keine verknüpften Banktransaktionen')
            ->emptyStateDescription('Dieses Dokument ist derzeit mit keiner Banktransaktion verknüpft.')
            ->emptyStateIcon(Heroicon::OutlinedLinkSlash);
    }

    /** @param Collection<int, BankTransaction> $records */
    public function unlinkSelected(Collection $records): void
    {
        try {
            app(UnassignPaperlessDocument::class)->handleMany(
                $this->documentId,
                $records->mapWithKeys(fn (BankTransaction $record): array => [
                    $record->getKey() => $record->assignmentFingerprint(),
                ])->all(),
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Die Verknüpfung konnte nicht gelöst werden.')
                ->body(collect($exception->errors())->flatten()->first())
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()
                ->danger()
                ->title('Die Verknüpfung konnte nicht gelöst werden.')
                ->body('Bitte versuchen Sie es erneut.')
                ->send();

            return;
        }

        $unlinkedCount = $records->count();
        $this->deselectAllTableRecords();
        $this->resetTable();

        Notification::make()
            ->success()
            ->title($unlinkedCount === 1
                ? 'Verknüpfung wurde gelöst.'
                : $unlinkedCount.' Verknüpfungen wurden gelöst.')
            ->send();

        $this->dispatch('paperless-mapping-removed');
    }

    #[On('paperless-mapping-created')]
    public function refreshLinkedTransactions(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.features.documents.linked-bank-transactions');
    }
}
