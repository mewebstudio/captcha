<?php namespace Mews\Captcha;

class FacadeTest extends \PHPUnit_Framework_TestCase
{
    public function testFacadeAccessor()
    {
        $captcha = get_class($this);

        Facades\Captcha::clearResolvedInstances();
        /* @noinspection PhpParamsInspection */
        Facades\Captcha::setFacadeApplication(compact('captcha'));

        $this->assertEquals($captcha, Facades\Captcha::getFacadeRoot());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        Facades\Captcha::setFacadeApplication(null);
        Facades\Captcha::clearResolvedInstances();
    }
}
