<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals\Schemas;

use App\Features\Terminals\Enums\TerminalConnectionStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PaymentTerminalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('terminals::messages.management.device'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('terminals::messages.fields.name'))->required()->maxLength(100),
                    Toggle::make('is_enabled')->label(__('terminals::messages.fields.enabled'))->default(true),
                    TextInput::make('manufacturer')->label(__('terminals::messages.fields.manufacturer'))->maxLength(100),
                    TextInput::make('model')->label(__('terminals::messages.fields.model'))->maxLength(100),
                ]),
            Section::make(__('terminals::messages.management.connection'))
                ->columns(2)
                ->schema([
                    TextInput::make('host')->label(__('terminals::messages.fields.host'))->required()->maxLength(255),
                    TextInput::make('port')->label(__('terminals::messages.fields.port'))->integer()->minValue(1)->maxValue(65535)->default(20007)->required(),
                    Select::make('protocol')->label(__('terminals::messages.fields.protocol'))->options(['zvt' => 'ZVT'])->default('zvt')->required(),
                    Select::make('connection_status')
                        ->label(__('terminals::messages.fields.connection_status'))
                        ->options(collect(TerminalConnectionStatus::cases())->mapWithKeys(fn (TerminalConnectionStatus $status): array => [$status->value => $status->label()])->all())
                        ->default(fn (): string => config('terminal.mode') === 'simulator'
                            ? TerminalConnectionStatus::Online->value
                            : TerminalConnectionStatus::Unknown->value)
                        ->helperText(__('terminals::messages.fields.connection_status_help'))
                        ->required(),
                    Select::make('payment_method_id')
                        ->label(__('terminals::messages.fields.payment_method'))
                        ->relationship('paymentMethod', 'payment_method_name')
                        ->helperText(__('terminals::messages.fields.payment_method_help'))
                        ->searchable()->preload()->required(),
                ]),
        ]);
    }
}
