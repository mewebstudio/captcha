<?php

namespace Mews\Captcha;

/**
 * Laravel 5 & 6 Captcha package
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
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Http\Response;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Intervention\Image\Gd\Font;
use Intervention\Image\Geometry\Factories\LineFactory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Captcha
 *
 * @package Mews\Captcha
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
     * @var Image
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
     * @var int
     */
    protected $lineWidth = 1;

    /**
     * @var string
     */
    protected $characters;

    /**
     * @var array
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
    protected $sensitive = false;

    /**
     * @var bool
     */
    protected $math = false;

    /**
     * @var int
     */
    protected $textLeftPadding = 4;

    /**
     * @var string
     */
    protected $fontsDirectory;

    /**
     * @var int
     */
    protected $expire = 60;

    /**
     * @var bool
     */
    protected $encrypt = true;

    /**
     * @var int
     */
    protected $marginTop = 0;

    /**
     * Constructor
     *
     * @param  Filesystem  $files
     * @param  Repository  $config
     * @param  ImageManager  $imageManager
     * @param  Session  $session
     * @param  Hasher  $hasher
     * @param  Str  $str
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
    ) {
        $this->files          = $files;
        $this->config         = $config;
        $this->imageManager   = $imageManager;
        $this->session        = $session;
        $this->hasher         = $hasher;
        $this->str            = $str;
        $this->characters     = config('captcha.characters', ['1', '2', '3', '4', '6', '7', '8', '9']);
        $this->fontsDirectory = config('captcha.fontsDirectory', dirname(__DIR__).'/assets/fonts');
    }

    /**
     * @param  string  $config
     * @return void
     */
    private function configure($config)
    {
        if ($this->config->has('captcha.'.$config)) {
            foreach ($this->config->get('captcha.'.$config) as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Create captcha image
     *
     * @param  string  $config
     * @param  bool  $api
     * @return array|mixed
     * @throws Exception
     */
    public function create(string $config = 'default', bool $api = false)
    {
        $this->backgrounds = $this->files->files(__DIR__.'/../assets/backgrounds');
        $this->fonts       = $this->files->files($this->fontsDirectory);

        $this->fonts = array_map(function (SplFileInfo $file) {
            return $file->getPathName();
        }, $this->fonts);

        $this->configure($config);

        $generator  = $this->generate();
        $this->text = $generator['value'];

        $this->canvas = $this->imageManager->create($this->width, $this->height)->fill($this->bgColor);

        if ($this->bgImage) {
            $this->image = $this->imageManager->read($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->place($this->image);
        } else {
            $this->image = $this->canvas;
        }

        if ($this->contrast != 0) {
            $this->image->contrast($this->contrast);
        }
        $this->lines();
        $this->text();
        if ($this->sharpen) {
            $this->image->sharpen($this->sharpen);
        }
        if ($this->blur) {
            $this->image->blur($this->blur);
        }

        Cache::put($this->get_cache_key($generator['key']), $generator['value'], $this->expire);

        return $api ? [
            'sensitive' => $generator['sensitive'],
            'key'       => $generator['key'],
            'img'       => $this->image->toJpeg()->toDataUri(),
        ] : new Response($this->image->toJpeg(), 200, [
            'Content-Type'        => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="image.jpg"',
        ]);
    }

    /**
     * Image backgrounds
     *
     * @return string
     */
    private function background(): string
    {
        return $this->backgrounds[rand(0, count($this->backgrounds) - 1)];
    }

    /**
     * Generate captcha text
     *
     * @return array
     * @throws Exception
     */
    private function generate(): array
    {
        if ($this->math) {
            $x     = random_int(10, 30);
            $y     = random_int(1, 9);
            $value = "$x + $y = ";
            $key   = $x + $y;
            $key   .= '';
        } else {
            $characters = is_string($this->characters) ? str_split($this->characters) : $this->characters;
            for ($i = 0; $i < $this->length; $i++) {
                $bag[] = $characters[rand(0, count($characters) - 1)];
            }
            $value = implode('', $bag);
            $key   = $this->sensitive ? strtolower(implode('', $bag)) : implode('', $bag);
        }

        $hash = $this->hasher->make($key);
        if ($this->encrypt) {
            $hash = Crypt::encrypt($hash);
        }

        $this->session->put('captcha', [
            'sensitive' => $this->sensitive,
            'key'       => $hash,
            'encrypt'   => $this->encrypt,
        ]);

        return [
            'value'     => $value,
            'sensitive' => $this->sensitive,
            'key'       => $hash,
        ];
    }

    /**
     * Writing captcha text
     *
     * @return void
     */
    private function text(): void
    {
        $marginTop = $this->image->height() / strlen($this->text);

        if ($this->marginTop !== 0) {
            $marginTop = $this->marginTop;
        }

        foreach (str_split($this->text) as $key => $char) {
            $marginLeft = $this->textLeftPadding + ($key * ($this->image->width() - $this->textLeftPadding) / strlen($this->text));

            $this->image->text($char, $marginLeft, $marginTop, function (FontFactory $font) {
                $font->file($this->font());
                $font->size($this->fontSize());
                $font->color($this->fontColor());
                $font->align('left');
                $font->valign('top');
                $font->angle($this->angle());
            });
        }
    }

    /**
     * Image fonts
     *
     * @return string
     */
    private function font(): string
    {
        return $this->fonts[rand(0, count($this->fonts) - 1)];
    }

    /**
     * Random font size
     *
     * @return int
     */
    private function fontSize(): int
    {
        return rand($this->image->height() - 10, $this->image->height());
    }

    /**
     * Random font color
     *
     * @return string
     */
    private function fontColor(): string
    {
        if (! empty($this->fontColors)) {
            $color = $this->fontColors[rand(0, count($this->fontColors) - 1)];
        } else {
            $color = '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }

        return $color;
    }

    /**
     * Angle
     *
     * @return int
     */
    private function angle(): int
    {
        return rand((-1 * $this->angle), $this->angle);
    }

    /**
     * Random image lines
     *
     * @return Image|ImageManager
     */
    private function lines()
    {
        for ($i = 0; $i <= $this->lines; $i++) {
            $this->image->drawLine(function (LineFactory $line) use ($i) {
                $line->from(rand(0, $this->image->width()) + $i * rand(0, $this->image->height()),
                    rand(0, $this->image->height()));
                $line->to(rand(0, $this->image->width()), rand(0, $this->image->height()));
                $line->color($this->fontColor());
                $line->width($this->lineWidth);
            });
        }

        return $this->image;
    }

    /**
     * Captcha check
     *
     * @param  string  $value
     * @return bool
     */
    public function check(string $value): bool
    {
        if (! $this->session->has('captcha')) {
            return false;
        }

        $key       = $this->session->get('captcha.key');
        $sensitive = $this->session->get('captcha.sensitive');
        $encrypt   = $this->session->get('captcha.encrypt');

        if (! Cache::pull($this->get_cache_key($key))) {
            $this->session->remove('captcha');
            return false;
        }

        if (! $sensitive) {
            $value = $this->str->lower($value);
        }

        if ($encrypt) {
            $key = Crypt::decrypt($key);
        }
        $check = $this->hasher->check($value, $key);
        if ($check) {
            $this->session->remove('captcha');
        }

        return $check;
    }

    /**
     * Returns the md5 short version of the key for cache
     *
     * @param  string  $key
     * @return string
     */
    protected function get_cache_key($key)
    {
        return 'captcha_'.md5($key);
    }

    /**
     * Captcha check
     *
     * @param  string  $value
     * @param  string  $key
     * @param  string  $config
     * @return bool
     */
    public function check_api($value, $key, $config = 'default'): bool
    {
        if (! Cache::pull($this->get_cache_key($key))) {
            return false;
        }

        $this->configure($config);

        if (! $this->sensitive) {
            $value = $this->str->lower($value);
        }
        if ($this->encrypt) {
            $key = Crypt::decrypt($key);
        }
        return $this->hasher->check($value, $key);
    }

    /**
     * Generate captcha image source
     *
     * @param  string  $config
     * @return string
     */
    public function src(string $config = 'default'): string
    {
        return url('captcha/'.$config).'?'.$this->str->random(8);
    }

    /**
     * Generate captcha image html tag
     *
     * @param  string  $config
     * @param  array  $attrs
     * $attrs -> HTML attributes supplied to the image tag where key is the attribute and the value is the attribute value
     * @return string
     */
    public function img(string $config = 'default', array $attrs = []): string
    {
        $attrs_str = '';
        foreach ($attrs as $attr => $value) {
            if ($attr == 'src') {
                continue;
            }

            $attrs_str .= $attr.'="'.$value.'" ';
        }
        return new HtmlString('<img src="'.$this->src($config).'" '.trim($attrs_str).'>');
    }
}
