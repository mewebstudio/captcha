<?php namespace Mews\Captcha;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $rule
     * @param string $formId
     * @param bool $passed
     * @param string $errorMessage
     * @dataProvider validatorDataProvider
     */
    public function testValidate($rule = 'captcha', $formId = null, $passed = true, $errorMessage = '[]')
    {
        /* @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        /* @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Container\Container $container */
        $container = $this->getMock('Illuminate\Container\Container', array('make'));

        $validator = new CaptchaValidator($translator, array('cf_captcha' => 'foo'), array('cf_captcha' => $rule));
        $validator->setContainer($container);

        $captcha = $this->getMock(__NAMESPACE__ . '\Captcha', array('check'), array(), '', false);
        $captcha->expects($this->once())->method('check')->with('foo', $formId)->willReturn($passed);

        $container->expects($this->once())->method('make')->with('captcha')->willReturn($captcha);
        if (!$passed) {
            $translator->expects($this->atLeastOnce())->method('trans')->willReturnArgument(0);
        }

        $this->assertSame($passed, $validator->passes());
        $this->assertEquals($errorMessage, (string)$validator->getMessageBag());
    }

    public static function validatorDataProvider()
    {
        return array(
            array('captcha:bar,NULL', 'bar'),
            array('captcha', null, false, '{"cf_captcha":["validation.captcha"]}'),
        );
    }
}
