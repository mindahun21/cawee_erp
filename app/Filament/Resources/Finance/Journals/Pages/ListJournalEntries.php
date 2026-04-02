<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\JournalEntryResource;
use App\Models\Finance\JournalEntry;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    // ── Header Actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Journal Entry')
                ->icon('heroicon-o-plus'),
        ];
    }

    // ── Tab-based filtering by status ─────────────────────────────────

    public function getTabs(): array
    {
        $counts = JournalEntry::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'all' => Tab::make('All')
                ->badge($counts->sum())
                ->badgeColor('gray'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge($counts->get('draft', 0))
                ->badgeColor('gray'),

            'pending_approval' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_approval'))
                ->badge($counts->get('pending_approval', 0))
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge($counts->get('approved', 0))
                ->badgeColor('info'),

            'posted' => Tab::make('Posted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'posted'))
                ->badge($counts->get('posted', 0))
                ->badgeColor('success'),

            'reversed' => Tab::make('Reversed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reversed'))
                ->badge($counts->get('reversed', 0))
                ->badgeColor('danger'),
        ];
    }
}
