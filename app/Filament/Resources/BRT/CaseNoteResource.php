<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\CaseNoteResource\Pages\CreateCaseNote;
use App\Filament\Resources\BRT\CaseNoteResource\Pages\EditCaseNote;
use App\Filament\Resources\BRT\CaseNoteResource\Pages\ListCaseNotes;
use App\Filament\Resources\BRT\CaseNoteResource\Pages\ViewCaseNote;
use App\Models\ME\MeCaseNote;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CaseNoteResource extends Resource
{
    protected static ?string $model = MeCaseNote::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Case Notes';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Note Details')
                ->columns(2)
                ->schema([
                    Select::make('beneficiary_id')
                        ->label('Beneficiary')
                        ->relationship('beneficiary', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required(),

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

                    DatePicker::make('follow_up_date')
                        ->label('Follow-Up Date')
                        ->native(false)
                        ->minDate(now()),

                    Toggle::make('is_confidential')
                        ->label('Mark as Confidential'),
                ]),

            Section::make('Content')
                ->schema([
                    Textarea::make('content')
                        ->label('Note Content')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.beneficiary_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('beneficiary.full_name')
                    ->label('Beneficiary')
                    ->searchable(),

                TextColumn::make('note_type')
                    ->badge()
                    ->color(fn (MeCaseNote $record): string => $record->note_type_color),

                TextColumn::make('content')
                    ->label('Current Note')
                    ->limit(60)
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
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('note_type')
                    ->options([
                        'general'    => 'General',
                        'follow_up'  => 'Follow-Up',
                        'counseling' => 'Counseling Session',
                        'incident'   => 'Incident Report',
                        'assessment' => 'Assessment',
                        'home_visit' => 'Home Visit',
                    ]),
                TernaryFilter::make('is_confidential'),
                TernaryFilter::make('overdue_follow_up')
                    ->label('Overdue Follow-Up')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('follow_up_date')->whereDate('follow_up_date', '<', now()),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('follow_up_date')->orWhereDate('follow_up_date', '>=', now())),
                        blank: fn ($query) => $query
                    ),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCaseNotes::route('/'),
            'create' => CreateCaseNote::route('/create'),
            'view'   => ViewCaseNote::route('/{record}'),
            'edit'   => EditCaseNote::route('/{record}/edit'),
        ];
    }
}
