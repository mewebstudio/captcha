<?php

if ( ! function_exists('captcha')) {

    function captcha($config = 'default')
    {
        return app('captcha')->create($config);
    }
}

if ( ! function_exists('captcha_src')) {

    function captcha_src($config = 'default')
    {
        return app('captcha')->src($config);
    }
}

if ( ! function_exists('captcha_img')) {

    function captcha_img($config = 'default')
    {
        return app('captcha')->img($config);
    }
}


if ( ! function_exists('captcha_check')) {

    function captcha_check($value)
    {
        return app('captcha')->check($value);
    }
}
