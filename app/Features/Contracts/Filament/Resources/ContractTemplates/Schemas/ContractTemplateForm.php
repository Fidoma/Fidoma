<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Schemas;

use App\Features\Contracts\Support\ContractPlaceholders;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
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
                    ->label(__('contracts::messages.template.fields.title'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'xl' => 8]),
                Select::make('status')
                    ->label(__('contracts::messages.template.fields.status'))
                    ->options([
                        'draft' => __('contracts::messages.template.statuses.draft'),
                        'active' => __('contracts::messages.template.statuses.active'),
                    ])
                    ->default('draft')
                    ->required()
                    ->columnSpan(['default' => 1, 'xl' => 4]),
                RichEditor::make('content')
                    ->label(__('contracts::messages.template.fields.content'))
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
                                Grid::make(['default' => 1, 'md' => 3])
                                    ->schema([
                                        Select::make('field_type')
                                            ->label(__('contracts::messages.template.placeholders.type'))
                                            ->options([
                                                'text' => __('contracts::messages.template.placeholders.types.text'),
                                                'date' => __('contracts::messages.template.placeholders.types.date'),
                                            ])
                                            ->default('text')
                                            ->native()
                                            ->selectablePlaceholder(false)
                                            ->required()
                                            ->columnSpan(['default' => 1, 'md' => 1]),
                                        TextInput::make('label')
                                            ->label(__('contracts::messages.template.placeholders.label'))
                                            ->helperText(__('contracts::messages.template.placeholders.helper'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(['default' => 1, 'md' => 2]),
                                    ]),
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
                                    'field_type' => $data['field_type'],
                                    'can_delete' => true,
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
