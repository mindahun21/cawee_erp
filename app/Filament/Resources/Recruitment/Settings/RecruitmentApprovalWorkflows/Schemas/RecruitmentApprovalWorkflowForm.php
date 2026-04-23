<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;

class RecruitmentApprovalWorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->options(RecruitmentApprovalWorkflow::documentTypes())
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->maxLength(65535),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),

                Repeater::make('stages')
                    ->relationship('stages')
                    ->schema([
                        TextInput::make('stage_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('stage_order')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Select::make('required_role')
                            ->options(RecruitmentApprovalWorkflow::availableRoles())
                            ->searchable()
                            ->required(),
                        Toggle::make('can_reject')
                            ->default(true),
                    ])
                    ->orderColumn('stage_order')
                    ->columns(['default' => 2])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
