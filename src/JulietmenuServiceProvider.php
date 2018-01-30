<?php

namespace Compasspointmedia\Julietmenu;

use Illuminate\Support\ServiceProvider;

class JulietmenuServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // loading the routes
        // require __DIR__ . "/Http/routes.php";
        // $configPath = __DIR__ . '/config/trickster.php';
        // $this->publishes([$configPath => config_path('trickster.php')]);
        // $this->mergeConfigFrom($configPath, 'trickster');

        // Registers Commands
        $this->commands('command.julietmenu.migration');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Compasspointmedia\Julietmenu\Julietmenu');
        
        $this->registerCommands();
        
        $this->bindFacade();

    }

    private function bindFacade() {
        $this->app->bind('julietmenu', function($app) {
            return new Julietmenu();
        });
    }


    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->singleton('command.julietmenu.migration', function ($app) {
            return new MigrationCommand();
        });
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.julietmenu.migration'
        ];
    }
}