<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
// Agrega las siguientes líneas:
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * El namespace predeterminado para los controladores.
     *
     * @var string
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Configura las rutas de tu aplicación.
     */
    public function boot()
    {
        // Configura el rate limiting para las API: por ejemplo, 100 peticiones por minuto por IP
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        parent::boot();
    }

    /**
     * Define las rutas de la aplicación.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define las rutas para la API.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define las rutas web.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }
}
