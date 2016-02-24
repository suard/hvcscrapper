<?php namespace Suard\HvcScrapper;

use Illuminate\Support\ServiceProvider;

class HvcScrapperServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->publishes([__DIR__ . '/config/hvcscrapper.php' => config_path('hvcscrapper.php')]);
        // $this->app->make('Suard\HvcScrapper\HvcScrapper');
        
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}