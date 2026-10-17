<?php

namespace App\Features\Contracts\Filament\Pages;

use App\Features\Contracts\Support\ContractPlaceholders;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Contract extends Page
{
    protected static ?string $title = 'Contract';

    protected static ?string $navigationLabel = 'Contract';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'contract';

    protected string $view = 'features.contracts.contract';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array{content?: string|null} */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                RichEditor::make('content')
                    ->label('Vertragstext')
                    ->placeholder('Vertragstext eingeben …')
                    ->minHeight('32rem')
                    ->columnSpanFull(),
                View::make('features.contracts.placeholders')
                    ->viewData(['groups' => ContractPlaceholders::groups()])
                    ->columnSpanFull(),
            ]);
    }
}
