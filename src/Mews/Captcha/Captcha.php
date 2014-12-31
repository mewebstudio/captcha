<?php namespace Mews\Captcha;

use Config;
use Session;
use Hash;
use URL;
use Str;
use Response;

/**
 *
 * Laravel 4 Captcha package
 * @copyright Copyright (c) 2014 MeWebStudio
 * @version 1.0.1
 * @author Muharrem ERİN
 * @contact me@mewebstudio.com
 * @link http://www.mewebstudio.com
 * @date 2014-01-25
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class Captcha
{
    /**
     * @var  Captcha  singleton instance of the Useragent object
     */
    protected static $singleton;

    /**
     * @var  Captcha config instance of the Captcha::$config object
     */
    protected $config = array();

    private $assets;
    private $fonts = array();
    private $backgrounds = array();

    public static function instance()
    {
        if (!self::$singleton) {
            $instance = new self();
            $instance->config = Config::get('captcha::config');
            $instance->assets = __DIR__ . '/../../../public/assets';
            $instance->fonts = $instance->assets('fonts');
            $instance->backgrounds = $instance->assets('backgrounds');
            self::$singleton = $instance;
        }

        return self::$singleton;
    }

    protected static function generateString($length, $characters = '2346789abcdefghjmnpqrtuxyzABCDEFGHJMNPQRTUXYZ')
    {
        $charLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[mt_rand(0, $charLength - 1)];
        }
        return $randomString;
    }

    /**
     * Generates a captcha image, writing it to the output
     * It is used internally by this bundle when pointing to "/captcha" (see [vendor]\routes.php)
     * Typically, you won't use this function, but use the above img() function instead
     *
     * @access  public
     * @return  Response
     */
    public function create($formId = null)
    {
        switch($this->config['type'])
        {
            case 'num':
                $code = static::generateString($this->config['length'], '1234567890');
                break;
            default:
                $code = static::generateString($this->config['length']);
                break;
        }

        if (!$formId) {
            $formId = hash('sha256', URL::previous());
        }
        Session::put('captchaHash.' . $formId, $this->hashMake($code));

        $bg_image = $this->asset('backgrounds');

        $bg_image_info = getimagesize($bg_image);
        if ($bg_image_info['mime'] == 'image/jpg' || $bg_image_info['mime'] == 'image/jpeg') {
            $old_image = imagecreatefromjpeg($bg_image);
        } elseif ($bg_image_info['mime'] == 'image/gif') {
            $old_image = imagecreatefromgif($bg_image);
        } elseif ($bg_image_info['mime'] == 'image/png') {
            $old_image = imagecreatefrompng($bg_image);
        }

        $new_image = imagecreatetruecolor($this->config['width'], $this->config['height']);
        $bg = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $bg);

        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $this->config['width'], $this->config['height'], $bg_image_info[0], $bg_image_info[1]);

        $bg = imagecolorallocate($new_image, 255, 255, 255);
        $codeLength = $this->config['length'];
        $spaces = (array)$this->config['space'];
        $space = $spaces[array_rand($spaces)];
        for ($i = 0; $i < $codeLength; ++$i) {
            $color_cols = explode(',', $this->asset('colors'));
            $fg = imagecolorallocate($new_image, trim($color_cols[0]), trim($color_cols[1]), trim($color_cols[2]));
            imagettftext($new_image, $this->asset('fontsizes'), mt_rand(-10, 15), 10 + ($i * $space), mt_rand($this->config['height'] - 10, $this->config['height'] - 5), $fg, $this->asset('fonts'), $code[$i]);
        }
        imagealphablending($new_image, false);

        ob_start();
        imagejpeg($new_image, null, $this->config['quality']);
        $content = ob_get_clean();
        imagedestroy($new_image);

        return Response::make($content, 200)
            ->header('cache-control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('pragma', 'no-cache')
            ->header('content-type', 'image/jpeg')
            ->header('content-disposition', 'inline; filename=captcha.jpg');
    }

    protected function hashMake($code)
    {
        $code = $this->config['sensitive'] ? $code : Str::lower($code);
        $key = Config::get('app.key');
        return Hash::make($code . $key);
    }

    protected function hashCheck($code, $hash)
    {
        $code = $this->config['sensitive'] ? $code : Str::lower($code);
        $key = Config::get('app.key');
        return Hash::check($code . $key, $hash);
    }

    /**
     * Fonts
     *
     * @access  public
     * @param   string
     * @return  array
     */
    public function assets($type = null)
    {

        $files = array();

        if ($type == 'fonts') {
            $ext = 'ttf';
        } elseif ($type == 'backgrounds') {
            $ext = 'png';
        }

        if ($type) {
            foreach (glob($this->assets . '/' . $type . '/*.' . $ext) as $filename) {
                $files[] = $filename;
            }
        }

        return $files;

    }

    /**
     * Select asset
     *
     * @access  public
     * @param   string
     * @return  string
     */
    public function asset($type = null)
    {
        $file = null;

        if ($type == 'fonts') {
            $file = $this->fonts[array_rand($this->fonts)];
        }
        if ($type == 'backgrounds') {
            $file = $this->backgrounds[array_rand($this->backgrounds)];
        }
        if ($type == 'fontsizes') {
            $file = $this->config['fontsizes'][array_rand($this->config['fontsizes'])];
        }
        if ($type == 'colors') {
            $file = $this->config['colors'][array_rand($this->config['colors'])];
        }
        return $file;
    }

    /**
     * Checks if the supplied captcha test value matches the stored one
     *
     * @param   string  $value
     * @param   string  $formId
     * @access  public
     * @return  bool
     */
    public function check($value, $formId = null)
    {
        if (!$formId) {
            $formId = hash('sha256', URL::previous());
        }
        $captchaHash = Session::get('captchaHash.' . $formId);

        $result = $value != null
            && $captchaHash != null
            && strlen($value) === $this->config['length'] // must be of the same length right?
            && $this->hashCheck($value, $captchaHash);

        // forget the hash to prevent replay
        Session::forget('captchaHash');
        return $result;
    }

    /**
     * Returns an URL to the captcha image
     * For example, you can use in your view something like
     * <img src="<?php echo Captcha::img(); ?>" alt="" />
     *
     * @access  public
     * @return  string
     */
    public function img($formId = null)
    {
        return URL::to('captcha?' . ($formId ? 'id=' . $formId . '&' : '') . mt_rand(100000, 999999));
    }
}
