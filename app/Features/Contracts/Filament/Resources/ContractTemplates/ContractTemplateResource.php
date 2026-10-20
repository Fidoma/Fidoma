<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates;

use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\CreateContractTemplate;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\EditContractTemplate;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\ListContractTemplates;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Schemas\ContractTemplateForm;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Tables\ContractTemplatesTable;
use App\Features\Contracts\Models\ContractTemplate;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContractTemplateResource extends Resource
{
    protected static ?string $model = ContractTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'contract-templates';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('contracts::messages.template.feature');
    }

    public static function getModelLabel(): string
    {
        return __('contracts::messages.template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contracts::messages.template.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return ContractTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractTemplates::route('/'),
            'create' => CreateContractTemplate::route('/create'),
            'edit' => EditContractTemplate::route('/{record}/edit'),
        ];
    }
}
