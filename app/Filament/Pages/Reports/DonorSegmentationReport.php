<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Filament\Widgets\Reports\SegmentationChart;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class DonorSegmentationReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected string $view = 'filament.pages.reports.donor-segmentation-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Donor Segmentation';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'segmentBy' => 'category',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('segmentBy')
                    ->label('Segment By')
                    ->options([
                        'category' => 'Donor Category',
                        'type' => 'Donor Type (Individual/Org)',
                        'amount' => 'Giving Volume (Amount)',
                        'frequency' => 'Giving Frequency',
                        'location' => 'Location (Country)',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (string $state) {
                        $this->dispatch('updateSegmentation', segmentBy: $state);
                    }),
            ])
            ->statePath('data');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SegmentationChart::class,
        ];
    }
}
