<?php

namespace Laravel\Sail;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class SailServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../runtimes' => base_path('docker'),
        ], 'sail');
    }

    /**
     * Register the console commands for the package.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Artisan::command('sail:install', function () {
            copy(__DIR__.'/../stubs/docker-compose.yml', base_path('docker-compose.yml'));
            copy(__DIR__.'/../stubs/sail', base_path('sail'));

            chmod(base_path('sail'), 0755);

            $this->updateEnvironmentVariables();
        })->purpose('Install Laravel Sail\'s default Docker Compose file');

        Artisan::command('sail:publish', function () {
            $this->call('vendor:publish', ['--tag' => 'sail']);

            file_put_contents(base_path('docker-compose.yml'), str_replace(
                './vendor/laravel/sail/runtimes/8.0',
                './docker/8.0',
                file_get_contents(base_path('docker-compose.yml'))
            ));
        })->purpose('Publish the Laravel Sail Docker files');
    }

    /**
     * Update the application's environment variables to use Sail appropriate values.
     *
     * @return void
     */
    protected function updateEnvironmentVariables()
    {
        $environment = file_get_contents(base_path('.env'));

        $environment = str_replace('DB_HOST=127.0.0.1', 'DB_HOST=mysql', $environment);
        $environment = str_replace('MEMCACHED_HOST=127.0.0.1', 'MEMCACHED_HOST=redis');
        $environment = str_replace('REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis');

        file_put_contents(base_path('.env'), $environment);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'sail.install-command',
            'sail.publish-command',
        ];
    }
}
