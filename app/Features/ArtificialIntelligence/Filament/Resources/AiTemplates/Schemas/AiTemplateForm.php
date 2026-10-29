<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Schemas;

use App\Features\ArtificialIntelligence\Enums\AiTemplateAction;
use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AiTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('artificial-intelligence::messages.form.section'))
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->label(__('artificial-intelligence::messages.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('area')
                            ->label(__('artificial-intelligence::messages.fields.area'))
                            ->options(AiTemplateArea::options())
                            ->default(AiTemplateArea::EmailMessage->value)
                            ->required(),
                        Select::make('action')
                            ->label(__('artificial-intelligence::messages.fields.action'))
                            ->options(AiTemplateAction::options())
                            ->required(),
                        Textarea::make('prompt')
                            ->label(__('artificial-intelligence::messages.fields.prompt'))
                            ->rows(16)
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('artificial-intelligence::messages.fields.active'))
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label(__('artificial-intelligence::messages.fields.sort_order'))
                            ->integer()
                            ->minValue(0),
                    ]),
            ]);
    }
}
