<?php

namespace Notabenedev\ProductExportYml;

use Illuminate\Support\ServiceProvider;
use Notabenedev\ProductExportYml\Console\Commands\ProductExportYmlMakeCommand;

class ProductExportYmlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/product-export-yml.php', 'product-export-yml'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Console.
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProductExportYmlMakeCommand::class,
            ]);
        }

        // Публикация конфигурации
        $this->publishes([
            __DIR__.'/config/product-export-yml.php' => config_path('product-export-yml.php')
        ], 'config');


        //Подключаем роуты
        if (config("product-export-yml.siteRoutes")) {
            $this->loadRoutesFrom(__DIR__."/routes/site/product-export-yml.php");
        }

        // Подключение шаблонов.
       // $this->loadViewsFrom(__DIR__ . '/resources/views', 'product-export-yml');
    }

}
