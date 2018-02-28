<?php namespace Mews\Captcha;

use Illuminate\Support\ServiceProvider;

/**
 * @property \Illuminate\Container\Container $app
 */
class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('mews/captcha');

        $app = $this->app;
        $this->app['router']->get('captcha', function () use ($app) {
            return $app['captcha']->create($app['request']->input('id'));
        });

        $this->app['validator']->resolver(function ($translator, $data, $rules, $messages) {
            return new CaptchaValidator($translator, $data, $rules, $messages);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app['captcha'] = $this->app->share(function ($app) {
            $config = $app['config'];
            $publishPath = $app['path.public'] . '/packages/mews/captcha';

            return new Captcha($config['captcha::config'], $config['app.key'], $publishPath, $app['hash'], $app['session'], $app['url']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return array('captcha');
    }
}
