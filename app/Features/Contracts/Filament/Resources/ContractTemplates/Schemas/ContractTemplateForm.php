<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Schemas;

use App\Features\Contracts\Support\ContractPlaceholders;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ContractTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'xl' => 12])
            ->components([
                TextInput::make('name')
                    ->label('Vorlagentitel')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                Select::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Entwurf', 'active' => 'Aktiv'])
                    ->default('draft')
                    ->required()
                    ->columnSpan(['default' => 1, 'xl' => 4]),
                RichEditor::make('content')
                    ->label('Vertragstext')
                    ->required()
                    ->rule('required')
                    ->minHeight('32rem')
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                View::make('features.contracts.placeholders')
                    ->viewData(['groups' => ContractPlaceholders::groups()])
                    ->columnSpan(['default' => 1, 'xl' => 4]),
            ]);
    }
}
