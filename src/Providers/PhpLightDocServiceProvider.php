<?php

namespace Wfgm5k2d\PhpLightDoc\Providers;

use Illuminate\Support\ServiceProvider;
use Wfgm5k2d\PhpLightDoc\Console\Commands\DocumentationGenerator;

class PhpLightDocServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/php-light-doc.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'php-light-doc');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DocumentationGenerator::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/php-light-doc'),
        ]);
        $this->publishes([
            __DIR__.'/../config/php-light-doc.php' => config_path('php-light-doc.php'),
        ]);
        $this->publishes([
            __DIR__.'/../Helper/ColorMethod.php' => app_path('Helper/vendor/ColorMethod.php'),
        ]);
        $this->publishes([
            __DIR__.'/../Attributes' => app_path('Attributes/vendor'),
        ]);
    }
}
