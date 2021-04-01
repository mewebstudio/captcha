# Captcha for Laravel 5/6/7/8

[![Build Status](https://travis-ci.org/mewebstudio/captcha.svg?branch=master)](https://travis-ci.org/mewebstudio/captcha) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mewebstudio/captcha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mewebstudio/captcha/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/mews/captcha/v/stable.svg)](https://packagist.org/packages/mews/captcha)
[![Latest Unstable Version](https://poser.pugx.org/mews/captcha/v/unstable.svg)](https://packagist.org/packages/mews/captcha)
[![License](https://poser.pugx.org/mews/captcha/license.svg)](https://packagist.org/packages/mews/captcha)
[![Total Downloads](https://poser.pugx.org/mews/captcha/downloads.svg)](https://packagist.org/packages/mews/captcha)

A simple [Laravel 5/6/7/8](http://www.laravel.com/) service provider for including the [Captcha for Laravel](https://github.com/mewebstudio/captcha).

for Laravel 4 [Captcha for Laravel Laravel 4](https://github.com/mewebstudio/captcha/tree/master-l4)

## Preview
![Preview](https://image.ibb.co/kZxMLm/image.png)

- [Captcha for Laravel 5/6/7/8](#captcha-for-laravel-5-6-7-8)
  * [Preview](#preview)
  * [Installation](#installation)
  * [Usage](#usage)
  * [Configuration](#configuration)
  * [Example Usage](#example-usage)
    + [Session Mode:](#session-mode-)
    + [Stateless Mode:](#stateless-mode-)
  * [Fork Updates](#fork-updates)
- [Return Image](#return-image)
- [Return URL](#return-url)
- [Return HTML](#return-html)
- [To use different configurations](#to-use-different-configurations)
  * [Links](#links)
  
## Installation

The Captcha Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the
`mews/captcha` package and setting the `minimum-stability` to `dev` (required for Laravel 5) in your
project's `composer.json`.

```json
{
    "require": {
        "laravel/framework": "5.0.*",
        "mews/captcha": "~2.0"
    },
    "minimum-stability": "dev"
}
```

or

Require this package with composer:
```
composer require mews/captcha
```

Update your packages with ```composer update``` or install with ```composer install```.

In Windows, you'll need to include the GD2 DLL `php_gd2.dll` in php.ini. And you also need include `php_fileinfo.dll` and `php_mbstring.dll` to fit the requirements of `mews/captcha`'s dependencies.




## Usage

To use the Captcha Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.

Find the `providers` key in `config/app.php` and register the Captcha Service Provider.

```php
    'providers' => [
        // ...
        'Mews\Captcha\CaptchaServiceProvider',
    ]
```
for Laravel 5.1+
```php
    'providers' => [
        // ...
        Mews\Captcha\CaptchaServiceProvider::class,
    ]
```

Find the `aliases` key in `config/app.php`.

```php
    'aliases' => [
        // ...
        'Captcha' => 'Mews\Captcha\Facades\Captcha',
    ]
```
for Laravel 5.1+
```php
    'aliases' => [
        // ...
        'Captcha' => Mews\Captcha\Facades\Captcha::class,
    ]
```

## Configuration

To use your own settings, publish config.

```$ php artisan vendor:publish```

`config/captcha.php`

```php
return [
    'default'   => [
        'length'    => 5,
        'width'     => 120,
        'height'    => 36,
        'quality'   => 90,
        'math'      => true,  //Enable Math Captcha
        'expire'    => 60,    //Stateless/API captcha expiration
    ],
    // ...
];
```

## Example Usage
### Session Mode:
```php

    // [your site path]/Http/routes.php
    Route::any('captcha-test', function() {
        if (request()->getMethod() == 'POST') {
            $rules = ['captcha' => 'required|captcha'];
            $validator = validator()->make(request()->all(), $rules);
            if ($validator->fails()) {
                echo '<p style="color: #ff0000;">Incorrect!</p>';
            } else {
                echo '<p style="color: #00ff30;">Matched :)</p>';
            }
        }
    
        $form = '<form method="post" action="captcha-test">';
        $form .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
        $form .= '<p>' . captcha_img() . '</p>';
        $form .= '<p><input type="text" name="captcha"></p>';
        $form .= '<p><button type="submit" name="check">Check</button></p>';
        $form .= '</form>';
        return $form;
    });
```
### Stateless Mode:
You get key and img from this url
`http://localhost/captcha/api/math`
and verify the captcha using this method:
```php
    //key is the one that you got from json response
    // fix validator
    // $rules = ['captcha' => 'required|captcha_api:'. request('key')];
    $rules = ['captcha' => 'required|captcha_api:'. request('key') . ',default'];
    $validator = validator()->make(request()->all(), $rules);
    if ($validator->fails()) {
        return response()->json([
            'message' => 'invalid captcha',
        ]);

    } else {
        //do the job
    }
```
## Fork Updates
These updates were made for Session Mode. It was designed for a mixture of AJAX contact forms and data entry forms that validates information on POST with HTTP request. The AJAX forms do not need captcha to be retained for validation, but any form that validates with POST and HTTP request does need a way to track when successful captcha has been entered. We use this method:

```php
    /**
     * Has captcha passed validation or failed?
     * @param  object $errors  message bag of errors returned
     * @param  string $captcha captcha value entered by user
     * @return string          return correct captcha value to resubmit or empty string if failed
     */
    public static function checkCaptcha($errors, $captcha)
    {
        $errorCap = $errors->first('captcha');
        if (substr_count($errorCap, 'security code') == 1) { $retCaptcha = '';
            session(['captcha.valid' => 'no']); }
        else { $retCaptcha = $captcha;  
            session(['captcha.valid' => 'yes']); }

        return $retCaptcha;
    }
```
Since `$errors` are only available in Laravel at the blade level, we use the code below in blade prior to displaying the form. We have the words `security code` in a custom message for errors on the captcha field, so that is what we search for (above). In the blade for data POST forms we determine what the content of captcha field should be (empty if not valid) before displaying the form:

```php
if ((isset($captcha)) && (count($errors) > 0)) { 
  $captcha = JForms::checkCaptcha($errors, $captcha); }
```
We determine whether the session `['captcha.valid']` should be 'yes' or 'no'. After data POST forms are processed and stored we run one last command in form controller to remove valid = yes:

```Session::forget('captcha');```

In the event someone starts the POST form, enters valid captcha, then abandons form, we also place the session forget code (shown above) in the AJAX form controllers before displaying form.

### Untested Option
Instead of posting to session whether captcha is valid, we have not tested the possibility of using the session flash method in Laravel which only persists for the current and one additional HTTP request:

```Session::flash('captcha.valid', 'yes');```

# Return Image
```php
captcha();
```
or
```php
Captcha::create();
```


# Return URL
```php
captcha_src();
```
or
```
Captcha::src('default');
```

# Return HTML
```php
captcha_img();
```
or
```php
Captcha::img();
```

# To use different configurations
```php
captcha_img('flat');

Captcha::img('inverse');
```
etc.

Based on [Intervention Image](https://github.com/Intervention/image)

^_^

## Links
* [Intervention Image](https://github.com/Intervention/image)
* [L5 Captcha on Github](https://github.com/mewebstudio/captcha)
* [L5 Captcha on Packagist](https://packagist.org/packages/mews/captcha)
* [For L4 on Github](https://github.com/mewebstudio/captcha/tree/master-l4)
* [License](http://www.opensource.org/licenses/mit-license.php)
* [Laravel website](http://laravel.com)
* [Laravel Turkiye website](http://www.laravel.gen.tr)
* [MeWebStudio website](http://www.mewebstudio.com)
