<?php

namespace Mews\Captcha;

use Exception;
use Illuminate\Routing\Controller;

/**
 * Class CaptchaController
 * @package Mews\Captcha
 */
class CaptchaController extends Controller
{
    /**
     * get CAPTCHA
     *
     * @param Captcha $captcha
     * @param string $config
     * @return array|mixed
     * @throws Exception
     */
    public function getCaptcha(Captcha $captcha, string $config = 'default')
    {
        if (ob_get_contents()) {
            ob_clean();
        }

        return $captcha->create($config);
    }

    /**
     * get CAPTCHA api
     *
     * @param Captcha $captcha
     * @param string $config
     * @return array|mixed
     * @throws Exception
     */
    public function getCaptchaApi(Captcha $captcha, string $config = 'default')
    {
        return $captcha->create($config, true);
    }
}
