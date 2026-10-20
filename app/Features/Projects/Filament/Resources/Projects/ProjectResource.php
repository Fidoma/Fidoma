<?php

namespace App\Features\Projects\Filament\Resources\Projects;

use App\Features\Projects\Filament\Resources\Projects\Pages\CreateProject;
use App\Features\Projects\Filament\Resources\Projects\Pages\EditProject;
use App\Features\Projects\Filament\Resources\Projects\Pages\ListProjects;
use App\Features\Projects\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Features\Projects\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Features\Projects\Models\Project;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = Project::class;

    protected static function navigationModule(): string
    {
        return 'projects';
    }

    protected static ?string $modelLabel = 'Projekt';

    protected static ?string $pluralModelLabel = 'Projekte';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'projects';

    protected static ?string $recordTitleAttribute = 'project_number';

    public static function getModelLabel(): string
    {
        return __('projects::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
