<?php

if ( ! function_exists('captcha')) {

    /**
     * @param string $config
     * @return mixed
     */
    function captcha($config = 'default')
    {
        return app('captcha')->create($config);
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


if ( ! function_exists('captcha_data_url')) {
    /**
     * @param $value
     * @return bool
     */
    function captcha_data_url($value)
    {
        return app('captcha')->create($value)->encode('data-url')->encoded;
    }
}
