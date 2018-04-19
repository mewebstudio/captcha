<?php

namespace Heimuya\Captcha;

use Illuminate\Routing\Controller;
use Illuminate\Config\Repository;

/**
 * Class CaptchaController
 * @package Heimuya\Captcha
 */
class CaptchaController extends Controller
{

    /**
     * get CAPTCHA
     *
     * @param \Heimuya\Captcha\Captcha $captcha
     * @param string $config
     * @return \Intervention\Image\ImageManager->response
     */
    public function getCaptcha(Captcha $captcha, $config = 'default')
    {
        return $captcha->create($config)->response(config(['captcha.' . $config . '.type' => 'png']), config(['captcha.' . $config . 'quality' => 90]));
    }

}
