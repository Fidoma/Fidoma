<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Schemas;

use App\Features\Contracts\Support\ContractPlaceholders;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ContractTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'xl' => 12])
            ->components([
                TextInput::make('name')
                    ->label('Vorlagentitel')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                Select::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Entwurf', 'active' => 'Aktiv'])
                    ->default('draft')
                    ->required()
                    ->columnSpan(['default' => 1, 'xl' => 4]),
                RichEditor::make('content')
                    ->label('Vertragstext')
                    ->required()
                    ->rule('required')
                    ->minHeight('32rem')
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                Section::make(__('contracts::messages.template.placeholders.heading'))
                    ->key('contract-template-placeholders')
                    ->compact()
                    ->headerActions([
                        Action::make('addPlaceholder')
                            ->label(__('contracts::messages.template.placeholders.add'))
                            ->icon('heroicon-m-plus')
                            ->iconButton()
                            ->modalHeading(__('contracts::messages.template.placeholders.modal_heading'))
                            ->modalSubmitActionLabel(__('contracts::messages.template.placeholders.save'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('contracts::messages.template.placeholders.label'))
                                    ->helperText(__('contracts::messages.template.placeholders.helper'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->action(function (array $data, Get $get, Set $set): void {
                                $label = trim($data['label']);
                                $fieldKey = Str::snake(Str::ascii($label));
                                $customPlaceholders = $get('custom_placeholders') ?? [];
                                $existingKeys = collect($customPlaceholders)->pluck('field_key');
                                $builtInKeys = collect(ContractPlaceholders::groups())
                                    ->flatten(1)
                                    ->keys()
                                    ->map(fn (string $placeholder): string => Str::between($placeholder, ':', '}}'));

                                if (blank($fieldKey) || $existingKeys->contains($fieldKey) || $builtInKeys->contains($fieldKey)) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('contracts::messages.template.placeholders.duplicate'))
                                        ->send();

                                    return;
                                }

                                $customPlaceholders[] = [
                                    'field_key' => $fieldKey,
                                    'label' => $label,
                                ];

                                $set('custom_placeholders', $customPlaceholders);
                            }),
                    ])
                    ->schema([
                        Hidden::make('custom_placeholders')
                            ->default([]),
                        View::make('features.contracts.placeholders')
                            ->statePath('custom_placeholders')
                            ->viewData(['groups' => ContractPlaceholders::groups()]),
                    ])
                    ->columnSpan(['default' => 1, 'xl' => 4]),
            ]);
    }
}
