<?php

namespace App\Filament\Resources\ME\IndicatorResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'targets';

    protected static ?string $title = 'Targets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('target_code_preview')
                    ->label('Target Code')
                    ->dehydrated(false)
                    ->disabled()
                    ->helperText('Auto-generated from Indicator Code + Target Name/Segment + Period.')
                    ->default(fn (): string => $this->generateTargetCodePreview(null, null, null, null)),
                DatePicker::make('period_start')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get): void {
                        $set('target_code_preview', $this->generateTargetCodePreview(
                            (string) ($get('scope_location') ?? null),
                            (string) ($get('scope_project') ?? null),
                            (string) ($get('period_start') ?? null),
                            (string) ($get('period_end') ?? null),
                        ));
                    })
                    ->helperText('Together with period end + project + location, this identifies a unique target row.'),
                DatePicker::make('period_end')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get): void {
                        $set('target_code_preview', $this->generateTargetCodePreview(
                            (string) ($get('scope_location') ?? null),
                            (string) ($get('scope_project') ?? null),
                            (string) ($get('period_start') ?? null),
                            (string) ($get('period_end') ?? null),
                        ));
                    })
                    ->afterOrEqual('period_start'),
                TextInput::make('target_value')
                    ->required()
                    ->numeric()
                    ->minValue(0.01),
                TextInput::make('scope_location')
                    ->label('Location / Segment')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, callable $get): void {
                        $set('target_code_preview', $this->generateTargetCodePreview(
                            (string) ($get('scope_location') ?? null),
                            (string) ($get('scope_project') ?? null),
                            (string) ($get('period_start') ?? null),
                            (string) ($get('period_end') ?? null),
                        ));
                    })
                    ->helperText('Optional split identifier (example: Women, Men, Region A).')
                    ->maxLength(255),
                TextInput::make('scope_project')
                    ->label('Project Code')
                    ->default(fn (): ?string => $this->getOwnerRecord()?->project?->project_code)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, callable $get): void {
                        $set('target_code_preview', $this->generateTargetCodePreview(
                            (string) ($get('scope_location') ?? null),
                            (string) ($get('scope_project') ?? null),
                            (string) ($get('period_start') ?? null),
                            (string) ($get('period_end') ?? null),
                        ));
                    })
                    ->helperText('Use one project code consistently. Duplicate target keys are blocked.')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('target_value')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('target_code')
                    ->label('Target Code')
                    ->state(fn ($record): string => $this->generateTargetCodePreview(
                        (string) ($record->scope_location ?? ''),
                        (string) ($record->scope_project ?? ''),
                        (string) ($record->period_start ?? ''),
                        (string) ($record->period_end ?? ''),
                    ))
                    ->toggleable(),
                TextColumn::make('scope_location')
                    ->label('Location / Segment')
                    ->placeholder('-'),
                TextColumn::make('scope_project')
                    ->label('Project Code')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private function generateTargetCodePreview(
        ?string $scopeLocation,
        ?string $scopeProject,
        ?string $periodStart,
        ?string $periodEnd
    ): string
    {
        $indicatorCode = (string) ($this->getOwnerRecord()?->code ?? 'IND');
        $segment = trim((string) ($scopeLocation ?? 'TOTAL'));
        if ($segment === '') {
            $segment = 'TOTAL';
        }
        $segmentToken = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '_', $segment));
        $segmentToken = trim($segmentToken, '_');
        if ($segmentToken === '') {
            $segmentToken = 'TOTAL';
        }

        $project = trim((string) ($scopeProject ?? ''));
        $projectToken = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '', $project));
        $startToken = strtoupper((string) preg_replace('/[^0-9]+/', '', (string) ($periodStart ?? '')));
        $endToken = strtoupper((string) preg_replace('/[^0-9]+/', '', (string) ($periodEnd ?? '')));

        $parts = [$indicatorCode, $segmentToken];
        if ($projectToken !== '') {
            $parts[] = $projectToken;
        }
        if ($startToken !== '') {
            $parts[] = $startToken;
        }
        if ($endToken !== '') {
            $parts[] = $endToken;
        }

        return substr(implode('_', $parts), 0, 100);
    }
}
