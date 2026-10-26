<?php

namespace App\Features\TourismStatistics\Filament\Resources\TourismReports;

use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\CreateTourismReport;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\EditTourismReport;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\ListTourismReports;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Schemas\TourismReportForm;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Tables\TourismReportsTable;
use App\Features\TourismStatistics\Models\TourismReport;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TourismReportResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = TourismReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Statistiken';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'tourismusstatistik';

    protected static function navigationModule(): string
    {
        return 'tourism_statistics';
    }

    public static function getNavigationLabel(): string
    {
        return __('tourism-statistics::messages.feature');
    }

    public static function getModelLabel(): string
    {
        return __('tourism-statistics::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tourism-statistics::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return TourismReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TourismReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTourismReports::route('/'),
            'create' => CreateTourismReport::route('/create'),
            'edit' => EditTourismReport::route('/{record}/edit'),
        ];
    }
}
