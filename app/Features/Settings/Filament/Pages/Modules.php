<?php

namespace App\Features\Settings\Filament\Pages;

use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\Settings\Services\ModuleSettings;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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

    public function mount(ModuleSettings $settings): void
    {
        $this->form->fill(['modules' => $settings->all()]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(ModuleSettings::class);

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
                    ->schema([]),
                $settings->modules(),
            ));
    }

    public function save(ModuleSettings $settings): void
    {
        /** @var array{modules: array<string, bool>} $state */
        $state = $this->form->getState();
        $settings->save($state['modules']);

        Notification::make()
            ->title(__('settings::messages.modules.saved'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}
