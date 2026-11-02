<?php

namespace App\Features\Settings\Filament\Pages;

use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\Settings\Services\ModuleSettings;
use App\Features\Webmail\Services\WebmailArchiveSettings;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Modules extends Page implements HasSchemas
{
    use InteractsWithSchemas, WithoutPageHeading;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'modules';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected string $view = 'settings::modules';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('settings::messages.modules.title');
    }

    public function getTitle(): string
    {
        return __('settings::messages.modules.title');
    }

    public function mount(ModuleSettings $settings, WebmailArchiveSettings $archiveSettings): void
    {
        $this->form->fill([
            'modules' => $settings->all(),
            'webmail_archive' => $archiveSettings->formData(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(ModuleSettings::class);
        $archiveSettings = app(WebmailArchiveSettings::class);

        return $schema
            ->statePath('data')
            ->columns(2)
            ->components(array_map(
                fn (string $module): Section => Section::make(__("settings::messages.modules.items.{$module}"))
                    ->afterHeader(
                        Toggle::make("modules.{$module}")
                            ->label(__("settings::messages.modules.items.{$module}"))
                            ->hiddenLabel()
                            ->default(true),
                    )
                    ->columns($module === 'webmail' ? 2 : 1)
                    ->schema($module === 'webmail' ? [
                        TextInput::make('webmail_archive.sender')
                            ->label(__('webmail::messages.archive.sender'))
                            ->placeholder('*@google.de')
                            ->rules([
                                fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    if (WebmailArchiveSettings::normalizeSenderPattern($value) === null) {
                                        $fail(__('webmail::messages.archive.sender_invalid'));
                                    }
                                },
                            ])
                            ->maxLength(254)
                            ->helperText(__('webmail::messages.archive.sender_help')),
                        TextInput::make('webmail_archive.password')
                            ->label(__('webmail::messages.archive.password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(1024)
                            ->required(fn (Get $get): bool => filled($get('webmail_archive.sender'))
                                && ! $archiveSettings->hasPassword())
                            ->helperText(__('webmail::messages.archive.password_help')),
                    ] : []),
                $settings->modules(),
            ));
    }

    public function save(ModuleSettings $settings, WebmailArchiveSettings $archiveSettings): void
    {
        /** @var array{modules: array<string, bool>, webmail_archive?: array{sender?: mixed, password?: mixed}} $state */
        $state = $this->form->getState();
        $settings->save($state['modules']);
        $archiveSettings->save($state['webmail_archive'] ?? []);

        Notification::make()
            ->title(__('settings::messages.modules.saved'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}
