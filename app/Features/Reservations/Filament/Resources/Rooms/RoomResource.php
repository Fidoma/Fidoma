<?php

namespace App\Features\Reservations\Filament\Resources\Rooms;

use App\Features\Reservations\Filament\Resources\Rooms\Pages\CreateRoom;
use App\Features\Reservations\Filament\Resources\Rooms\Pages\EditRoom;
use App\Features\Reservations\Filament\Resources\Rooms\Pages\ListRooms;
use App\Features\Reservations\Models\Room;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $modelLabel = 'Zimmer';

    protected static ?string $pluralModelLabel = 'Zimmer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Reservierungen';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'room_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zimmer')
                ->columns(2)
                ->schema([
                    TextInput::make('room_number')->label('Zimmernummer')->required()->maxLength(50)->unique(ignoreRecord: true),
                    TextInput::make('room_name')->label('Bezeichnung')->maxLength(100),
                    TextInput::make('room_capacity')->label('Kapazität')->numeric()->minValue(1)->default(1)->required(),
                    Toggle::make('room_active')->label('Aktiv')->default(true),
                ]),
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
            ->toolbarActions([CreateAction::make()->label('Zimmer')->icon(Heroicon::Plus)])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Room $record): bool => ! $record->reservationItems()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }
}
