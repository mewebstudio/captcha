<?php namespace Mews\Captcha;

use Illuminate\Validation\Validator;

/**
 * @property \Illuminate\Container\Container $container
 */
class CaptchaValidator extends Validator
{
    /**
     * Validate that a captcha has a matching confirmation.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  array $parameters
     * @return bool
     */
    public function validateCaptcha($attribute, $value, $parameters)
    {
        $formId = isset($parameters[0]) ? $parameters[0] : null;
        return $this->container['captcha']->check($value, $formId);
    }
}
