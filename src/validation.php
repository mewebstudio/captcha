<?php

Validator::extend('captca', function($attribute, $value, $parameters)
{
    return Captcha::check($value);
});