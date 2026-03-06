<?php

namespace App\Filament\Resources\RecruitmentCandidates\Pages;

use App\Filament\Resources\RecruitmentCandidates\RecruitmentCandidateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateRecruitmentCandidate extends CreateRecord
{
    protected static string $resource = RecruitmentCandidateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['contact_password'] = Hash::make('1234');

        return $data;
    }
}
