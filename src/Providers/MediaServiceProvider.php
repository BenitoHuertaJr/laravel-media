<?php

namespace iamx\Media\Providers;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
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
        if(count(glob(base_path("database/migrations/*create_media_table.php"))) == 0)
            $this->publishes([__DIR__.'/../Database/migrations/create_media_table.php' => database_path('migrations') . '/' . date("Y_m_d_Gis") . '_create_media_table.php'], 'migrations');
    }
}
