<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\RelationManagers;

use App\Models\ME\MeCaseNote;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaseNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'caseNotes';

    protected static ?string $title = 'Case Notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('note_type')
                ->options([
                    'general'    => 'General',
                    'follow_up'  => 'Follow-Up',
                    'counseling' => 'Counseling Session',
                    'incident'   => 'Incident Report',
                    'assessment' => 'Assessment',
                    'home_visit' => 'Home Visit',
                ])
                ->required()
                ->default('general'),

            Select::make('project_id')
                ->label('Related Project')
                ->relationship('project', 'name')
                ->searchable()
                ->preload(),

            Select::make('authored_by')
                ->label('Case Worker')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('content')
                ->label('Note')
                ->required()
                ->rows(5),

            DatePicker::make('follow_up_date')
                ->label('Follow-Up Date')
                ->native(false)
                ->minDate(now()),

            Toggle::make('is_confidential')
                ->label('Mark as Confidential'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['author', 'project']))
            ->columns([
                TextColumn::make('note_type')
                    ->badge()
                    ->color(fn (MeCaseNote $record): string => $record->note_type_color),

                TextColumn::make('content')
                    ->label('Note')
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('author.name')
                    ->label('Case Worker')
                    ->placeholder('—'),

                TextColumn::make('follow_up_date')
                    ->label('Follow-Up')
                    ->date()
                    ->placeholder('—')
                    ->color(fn (MeCaseNote $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                IconColumn::make('is_confidential')
                    ->label('Confidential')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }
}
