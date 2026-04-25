<?php

namespace App\Filament\Clusters\Planning\Resources\Tasks;

use App\Filament\Clusters\Planning;
use App\Filament\Clusters\Planning\Resources\Tasks\Pages\CreateTask;
use App\Filament\Clusters\Planning\Resources\Tasks\Pages\EditTask;
use App\Filament\Clusters\Planning\Resources\Tasks\Pages\ListTasks;
use App\Filament\Clusters\Planning\Resources\Tasks\Pages\ViewTask;
use App\Filament\Clusters\Planning\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Clusters\Planning\Resources\Tasks\Schemas\TaskInfolist;
use App\Filament\Clusters\Planning\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $cluster = Planning::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaskInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
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
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
