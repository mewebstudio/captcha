<?php

if ( ! function_exists('captcha')) {

    /**
     * @param null   $text
     * @param string $config
     * @return mixed
     */
    function captcha($text = null, $config = 'default')
    {
        return app('captcha')->create($text, $config);
    }
}

if ( ! function_exists('captcha_src')) {
    /**
     * @param string $config
     * @return string
     */
    function captcha_src($config = 'default')
    {
        return app('captcha')->src($config);
    }
}

if ( ! function_exists('captcha_img')) {

    /**
     * @param string $config
     * @return mixed
     */
    function captcha_img($config = 'default')
    {
        return app('captcha')->img($config);
    }
}


if ( ! function_exists('captcha_check')) {
    /**
     * @param $value
     * @return bool
     */
    function captcha_check($value)
    {
        return app('captcha')->check($value);
    }
}
