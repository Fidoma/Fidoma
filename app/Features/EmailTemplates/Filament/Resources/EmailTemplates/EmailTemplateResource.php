<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages\CreateEmailTemplate;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Schemas\EmailTemplateForm;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Tables\EmailTemplatesTable;
use App\Features\EmailTemplates\Models\EmailTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmailTemplateResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = EmailTemplate::class;

    protected static ?string $modelLabel = 'E-Mail-Vorlage';

    protected static ?string $pluralModelLabel = 'E-Mail-Vorlagen';

    protected static ?string $navigationLabel = 'E-Mail-Vorlagen';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'email_template_title';

    public static function form(Schema $schema): Schema
    {
        return EmailTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'create' => CreateEmailTemplate::route('/create'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
