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
        "laravel/framework": "4.0.*",
        "mews/captcha": "dev-master"
    },
    "minimum-stability": "dev"
}
```

Update your packages with ```composer update``` or install with ```composer install```.

### [laravel4-powerpack](https://github.com/laravelbook/laravel4-powerpack) pack requires!

## Usage

To use the Captcha Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.

Find the `providers` key in `app/config/app.php` and register the Captcha Service Provider.

```php
    'providers' => array(
        // ...
        "LaravelBook\Laravel4Powerpack\Providers\PowerpackServiceProvider",
        'Mews\Captcha\CaptchaServiceProvider',
    )
```

Find the `aliases` key in `app/config/app.php`.

```php
    'aliases' => array(
        // ...
        'HTML' => 'LaravelBook\Laravel4Powerpack\Facades\HTMLFacade',
        'Form' => 'LaravelBook\Laravel4Powerpack\Facades\FormFacade',
        'Str' => 'LaravelBook\Laravel4Powerpack\Facades\StrFacade',
        'Captcha' => 'Mews\Captcha\Facades\Captcha',
    )
```

## Example

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

        $content = Form::open(URL::to(Request::segment(1)));
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
* [laravel4-powerpack on Github](https://github.com/laravelbook/laravel4-powerpack)
* [License](http://www.opensource.org/licenses/mit-license.php)
* [Laravel website](http://laravel.com)
* [Laravel Turkiye website](http://www.laravel.gen.tr)
* [MeWebStudio website](http://www.mewebstudio.com)
