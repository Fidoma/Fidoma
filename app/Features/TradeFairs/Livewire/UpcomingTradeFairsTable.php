<?php

namespace App\Features\TradeFairs\Livewire;

use App\Features\TradeFairs\Models\TradeFair;
use App\Features\TradeFairs\Services\TradeFairCsvImporter;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class UpcomingTradeFairsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function table(Table $table): Table
    {
        $today = CarbonImmutable::today('Europe/Berlin');

        return $table
            ->query(TradeFair::query()
                ->visibleInScheduler()
                ->orderBy('start_date')
                ->orderBy('name'))
            ->queryStringIdentifier('upcoming-trade-fairs')
            ->heading(__('trade-fairs::messages.upcoming'))
            ->description(__('trade-fairs::messages.table.description'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('trade-fairs::messages.table.name'))
                    ->description(fn (TradeFair $record): ?string => $record->description)
                    ->searchable(['name', 'description'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('start_date')
                    ->label(__('trade-fairs::messages.table.start_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('trade-fairs::messages.table.end_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('trade-fairs::messages.table.location'))
                    ->placeholder('–')
                    ->toggleable(),
                TextColumn::make('website')
                    ->label(__('trade-fairs::messages.table.website'))
                    ->formatStateUsing(fn (): string => __('trade-fairs::messages.table.open'))
                    ->url(fn (TradeFair $record): ?string => $record->website)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->placeholder('–'),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label(__('trade-fairs::messages.table.period'))
                    ->options([
                        'all' => __('trade-fairs::messages.table.periods.all'),
                        'current' => __('trade-fairs::messages.table.periods.current'),
                        'next_7_days' => __('trade-fairs::messages.table.periods.next_7_days'),
                        'next_14_days' => __('trade-fairs::messages.table.periods.next_14_days'),
                        'all_future' => __('trade-fairs::messages.table.periods.all_future'),
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data) use ($today): Builder {
                        return match ($data['value'] ?? 'all') {
                            'current' => $query
                                ->whereDate('start_date', '<=', $today->toDateString())
                                ->whereDate('end_date', '>=', $today->toDateString()),
                            'next_7_days' => $query
                                ->whereDate('start_date', '<=', $today->addDays(7)->toDateString())
                                ->whereDate('end_date', '>=', $today->toDateString()),
                            'next_14_days' => $query
                                ->whereDate('start_date', '<=', $today->addDays(14)->toDateString())
                                ->whereDate('end_date', '>=', $today->toDateString()),
                            'all_future' => $query->whereDate('end_date', '>=', $today->toDateString()),
                            default => $query,
                        };
                    }),
            ])
            ->searchPlaceholder(__('trade-fairs::messages.table.search'))
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('trade-fairs::messages.table.empty'))
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public function importTradeFairsAction(): Action
    {
        return Action::make('importTradeFairs')
            ->label(__('trade-fairs::messages.import.action'))
            ->tooltip(__('trade-fairs::messages.import.action'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->iconButton()
            ->modalHeading(__('trade-fairs::messages.import.heading'))
            ->modalSubmitActionLabel(__('trade-fairs::messages.import.submit'))
            ->schema([
                FileUpload::make('csv_file')
                    ->label(__('trade-fairs::messages.import.file'))
                    ->disk('local')
                    ->directory('imports/trade-fairs')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $path = $this->uploadedPath($data['csv_file'] ?? null);
                $errors = [];

                try {
                    abort_unless(
                        str_starts_with($path, 'imports/trade-fairs/') && Storage::disk('local')->exists($path),
                        422,
                        __('trade-fairs::messages.import.unreadable'),
                    );
                    $result = app(TradeFairCsvImporter::class)->import(
                        Storage::disk('local')->path($path),
                        function (int $line, string $reason) use (&$errors): void {
                            $errors[] = __('trade-fairs::messages.import.row_error', compact('line', 'reason'));
                        },
                    );
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->danger()
                        ->title(__('trade-fairs::messages.import.failed'))
                        ->body($exception->getMessage())
                        ->send();

                    return;
                } finally {
                    if ($path !== '') {
                        Storage::disk('local')->delete($path);
                    }
                }

                $notification = Notification::make()
                    ->title(__('trade-fairs::messages.import.completed', $result));

                if ($result['failed'] > 0) {
                    $notification->warning()->body(implode("\n", array_slice($errors, 0, 5)));
                } else {
                    $notification->success();
                }

                $notification->send();
                $this->resetTable();
                $this->dispatch('trade-fairs-imported');
            });
    }

    #[On('trade-fairs-imported')]
    #[On('trade-fairs-day-changed')]
    public function refreshTable(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('trade-fairs::upcoming-trade-fairs-table');
    }

    private function uploadedPath(mixed $state): string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        return is_string($state) ? $state : '';
    }
}
