<?php

Route::get(Config::get('captcha::config')['route'], [
    'uses' => function () {
            return Captcha::create();
        },
    'as' => 'captcha'
]);