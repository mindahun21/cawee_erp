<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Tabs::make()->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('General Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required(),
                                Select::make('type')
                                    ->required()
                                    ->options([
                                        'annual' => 'Annual Plan',
                                        'monthly' => 'Monthly Plan',
                                        'weekly' => 'Weekly Plan',
                                        'activity' => 'Activity/Task',
                                    ])
                                    ->default('annual')
                                    ->reactive(),
                                Select::make('parent_id')
                                    ->label('Parent Plan')
                                    ->relationship('parent', 'title', function ($query, $get) {
                                        return $query->when($get('type'), function ($q, $type) {
                                            $order = ['annual', 'monthly', 'weekly', 'activity'];
                                            $currentIndex = array_search($type, $order);
                                            if ($currentIndex > 0) {
                                                return $q->where('type', $order[$currentIndex - 1]);
                                            }
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select parent plan if applicable'),
                                Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('project_id')
                                    ->relationship('project', 'project_name') 
                                    ->searchable()
                                    ->preload(),
                            ]),
                            \Filament\Schemas\Components\Section::make('Schedule')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('start_date')
                                        ->required(),
                                    DatePicker::make('end_date')
                                        ->required()
                                        ->after('start_date'),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Detail & Outcomes')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            Textarea::make('description')
                                ->rows(3),
                            Textarea::make('objectives')
                                ->rows(3),
                            Textarea::make('outcomes')
                                ->rows(3),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Finance & Attachments')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Select::make('budget_id')
                                ->relationship('budget', 'title')
                                ->searchable()
                                ->preload()
                                ->helperText('Link to Finance budget (ProcurementBudget)'),
                            \Filament\Forms\Components\FileUpload::make('attachments')
                                ->multiple()
                                ->disk('public')
                                ->directory('planning-attachments')
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable(),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Execution')
                        ->icon('heroicon-o-bolt')
                        ->schema([
                            \Filament\Forms\Components\Slider::make('progress_percentage')
                                ->label('Progress Percentage')
                                ->step(5)
                                ->default(0)
                        ]),
                ])->columnSpanFull()
            ]);
    }
}
