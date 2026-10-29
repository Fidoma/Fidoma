<?php

namespace App\Features\Settings\Filament\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Templates extends Page
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'templates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected string $view = 'settings::templates';

    public static function getNavigationLabel(): string
    {
        return __('settings::messages.navigation.templates');
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            parent::getNavigationItemActiveRoutePattern(),
            EmailTemplateResource::getRouteBaseName().'.*',
            ContractTemplateResource::getRouteBaseName().'.*',
            AiTemplateResource::getRouteBaseName().'.*',
        ];
    }

    public static function canAccess(): bool
    {
        return EmailTemplateResource::canViewAny()
            || ContractTemplateResource::canViewAny()
            || AiTemplateResource::canViewAny();
    }

    public function mount(): void
    {
        foreach ([EmailTemplateResource::class, ContractTemplateResource::class, AiTemplateResource::class] as $resource) {
            if ($resource::canViewAny()) {
                redirect($resource::getUrl());

                return;
            }
        }
    }
}
