<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class FeatureTranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $languageDirectories = glob(app_path('Features/*/lang'), GLOB_ONLYDIR) ?: [];

        foreach ($languageDirectories as $languageDirectory) {
            $feature = basename(dirname($languageDirectory));

            $this->loadTranslationsFrom($languageDirectory, Str::kebab($feature));
        }
    }
}
