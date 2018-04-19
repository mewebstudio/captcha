<?php

namespace Heimuya\Captcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Heimuya\Captcha
 */
class Captcha extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor() { return 'captcha'; }

}
