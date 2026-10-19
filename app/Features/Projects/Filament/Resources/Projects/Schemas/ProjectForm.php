<?php

namespace App\Features\Projects\Filament\Resources\Projects\Schemas;

use App\Features\Clients\Models\Client;
use App\Features\Contracts\Models\Contract;
use App\Features\Projects\Enums\ProjectStatus;
use App\Features\Quotes\Models\Quote;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'xl' => 2])->components([
            Section::make(__('projects::messages.form.project'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('client_id')
                        ->label(__('projects::messages.fields.client'))
                        ->options(fn (): array => Client::query()
                            ->where('client_active', true)
                            ->orderBy('client_name')
                            ->get()
                            ->mapWithKeys(fn (Client $client): array => [
                                $client->getKey() => $client->client_company ?: $client->client_name,
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('quote_id', null);
                            $set('contract_id', null);
                        })
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('project_number')
                        ->label(__('projects::messages.fields.number'))
                        ->placeholder(__('projects::messages.form.number_placeholder'))
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Select::make('project_status')
                        ->label(__('projects::messages.fields.status'))
                        ->options(self::enumOptions(ProjectStatus::cases()))
                        ->default(ProjectStatus::Draft->value)
                        ->required(),
                    Textarea::make('project_description')
                        ->label(__('projects::messages.fields.description'))
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Section::make(__('projects::messages.form.framework'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('project_name')
                        ->label(__('projects::messages.fields.name'))
                        ->maxLength(255),
                    TextInput::make('contract_amount')
                        ->label(__('projects::messages.fields.contract_amount'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('€'),
                    DatePicker::make('project_start_date')
                        ->label(__('projects::messages.fields.start_date')),
                    DatePicker::make('project_end_date')
                        ->label(__('projects::messages.fields.end_date'))
                        ->afterOrEqual('project_start_date'),
                    Select::make('quote_id')
                        ->label(__('projects::messages.fields.quote'))
                        ->options(fn (Get $get): array => Quote::query()
                            ->when($get('client_id'), fn ($query, $clientId) => $query->where('client_id', $clientId))
                            ->orderByDesc('quote_date_created')
                            ->pluck('quote_number', 'quote_id')
                            ->filter()
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('contract_id')
                        ->label(__('projects::messages.fields.contract'))
                        ->options(fn (Get $get): array => Contract::query()
                            ->when($get('client_id'), fn ($query, $clientId) => $query->where('client_id', $clientId))
                            ->orderByDesc('contract_create_date')
                            ->pluck('number', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                ]),
        ]);
    }

    /** @param array<int, ProjectStatus> $cases */
    private static function enumOptions(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn ($case): array => [$case->value => $case->label()])->all();
    }
}
