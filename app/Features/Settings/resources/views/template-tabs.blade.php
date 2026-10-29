<x-filament-widgets::widget>
    <x-filament::tabs
        :label="__('settings::messages.navigation.templates')"
        class="mx-auto w-fit max-w-full"
    >
        @if (\App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource::canViewAny())
            <x-filament::tabs.item
                tag="a"
                :href="\App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource::getUrl()"
                :active="request()->routeIs(\App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource::getRouteBaseName().'.*')"
                icon="heroicon-o-envelope"
            >
                {{ \App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource::getNavigationLabel() }}
            </x-filament::tabs.item>
        @endif

        @if (\App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource::canViewAny())
            <x-filament::tabs.item
                tag="a"
                :href="\App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource::getUrl()"
                :active="request()->routeIs(\App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource::getRouteBaseName().'.*')"
                icon="heroicon-o-document-text"
            >
                {{ \App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource::getNavigationLabel() }}
            </x-filament::tabs.item>
        @endif

        @if (\App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource::canViewAny())
            <x-filament::tabs.item
                tag="a"
                :href="\App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource::getUrl()"
                :active="request()->routeIs(\App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource::getRouteBaseName().'.*')"
                icon="heroicon-o-sparkles"
            >
                {{ \App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource::getNavigationLabel() }}
            </x-filament::tabs.item>
        @endif
    </x-filament::tabs>
</x-filament-widgets::widget>
