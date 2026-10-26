<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports\Schemas;

use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\CreateTourismReport;
use App\Features\TourismStatistics\Services\PopulateTourismReportFromReservations;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class TourismReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Hidden::make('data_source')->default('manual'),
            Section::make(__('tourism-statistics::messages.sections.reporting_period'))
                ->afterHeader(
                    Actions::make([
                        Action::make('populateFromReservations')
                            ->label(__('tourism-statistics::messages.actions.populate'))
                            ->icon(Heroicon::OutlinedSparkles)
                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTourismReport)
                            ->action(function (Get $get, Set $set): void {
                                $prefill = app(PopulateTourismReportFromReservations::class)->handle(
                                    (int) $get('reporting_year'),
                                    (int) $get('reporting_month'),
                                );

                                $set('beds_offered', $prefill->bedsOffered);
                                $set('available_guest_room_days', $prefill->availableGuestRoomDays);
                                $set('occupied_guest_room_days', $prefill->occupiedGuestRoomDays);
                                $set('countryValues', $prefill->countryValues);
                                $set('data_source', 'reservations');

                                Notification::make()
                                    ->success()
                                    ->title(__('tourism-statistics::messages.populate.success'))
                                    ->body(__('tourism-statistics::messages.populate.summary', [
                                        'count' => $prefill->reservationItemCount,
                                    ]))
                                    ->send();
                            }),
                    ]),
                )
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('reporting_month')
                        ->label(__('tourism-statistics::messages.fields.month'))
                        ->options(self::months())
                        ->default((int) now()->subMonth()->format('n'))
                        ->required(),
                    Select::make('reporting_year')
                        ->label(__('tourism-statistics::messages.fields.year'))
                        ->options(self::years())
                        ->default((int) now()->subMonth()->format('Y'))
                        ->required(),
                ]),
            Section::make(__('tourism-statistics::messages.sections.capacity'))
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextInput::make('beds_offered')
                        ->label(__('tourism-statistics::messages.fields.beds_offered'))
                        ->integer()
                        ->minValue(0)
                        ->maxValue(999999)
                        ->required(),
                    TextInput::make('available_guest_room_days')
                        ->label(__('tourism-statistics::messages.fields.available_guest_room_days'))
                        ->integer()
                        ->minValue(0)
                        ->maxValue(999999),
                    TextInput::make('occupied_guest_room_days')
                        ->label(__('tourism-statistics::messages.fields.occupied_guest_room_days'))
                        ->integer()
                        ->minValue(0)
                        ->maxValue(999999)
                        ->rule(fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $available = $get('available_guest_room_days');

                            if ($value !== null && $available !== null && (int) $value > (int) $available) {
                                $fail(__('tourism-statistics::messages.validation.occupied_exceeds_available'));
                            }
                        }),
                ]),
            Section::make(__('tourism-statistics::messages.sections.closure'))
                ->description(__('tourism-statistics::messages.sections.closure_hint'))
                ->columns(['default' => 1, 'md' => 3])
                ->collapsed()
                ->schema([
                    TextInput::make('closure_day')
                        ->label(__('tourism-statistics::messages.fields.closure_day'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(31),
                    DatePicker::make('reopening_date')
                        ->label(__('tourism-statistics::messages.fields.reopening_date'))
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    TextInput::make('deregistration_day')
                        ->label(__('tourism-statistics::messages.fields.deregistration_day'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(31),
                ]),
            Section::make(__('tourism-statistics::messages.sections.countries'))
                ->description(__('tourism-statistics::messages.sections.countries_hint'))
                ->columns(1)
                ->schema([
                    Repeater::make('countryValues')
                        ->label(__('tourism-statistics::messages.fields.country_values'))
                        ->relationship()
                        ->default(ResidenceCountryCatalog::emptyRowsByCode())
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->live(onBlur: true)
                        ->table([
                            TableColumn::make(__('tourism-statistics::messages.fields.code')),
                            TableColumn::make(__('tourism-statistics::messages.fields.residence_country')),
                            TableColumn::make(__('tourism-statistics::messages.fields.arrivals'))->markAsRequired(),
                            TableColumn::make(__('tourism-statistics::messages.fields.overnight_stays'))->markAsRequired(),
                        ])
                        ->schema([
                            Hidden::make('country_key')->required(),
                            Placeholder::make('country_code')
                                ->hiddenLabel()
                                ->content(fn (Get $get): string => ResidenceCountryCatalog::find((string) $get('country_key'))['code'] ?? '–'),
                            Placeholder::make('country_label')
                                ->hiddenLabel()
                                ->content(function (Get $get): string {
                                    $country = ResidenceCountryCatalog::find((string) $get('country_key'));

                                    return $country['label'] ?? (string) $get('country_key');
                                }),
                            TextInput::make('arrivals')
                                ->hiddenLabel()
                                ->integer()
                                ->minValue(0)
                                ->maxValue(fn (Get $get): int => ResidenceCountryCatalog::maximum((string) $get('country_key'))),
                            TextInput::make('overnight_stays')
                                ->hiddenLabel()
                                ->integer()
                                ->minValue(0)
                                ->maxValue(fn (Get $get): int => ResidenceCountryCatalog::maximum((string) $get('country_key')))
                                ->rule(fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $arrivals = $get('arrivals');

                                    if ($value !== null && $arrivals !== null && (int) $value < (int) $arrivals) {
                                        $fail(__('tourism-statistics::messages.validation.overnights_below_arrivals'));
                                    }
                                }),
                        ])
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'md' => 4])
                        ->schema([
                            Placeholder::make('totals_label')
                                ->hiddenLabel()
                                ->content(__('tourism-statistics::messages.fields.total'))
                                ->columnSpan(['default' => 1, 'md' => 2]),
                            Placeholder::make('arrivals_total')
                                ->label(__('tourism-statistics::messages.fields.arrivals_total'))
                                ->content(fn (Get $get): string => number_format(self::total($get('countryValues'), 'arrivals'), 0, ',', '.')),
                            Placeholder::make('overnight_stays_total')
                                ->label(__('tourism-statistics::messages.fields.overnight_stays_total'))
                                ->content(fn (Get $get): string => number_format(self::total($get('countryValues'), 'overnight_stays'), 0, ',', '.')),
                        ]),
                ]),
            Section::make(__('tourism-statistics::messages.sections.remarks'))
                ->schema([
                    Textarea::make('remarks')
                        ->label(__('tourism-statistics::messages.fields.remarks'))
                        ->rows(4)
                        ->maxLength(255)
                        ->rule(fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                            if (filled($value) && iconv('UTF-8', 'Windows-1252', (string) $value) === false) {
                                $fail(__('tourism-statistics::messages.validation.windows_1252'));
                            }
                        }),
                ]),
        ]);
    }

    /** @return array<int, string> */
    private static function months(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn (int $month): array => [
            $month => __('tourism-statistics::messages.months.'.$month),
        ])->all();
    }

    /** @return array<int, int> */
    private static function years(): array
    {
        $year = (int) now()->format('Y');

        return collect(range($year + 1, $year - 5))->mapWithKeys(fn (int $value): array => [$value => $value])->all();
    }

    /** @param array<array-key, array<string, mixed>>|null $rows */
    private static function total(?array $rows, string $field): int
    {
        return (int) collect($rows ?? [])->sum(fn (array $row): int => (int) ($row[$field] ?? 0));
    }
}
