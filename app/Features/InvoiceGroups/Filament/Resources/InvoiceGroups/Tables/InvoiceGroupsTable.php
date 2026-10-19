<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Tables;

use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\InvoiceGroups\Services\InvoiceGroupNumberFormatter;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => InvoiceGroupResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('invoice_group_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_group_identifier_format')
                    ->label('Nummernformat')
                    ->searchable(),
                TextColumn::make('next_number_preview')
                    ->label('Nächste Belegnummer')
                    ->state(fn (InvoiceGroup $record): string => app(InvoiceGroupNumberFormatter::class)->format(
                        $record->invoice_group_identifier_format,
                        $record->invoice_group_next_id,
                        $record->invoice_group_left_pad,
                    ))
                    ->copyable(),
                TextColumn::make('invoice_group_next_id')
                    ->label('Laufende Nummer')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('invoice_group_left_pad')
                    ->label('Mindeststellen')
                    ->numeric(),
            ])
            ->filters([
                Filter::make('invoice_group_name')
                    ->label('Name')
                    ->schema([TextInput::make('value')->label('Name')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('invoice_group_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->defaultSort('invoice_group_name')
            ->toolbarActions([
                CreateAction::make()
                    ->label('Nummernkreis')
                    ->icon(Heroicon::Plus)
                    ->url(InvoiceGroupResource::getUrl('create')),
            ]);
    }
}
