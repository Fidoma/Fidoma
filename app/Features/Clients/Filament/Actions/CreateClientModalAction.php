<?php

namespace App\Features\Clients\Filament\Actions;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

final class CreateClientModalAction
{
    public static function make(string $name = 'create', bool $rejectDuplicateEmail = false): CreateAction
    {
        return CreateAction::make($name)
            ->model(Client::class)
            ->label(__('clients::messages.actions.create_client'))
            ->icon(Heroicon::Plus)
            ->modalHeading(__('clients::messages.actions.create_client'))
            ->modalSubmitActionLabel(__('clients::messages.actions.create_client'))
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(fn (Schema $schema): Schema => ClientResource::form($schema))
            ->using(function (array $data) use ($rejectDuplicateEmail): Client {
                $email = trim((string) ($data['client_email'] ?? ''));

                if ($rejectDuplicateEmail && $email !== '' && Client::query()->where('client_email', $email)->exists()) {
                    throw ValidationException::withMessages([
                        'client_email' => __('clients::messages.validation.duplicate_email'),
                    ]);
                }

                return app(CreateClient::class)->handle($data);
            })
            ->createAnother(false)
            ->successNotificationTitle(__('clients::messages.actions.create_client'));
    }
}
