<?php

namespace Mews\Captcha;

use Illuminate\Routing\Router;
use Illuminate\Validation\Factory;
use Intervention\Image\ImageManager;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

/**
 * Class CaptchaServiceProvider
 * @package Mews\Captcha
 */
class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../config/captcha.php' => config_path('captcha.php')
        ], 'config');

        // HTTP routing
        if(!config('captcha.disable')){
            if (strpos($this->app->version(), 'Lumen') !== false) {
                /* @var Router $router */
                $router = $this->app;
                $router->get('captcha[/api/{config}]', 'Mews\Captcha\LumenCaptchaController@getCaptchaApi');
                $router->get('captcha[/{config}]', 'Mews\Captcha\LumenCaptchaController@getCaptcha');
            } else {
                /* @var Router $router */
                $router = $this->app['router'];
                if ((double)$this->app->version() >= 5.2) {
                    $router->get('captcha/api/{config?}', '\Mews\Captcha\CaptchaController@getCaptchaApi')->middleware('web');
                    $router->get('captcha/{config?}', '\Mews\Captcha\CaptchaController@getCaptcha')->middleware('web');
                } else {
                    $router->get('captcha/api/{config?}', '\Mews\Captcha\CaptchaController@getCaptchaApi');
                    $router->get('captcha/{config?}', '\Mews\Captcha\CaptchaController@getCaptcha');
                }
            }
        }

        /* @var Factory $validator */
        $validator = $this->app['validator'];

        // Validator extensions
        $validator->extend('captcha', function ($attribute, $value, $parameters) {
            return config('captcha.disable') || ($value && captcha_check($value));
        });

        // Validator extensions
        $validator->extend('captcha_api', function ($attribute, $value, $parameters) {
            return config('captcha.disable') || ($value && captcha_api_check($value, $parameters[0], $parameters[1] ?? 'default'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge configs
        $this->mergeConfigFrom(
            __DIR__ . '/../config/captcha.php',
            'captcha'
        );

         // Bind the ImageManager with an explicit driver
         if (!$this->app->bound('Intervention\Image\ImageManager')) {
            $this->app->singleton('Intervention\Image\ImageManager', function ($app) {
                // Determine which driver to use, defaulting to 'gd'
                $driver = config('captcha.driver', 'gd') === 'imagick' ? new ImagickDriver() : new GdDriver();

                return new ImageManager($driver);
            });
        }

        // Bind captcha
        $this->app->bind('captcha', function ($app) {
            return new Captcha(
                $app['Illuminate\Filesystem\Filesystem'],
                $app['Illuminate\Contracts\Config\Repository'],
                $app['Intervention\Image\ImageManager'],
                $app['Illuminate\Session\Store'],
                $app['Illuminate\Hashing\BcryptHasher'],
                $app['Illuminate\Support\Str']
            );
        });
    }
}
