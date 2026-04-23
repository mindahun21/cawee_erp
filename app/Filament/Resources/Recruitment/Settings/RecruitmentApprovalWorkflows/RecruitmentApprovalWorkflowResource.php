<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows;

use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Pages\CreateRecruitmentApprovalWorkflow;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Pages\EditRecruitmentApprovalWorkflow;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Pages\ListRecruitmentApprovalWorkflows;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Schemas\RecruitmentApprovalWorkflowForm;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Tables\RecruitmentApprovalWorkflowsTable;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RecruitmentApprovalWorkflowResource extends Resource
{
    protected static ?string $model = RecruitmentApprovalWorkflow::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RecruitmentApprovalWorkflowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentApprovalWorkflowsTable::configure($table);
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
            'index' => ListRecruitmentApprovalWorkflows::route('/'),
            'create' => CreateRecruitmentApprovalWorkflow::route('/create'),
            'edit' => EditRecruitmentApprovalWorkflow::route('/{record}/edit'),
        ];
    }
}
