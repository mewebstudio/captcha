<?php

namespace Heimuya\Captcha;

/**
 * Laravel 5 Captcha package
 *
 * @copyright Copyright (c) 2015 MeWebStudio
 * @version 2.x
 * @author Muharrem ERİN
 * @contact me@mewebstudio.com
 * @web http://www.mewebstudio.com
 * @date 2015-04-03
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Illuminate\Session\Store as Session;

/**
 * Class Captcha
 * @package Heimuya\Captcha
 */
class Captcha
{

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Hasher
     */
    protected $hasher;

    /**
     * @var Str
     */
    protected $str;

    /**
     * @var ImageManager->canvas
     */
    protected $canvas;

    /**
     * @var ImageManager->image
     */
    protected $image;

    /**
     * @var array
     */
    protected $backgrounds = [];

    /**
     * @var array
     */
    protected $fonts = [];

    /**
     * @var array
     */
    protected $fontColors = [];

    /**
     * @var int
     */
    protected $length = 5;

    /**
     * @var int
     */
    protected $width = 120;

    /**
     * @var int
     */
    protected $height = 36;

    /**
     * @var int
     */
    protected $angle = 15;

    /**
     * @var int
     */
    protected $lines = 3;

    /**
     * @var string
     */
    protected $characters;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var int
     */
    protected $contrast = 0;

    /**
     * @var int
     */
    protected $quality = 90;

    /**
     * @var int
     */
    protected $sharpen = 0;

    /**
     * @var int
     */
    protected $blur = 0;

    /**
     * @var bool
     */
    protected $bgImage = true;

    /**
     * @var string
     */
    protected $bgColor = '#ffffff';

    /**
     * @var bool
     */
    protected $invert = false;

    /**
     * @var bool
     */
    protected $sensitive = false;

    /**
     * @var int
     */
    protected $textLeftPadding = 4;

    /**
     * Constructor
     *
     * @param Filesystem $files
     * @param Repository $config
     * @param ImageManager $imageManager
     * @param Session $session
     * @param Hasher $hasher
     * @param Str $str
     * @throws Exception
     * @internal param Validator $validator
     */
    public function __construct(
        Filesystem $files,
        Repository $config,
        ImageManager $imageManager,
        Session $session,
        Hasher $hasher,
        Str $str
    )
    {
        $this->files = $files;
        $this->config = $config;
        $this->imageManager = $imageManager;
        $this->session = $session;
        $this->hasher = $hasher;
        $this->str = $str;
        $this->characters = config('captcha.characters','2346789abcdefghjmnpqrtuxyzABCDEFGHJMNPQRTUXYZ');
    }

    /**
     * @param string $config
     * @return void
     */
    protected function configure($config)
    {
        if ($this->config->has('captcha.' . $config))
        {
            foreach($this->config->get('captcha.' . $config) as $key => $val)
            {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Create captcha image
     *
     * @param string $config
     * @return ImageManager->response
     */
    public function create($config = 'default')
    {
        $this->backgrounds = $this->files->files(__DIR__ . '/../assets/backgrounds');
        $this->fonts = $this->files->files(__DIR__ . '/../assets/fonts');
        
        if (app()->version() >= 5.5){
            $this->fonts = array_map(function($file) {
                return $file->getPathName();
            }, $this->fonts);
        }
        
        $this->fonts = array_values($this->fonts); //reset fonts array index

        $this->configure($config);

        $this->text = $this->generate();

        $this->canvas = $this->imageManager->canvas(
            $this->width,
            $this->height,
            $this->bgColor
        );

        if ($this->bgImage)
        {
            $this->image = $this->imageManager->make($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->insert($this->image);
        }
        else
        {
            $this->image = $this->canvas;
        }

        if ($this->contrast != 0)
        {
            $this->image->contrast($this->contrast);
        }

        $this->text();

        $this->lines();

        if ($this->sharpen)
        {
            $this->image->sharpen($this->sharpen);
        }
        if ($this->invert)
        {
            $this->image->invert($this->invert);
        }
        if ($this->blur)
        {
            $this->image->blur($this->blur);
        }

        return $this->image;
    }

    /**
     * Image backgrounds
     *
     * @return string
     */
    protected function background()
    {
        return $this->backgrounds[rand(0, count($this->backgrounds) - 1)];
    }

    /**
     * Generate captcha text
     *
     * @return string
     */
    protected function generate()
    {
        $characters = str_split($this->characters);

        $bag = '';
        for($i = 0; $i < $this->length; $i++)
        {
            $bag .= $characters[rand(0, count($characters) - 1)];
        }

        $this->session->put('captcha', [
            'sensitive' => $this->sensitive,
            'key'       => $this->hasher->make($this->sensitive ? $bag : $this->str->lower($bag))
        ]);

        return $bag;
    }

    /**
     * Writing captcha text
     */
    protected function text()
    {
        $marginTop = $this->image->height() / $this->length;

        $i = 0;
        foreach(str_split($this->text) as $char)
        {
            $marginLeft = $this->textLeftPadding +  ($i * ($this->image->width() - $this->textLeftPadding) / $this->length);

            $this->image->text($char, $marginLeft, $marginTop, function($font) {
                $font->file($this->font());
                $font->size($this->fontSize());
                $font->color($this->fontColor());
                $font->align('left');
                $font->valign('top');
                $font->angle($this->angle());
            });

            $i++;
        }
    }

    /**
     * Image fonts
     *
     * @return string
     */
    protected function font()
    {
        return $this->fonts[rand(0, count($this->fonts) - 1)];
    }

    /**
     * Random font size
     *
     * @return integer
     */
    protected function fontSize()
    {
        return rand($this->image->height() - 10, $this->image->height());
    }

    /**
     * Random font color
     *
     * @return array
     */
    protected function fontColor()
    {
        if ( ! empty($this->fontColors))
        {
            $color = $this->fontColors[rand(0, count($this->fontColors) - 1)];
        }
        else
        {
            $color = [rand(0, 255), rand(0, 255), rand(0, 255)];
        }

        return $color;
    }

    /**
     * Angle
     *
     * @return int
     */
    protected function angle()
    {
        return rand((-1 * $this->angle), $this->angle);
    }

    /**
     * Random image lines
     *
     * @return \Intervention\Image\Image
     */
    protected function lines()
    {
        for($i = 0; $i <= $this->lines; $i++)
        {
            $this->image->line(
                rand(0, $this->image->width()) + $i * rand(0, $this->image->height()),
                rand(0, $this->image->height()),
                rand(0, $this->image->width()),
                rand(0, $this->image->height()),
                function ($draw) {
                    $draw->color($this->fontColor());
                }
            );
        }
        return $this->image;
    }

    /**
     * Captcha check
     *
     * @param $value
     * @return bool
     */
    public function check($value)
    {
        if ( ! $this->session->has('captcha'))
        {
            return false;
        }

        $key = $this->session->get('captcha.key');

        if ( ! $this->session->get('captcha.sensitive'))
        {
            $value = $this->str->lower($value);
        }

        $this->session->remove('captcha');

        return $this->hasher->check($value, $key);
    }

    /**
     * Generate captcha image source
     *
     * @param null $config
     * @return string
     */
    public function src($config = null)
    {
        return url('captcha' . ($config ? '/' . $config : '/default')) . '?' . $this->str->random(8);
    }

    /**
     * Generate captcha image html tag
     *
     * @param null $config
     * @param array $attrs HTML attributes supplied to the image tag where key is the attribute
     * and the value is the attribute value
     * @return string
     */
    public function img($config = null, $attrs = [])
    {
        $attrs_str = '';
        foreach($attrs as $attr => $value){
            if ($attr == 'src'){
                //Neglect src attribute
                continue;
            }
            $attrs_str .= $attr.'="'.$value.'" ';
        }
        return '<img src="' . $this->src($config) . '" '. trim($attrs_str).'>';
    }

}
