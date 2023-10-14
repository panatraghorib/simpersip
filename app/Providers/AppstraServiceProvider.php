<?php

namespace App\Providers;

use App\Facades\Appstra as AppstraFacade;
use Illuminate\Foundation\AliasLoader;
use App;
use App\Appstra;
use Illuminate\Support\ServiceProvider;

class AppstraServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Appstra', AppstraFacade::class);
        App::bind('appstra', function() {
            return new Appstra();
        });
    }
}
