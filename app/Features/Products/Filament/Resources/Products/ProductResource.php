<?php

namespace App\Features\Products\Filament\Resources\Products;

use App\Features\Products\Filament\Clusters\ProductsCluster;
use App\Features\Products\Filament\Resources\Products\Pages\CreateProduct;
use App\Features\Products\Filament\Resources\Products\Pages\EditProduct;
use App\Features\Products\Filament\Resources\Products\Pages\ListProducts;
use App\Features\Products\Filament\Resources\Products\Schemas\ProductForm;
use App\Features\Products\Filament\Resources\Products\Tables\ProductsTable;
use App\Features\Products\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'Produkt';

    protected static ?string $pluralModelLabel = 'Produkte';

    protected static ?string $navigationLabel = 'Produkte';

    protected static ?string $cluster = ProductsCluster::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
