# Captcha for Laravel 4

A simple [Laravel 4](http://four.laravel.com/) service provider for including the [Captcha for Laravel 4](https://github.com/mewebstudio/captcha).

## Preview
![Preview](http://i.imgur.com/kfXYhlk.jpg?1)

## Installation

The Captcha Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the
`mews/captcha` package and setting the `minimum-stability` to `dev` (required for Laravel 4) in your
project's `composer.json`.

```json
{
    "require": {
        "laravel/framework": "4.1.*",
        "mews/captcha": "dev-master-l4"
    },
    "minimum-stability": "dev"
}
```

###Updated Installation

The improvements of mauris's fork over mewebstudio are listed on the [pull request](https://github.com/mewebstudio/captcha/pull/14).

In order to use [mauris's](https://github.com/mauris/captcha) fork, the repository meeds to be added into the `composer.json` in the following manner:

```json
{
    "require": {
        "laravel/framework": "4.1.*",
        "mews/captcha": "1.0.*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mauris/captcha"
        }
    ]
}
```

Update your packages with ```composer update``` or install with ```composer install```.

In Windows, you'll need to include the GD2 DLL `php_gd2.dll` as an extension in php.ini.

## Usage

To use the Captcha Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.

Find the `providers` key in `app/config/app.php` and register the Captcha Service Provider.

```php
    'providers' => array(
        // ...
        'Mews\Captcha\CaptchaServiceProvider',
    )
```

Find the `aliases` key in `app/config/app.php`.

```php
    'aliases' => array(
        // ...
        'Captcha' => 'Mews\Captcha\Facades\Captcha',
    )
```

## Configuration

To use your own settings, publish config.

```$ php artisan config:publish mews/captcha```

## Example Usage

```php

    // [your site path]/app/routes.php

    Route::any('/captcha-test', function()
    {

        if (Request::getMethod() == 'POST')
        {
            $rules =  array('captcha' => array('required', 'captcha'));
            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails())
            {
                echo '<p style="color: #ff0000;">Incorrect!</p>';
            }
            else
            {
                echo '<p style="color: #00ff30;">Matched :)</p>';
            }
        }

        $content = Form::open(array(URL::to(Request::segment(1))));
        $content .= '<p>' . HTML::image(Captcha::img(), 'Captcha image') . '</p>';
        $content .= '<p>' . Form::text('captcha') . '</p>';
        $content .= '<p>' . Form::submit('Check') . '</p>';
        $content .= '<p>' . Form::close() . '</p>';
        return $content;

    });
```

^_^

## Links

* [L4 Captcha on Github](https://github.com/mewebstudio/captcha)
* [L4 Captcha on Packagist](https://packagist.org/packages/mews/captcha)
* [For L3 on Github](https://github.com/mewebstudio/mecaptcha)
* [License](http://www.opensource.org/licenses/mit-license.php)
* [Laravel website](http://laravel.com)
* [Laravel Turkiye website](http://www.laravel.gen.tr)
* [MeWebStudio website](http://www.mewebstudio.com)
