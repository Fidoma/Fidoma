<?php

namespace App\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;
use Filament\Support\Icons\Heroicon;

abstract class CreateRecord extends BaseCreateRecord
{
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->icon(Heroicon::ArrowPath);
    }
}
