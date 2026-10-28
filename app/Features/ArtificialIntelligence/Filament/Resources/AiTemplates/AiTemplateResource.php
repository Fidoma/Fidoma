<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\CreateAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\EditAiTemplate;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages\ListAiTemplates;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Schemas\AiTemplateForm;
use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Tables\AiTemplatesTable;
use App\Features\ArtificialIntelligence\Models\AiTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiTemplateResource extends Resource
{
    protected static ?string $model = AiTemplate::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('artificial-intelligence::messages.template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('artificial-intelligence::messages.templates');
    }

    public static function getNavigationLabel(): string
    {
        return __('artificial-intelligence::messages.templates');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('artificial-intelligence::messages.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return AiTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiTemplates::route('/'),
            'create' => CreateAiTemplate::route('/create'),
            'edit' => EditAiTemplate::route('/{record}/edit'),
        ];
    }
}
