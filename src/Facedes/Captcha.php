<?php namespace Mews\Captcha\Facedes;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mews\Purifier
 */
class Captcha extends Facade {

    protected static function getFacadeAccessor() { return 'captcha'; }

}
