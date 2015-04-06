<?php namespace Mews\Captcha;

use Illuminate\Validation\Validator;

class CaptchaValidator extends Validator
{
    public function validateCaptcha($attribute, $value, $parameters)
    {
        return captcha_check($value);
    }
}
