<?php

namespace App\Filament\Resources\JeopardyBoardResource\Pages;

use App\Filament\Resources\JeopardyBoardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJeopardyBoard extends EditRecord
{
    protected static string $resource = JeopardyBoardResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
