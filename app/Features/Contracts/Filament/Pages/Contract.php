<?php

namespace App\Features\Contracts\Filament\Pages;

use App\Features\Contracts\Support\ContractPlaceholders;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Contract extends Page
{
    use WithoutPageHeading;

    protected static ?string $title = 'Contract';

    protected static ?string $navigationLabel = 'Contract';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'contract';

    protected string $view = 'features.contracts.contract';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array{content?: string|null, template?: string|null} */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(['default' => 1, 'xl' => 12])
            ->components([
                Actions::make([
                    Action::make('createContract')
                        ->label('Vertrag erstellen')
                        ->icon(Heroicon::Plus)
                        ->action(fn (): array => $this->createContract()),
                ])
                    ->columnSpan(['default' => 1, 'xl' => 2]),
                TextInput::make('template')
                    ->hiddenLabel()
                    ->placeholder('Vorlage')
                    ->required()
                    ->columnSpan(['default' => 1, 'xl' => 10]),
                RichEditor::make('content')
                    ->hiddenLabel()
                    ->placeholder('Vertragstext eingeben …')
                    ->required()
                    ->rule('required')
                    ->minHeight('32rem')
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                View::make('features.contracts.placeholders')
                    ->viewData(['groups' => ContractPlaceholders::groups()])
                    ->columnSpan(['default' => 1, 'xl' => 4]),
            ]);
    }

    /** @return array<string, mixed> */
    public function createContract(): array
    {
        return $this->form->getState();
    }
}
