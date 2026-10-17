<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Schemas;

use App\Features\Clients\Models\Client;
use App\Features\Contracts\Models\ContractTemplate;
use App\Features\Contracts\Models\ContractTemplateField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Select::make('client_id')
                    ->label('Kunde')
                    ->options(fn (): array => Client::query()->where('client_active', true)->orderBy('client_name')->get()
                        ->mapWithKeys(fn (Client $client): array => [
                            $client->getKey() => $client->client_company ?: $client->client_name,
                        ])->all())
                    ->searchable()->preload()->required(),
                Select::make('template_id')
                    ->label('Vertragsvorlage')
                    ->options(fn (): array => ContractTemplate::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->preload()->live()->required(),
                Section::make('Vertragsdaten')
                    ->visible(fn (Get $get): bool => filled($get('template_id')))
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema(fn (Get $get): array => [
                        TextInput::make('number')
                            ->label('Vertragsnummer')
                            ->placeholder('Wird automatisch vergeben')
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('contract_location')->label('Vertragsort')->required()->maxLength(255),
                        DatePicker::make('contract_create_date')->label('Erstellungsdatum')->default(now()->toDateString())->required(),
                        DatePicker::make('contract_start_date')->label('Vertragsbeginn')->default(now()->toDateString())->required(),
                        DatePicker::make('contract_end_date')->label('Vertragsende')->afterOrEqual('contract_start_date'),
                        ...self::customFieldComponents($get('template_id')),
                    ])->columnSpanFull(),
            ]);
    }

    /** @return array<int, Component> */
    private static function customFieldComponents(mixed $templateId): array
    {
        if (blank($templateId)) {
            return [];
        }

        return ContractTemplateField::query()->where('template_id', $templateId)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (ContractTemplateField $field): Component => self::customFieldComponent($field))->all();
    }

    private static function customFieldComponent(ContractTemplateField $field): Component
    {
        $component = match ($field->field_type) {
            'textarea' => Textarea::make("custom_fields.{$field->field_key}"),
            'date' => DatePicker::make("custom_fields.{$field->field_key}"),
            'boolean' => Toggle::make("custom_fields.{$field->field_key}"),
            'number' => TextInput::make("custom_fields.{$field->field_key}")->numeric(),
            'currency' => TextInput::make("custom_fields.{$field->field_key}")->numeric()->prefix('€'),
            default => TextInput::make("custom_fields.{$field->field_key}"),
        };

        return $component->label($field->label)->default($field->default_value)->required($field->is_required);
    }
}
