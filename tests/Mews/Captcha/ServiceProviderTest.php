<?php namespace Mews\Captcha;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $provider = new CaptchaServiceProvider(null);

        $this->assertFalse($provider->isDeferred());
        $this->assertEquals(array('captcha'), $provider->provides());
    }

    public function testRegister()
    {
        $app = $this->getMock('Illuminate\Container\Container', array('bind', 'make'));

        $mocks = array(
            'config' => array('captcha::config' => array(), 'app.key' => ''),
            'path.public' => __DIR__, 'hash' => true, 'session' => true, 'url' => true,
        );
        $app->expects($this->exactly(5))->method('make')->willReturnCallback(function ($abstract) use ($mocks) {
            return isset($mocks[$abstract]) ? $mocks[$abstract] : null;
        });

        $me = $this;
        $path = realpath(__DIR__ . '/../../../public');

        $app->expects($this->once())->method('bind')
            ->with('captcha', $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_CALLABLE), $this->isFalse())
            ->willReturnCallback(function ($abstract, $concrete) use ($app, $me, $path) {
                $object = $concrete($app);
                $me->assertInstanceOf(__NAMESPACE__ . '\\' . ucfirst($abstract), $object);

                $me->assertAttributeSame(true, 'hasher', $object);
                $me->assertAttributeSame(true, 'session', $object);
                $me->assertAttributeSame(true, 'url', $object);
                $me->assertAttributeEquals($path . '/assets', 'assets', $object);
                $me->assertAttributeCount(7, 'fonts', $object);
                $me->assertAttributeCount(15, 'backgrounds', $object);
            });

        /** @noinspection PhpParamsInspection */
        $provider = new CaptchaServiceProvider($app);
        $provider->register();
    }

    public function testBoot()
    {
        $app = $this->getMock('Illuminate\Container\Container', array('make'));

        $files = $this->getMock('Illuminate\Filesystem\Filesystem', array('isDirectory'));
        $config = $this->getMock('Illuminate\Config\Repository', array('package'), array(), '', false);
        $router = $this->getMock('Illuminate\Routing\Router', array('get'), array(), '', false);
        $captcha = $this->getMock(__NAMESPACE__ . '\Captcha', array('create'), array(), '', false);
        $request = $this->getMock('Illuminate\Http\Request', array('input'), array(), '', false);

        /* @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Validation\Factory $validator */
        $validator = $this->getMock('Illuminate\Validation\Factory', null, array($this->getMock('Symfony\Component\Translation\TranslatorInterface')));
        $path = realpath(__DIR__ . '/../../../src');

        $mocks = compact('files', 'config', 'path', 'router', 'captcha', 'request', 'validator');
        $app->expects($this->exactly(10))->method('make')->willReturnCallback(function ($abstract) use ($mocks) {
            return isset($mocks[$abstract]) ? $mocks[$abstract] : null;
        });

        $me = $this;
        $formId = uniqid();

        $files->expects($this->exactly(4))->method('isDirectory')->willReturnCallback('is_dir');
        $config->expects($this->once())->method('package')->with('mews/captcha', $path . '/config', 'captcha');
        $router->expects($this->once())->method('get')->willReturnCallback(function ($pattern, $action) use ($me, $formId) {
            $me->assertEquals('captcha', $pattern);
            $me->assertTrue(is_callable($action));

            $result = $action();
            $me->assertSame($formId, $result);
        });
        $captcha->expects($this->once())->method('create')->with($formId)->willReturnArgument(0);
        $request->expects($this->once())->method('input')->with('id')->willReturn($formId);

        /** @noinspection PhpParamsInspection */
        $provider = new CaptchaServiceProvider($app);
        $provider->boot();

        $object = $validator->make(array(), array());
        $this->assertInstanceOf(__NAMESPACE__ . '\CaptchaValidator', $object);
        $this->assertEmpty($object->getData());
        $this->assertEmpty($object->getRules());
    }
}
