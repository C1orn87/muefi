<?php

namespace App\Filament\Resources\JeopardyBoardResource\Pages;

use App\Filament\Resources\JeopardyBoardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJeopardyBoard extends CreateRecord
{
    protected static string $resource = JeopardyBoardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
