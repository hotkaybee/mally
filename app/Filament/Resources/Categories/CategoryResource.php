<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Schemas\CategoryInfolist;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    /**
     * Filament uses the record title attribute for two different things: it
     * reads it off the model for display (accessors apply), and it puts it
     * straight into SQL for global search (accessors do not). So it must name
     * a real column — pointed at the active locale's one.
     */
    public static function getRecordTitleAttribute(): ?string
    {
        return Category::nameColumn();
    }

    /**
     * Display goes through the accessor, which falls back across locales, so a
     * half-translated category still shows a name instead of a blank.
     */
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->name ?: static::getModelLabel();
    }

    /**
     * Search every locale's column, so a category is findable by its Russian,
     * Uzbek or English name regardless of the active locale.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return Category::nameColumns();
    }

    public static function getNavigationLabel(): string
    {
        return __('Categories');
    }

    public static function getModelLabel(): string
    {
        return __('Categories');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
