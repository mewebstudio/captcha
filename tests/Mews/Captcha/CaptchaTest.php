<?php namespace Mews\Captcha;

class CaptchaTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $config = require __DIR__ . '/../../../src/config/config.php';

        $path = realpath(__DIR__ . '/../../../public');
        $captcha = new Captcha($config, time(), $path, true, true, true);

        $this->assertAttributeEquals($path . '/assets', 'assets', $captcha);
        $this->assertAttributeNotEmpty('fonts', $captcha);
        $this->assertAttributeNotEmpty('backgrounds', $captcha);

        $this->assertCount(0, $captcha->assets());
        $this->assertNull($captcha->asset());

        return $captcha;
    }

    static function writeObjectAttribute($object, array $values)
    {
        foreach ($values as $name => $value) {
            $attribute = new \ReflectionProperty($object, $name);
            $attribute->setAccessible(true);
            $attribute->setValue($object, $value);
        }
    }

    /**
     * @param Captcha $captcha
     * @depends testConstructor
     */
    public function testImgUrl($captcha)
    {
        $url = $this->getMock('Illuminate\Routing\UrlGenerator', array('to'), array(), '', false);
        $this->writeObjectAttribute($captcha, compact('url'));

        $url->expects($this->exactly(2))->method('to')->willReturnArgument(0);

        $this->assertRegExp('/^captcha\?\d{6}$/', $captcha->img());
        $this->assertRegExp('/^captcha\?id=' . ($id = uniqid()) . '&\d{6}$/', $captcha->img($id));
    }

    /**
     * @param Captcha $captcha
     * @depends testConstructor
     */
    public function testCheck($captcha)
    {
        $hasher = $this->getMock('Illuminate\Hashing\HasherInterface');
        $session = $this->getMock('Illuminate\Session\Store', array('get', 'forget'), array(), '', false);
        $url = $this->getMock('Illuminate\Routing\UrlGenerator', array('previous'), array(), '', false);
        $this->writeObjectAttribute($captcha, compact('session', 'hasher', 'url'));

        $hash = hash('sha256', '/');
        $hashKey = $this->getObjectAttribute($captcha, 'hashKey');

        $url->expects($this->once())->method('previous')->willReturn('/');
        $session->expects($this->once())->method('get')->with('captchaHash.' . $hash)->willReturn($hash);
        $hasher->expects($this->once())->method('check')->with('123abc' . $hashKey, $hash)->willReturn(true);
        $session->expects($this->once())->method('forget')->with('captchaHash');

        $result = $captcha->check('123Abc');
        $this->assertTrue($result);
    }

    /**
     * @param Captcha $captcha
     * @depends testConstructor
     * @requires extension gd
     */
    public function testCreate($captcha)
    {
        $hasher = $this->getMock('Illuminate\Hashing\HasherInterface');
        $session = $this->getMock('Illuminate\Session\Store', array('put'), array(), '', false);
        $url = $this->getMock('Illuminate\Routing\UrlGenerator', array('previous'), array(), '', false);
        $this->writeObjectAttribute($captcha, compact('session', 'hasher', 'url'));

        $hash = hash('sha256', '/');
        $hashKey = $this->getObjectAttribute($captcha, 'hashKey');

        $url->expects($this->once())->method('previous')->willReturn('/');
        $hasher->expects($this->once())->method('make')->with($this->matchesRegularExpression('/^[0-9A-Za-z]{6}' . $hashKey . '$/'))->willReturn($hash);
        $session->expects($this->once())->method('put')->with('captchaHash.' . $hash, $hash);

        $response = $captcha->create();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertAttributeSame(false, 'streamed', $response);

        $date = with(new \DateTime(null, new \DateTimeZone('UTC')))->format('D, d M Y H:i:s');
        $headers =
            "Cache-Control:       max-age=0, must-revalidate, no-cache, no-store, private\r\n" .
            "Content-Disposition: inline; filename=captcha.jpg\r\n" .
            "Content-Type:        image/jpeg\r\n" .
            "Date:                $date GMT\r\n" .
            "Pragma:              no-cache\r\n";
        $this->assertEquals($headers, (string)$response->headers);

        $this->setOutputCallback(function ($content) {
            $file = tempnam(sys_get_temp_dir(), 'CAP');
            @file_put_contents($file, $content);
            return $content;
        });
        $this->expectOutputRegex('/^\xFF\xD8\xFF\xE0.{2}\x4A\x46\x49\x46\0/');
        $response->sendContent();

        $this->assertAttributeSame(true, 'streamed', $response);
        $this->assertNull($response->sendContent());
    }

    /**
     * @param Captcha $captcha
     * @depends testConstructor
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Foo!
     */
    public function testCreateNumeric($captcha)
    {
        $config = $this->getObjectAttribute($captcha, 'config');
        $config['type'] = 'num';

        $hasher = $this->getMock('Illuminate\Hashing\HasherInterface');
        $this->writeObjectAttribute($captcha, compact('config', 'hasher'));

        $hashKey = $this->getObjectAttribute($captcha, 'hashKey');
        $hasher->expects($this->once())->method('make')->with($this->matchesRegularExpression('/^\d{6}' . $hashKey . '$/'))
            ->willThrowException(new \InvalidArgumentException('Foo!'));

        $captcha->create('!');
    }
}
