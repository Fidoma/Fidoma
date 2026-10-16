<?php

use App\Providers\AppServiceProvider;
use App\Providers\FeatureTranslationServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    FeatureTranslationServiceProvider::class,
    AdminPanelProvider::class,
];
