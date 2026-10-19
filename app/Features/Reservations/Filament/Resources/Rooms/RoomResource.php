<?php

namespace App\Features\Reservations\Filament\Resources\Rooms;

use App\Features\Reservations\Filament\Resources\Rooms\Pages\ListRooms;
use App\Features\Reservations\Models\Room;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $modelLabel = 'Zimmer';

    protected static ?string $pluralModelLabel = 'Zimmer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Reservierungen';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'room_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('room_number')->label('Zimmernummer')->required()->maxLength(50)->unique(ignoreRecord: true),
                TextInput::make('room_name')->label('Bezeichnung')->maxLength(100),
                TextInput::make('room_capacity')->label('Kapazität')->numeric()->minValue(1)->default(1)->required(),
                Toggle::make('room_active')->label('Aktiv')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('room_number')
            ->columns([
                TextColumn::make('room_number')->label('Zimmernummer')->searchable()->sortable(),
                TextColumn::make('room_name')->label('Bezeichnung')->searchable(),
                TextColumn::make('room_capacity')->label('Kapazität')->sortable(),
                IconColumn::make('room_active')->label('Aktiv')->boolean(),
                TextColumn::make('reservation_items_count')->label('Aufenthalte')->counts('reservationItems'),
            ])
            ->filters([
                TernaryFilter::make('room_active')
                    ->label('Aktiv'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Zimmer')
                    ->icon(Heroicon::Plus)
                    ->modalHeading('Zimmer hinzufügen')
                    ->modalSubmitActionLabel('Zimmer erstellen'),
                Action::make('edit')
                    ->modalHeading('Zimmer bearbeiten')
                    ->modalSubmitActionLabel('Änderungen speichern')
                    ->fillForm(fn (Room $record): array => $record->attributesToArray())
                    ->schema(fn (Schema $schema): Schema => self::form($schema))
                    ->action(function (Room $record, array $data): void {
                        $record->update($data);
                        Notification::make()->success()->title('Zimmer wurde gespeichert.')->send();
                    })
                    ->modalFooterActions(fn (Action $action): array => [
                        $action->getModalSubmitAction(),
                        $action->getModalCancelAction(),
                        DeleteAction::make('deleteRoom')
                            ->label('Löschen')
                            ->icon(Heroicon::OutlinedTrash)
                            ->disabled(fn (Room $record): bool => $record->reservationItems()->exists())
                            ->tooltip(fn (Room $record): ?string => $record->reservationItems()->exists()
                                ? 'Zimmer mit Aufenthalten können nicht gelöscht werden.'
                                : null)
                            ->cancelParentActions(),
                    ])
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->extraAttributes(['style' => 'display: none']),
            ])
            ->recordAction('edit')
            ->recordActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
        ];
    }
}
