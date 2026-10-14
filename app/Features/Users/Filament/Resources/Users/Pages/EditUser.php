<?php

namespace App\Features\Users\Filament\Resources\Users\Pages;

use App\Features\Users\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => auth()->id() === $this->record->getKey())
                ->tooltip(fn (): ?string => auth()->id() === $this->record->getKey()
                    ? 'Das eigene Benutzerkonto kann nicht gelöscht werden.'
                    : null),
        ];
    }
}
