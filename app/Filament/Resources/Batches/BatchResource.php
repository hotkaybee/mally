<?php

namespace App\Filament\Resources\Batches;

use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\Pages\ViewBatch;
use App\Filament\Resources\Batches\Schemas\BatchForm;
use App\Filament\Resources\Batches\Schemas\BatchInfolist;
use App\Filament\Resources\Batches\Tables\BatchesTable;
use App\Models\ProductBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationLabel(): string
    {
        return __('Batches');
    }

    public static function getModelLabel(): string
    {
        return __('Batch');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Batches');
    }

    public static function form(Schema $schema): Schema
    {
        return BatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatchesTable::configure($table);
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
            'index' => ListBatches::route('/'),
            'create' => CreateBatch::route('/create'),
            'view' => ViewBatch::route('/{record}'),
            'edit' => EditBatch::route('/{record}/edit'),
        ];
    }
}
