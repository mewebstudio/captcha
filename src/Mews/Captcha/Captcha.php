<?php namespace Mews\Captcha;

use Config;
use Session;
use Hash;
use URL;

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

    protected static function generateString($length)
    {
        $characters = '23456789abcdefghjmnpqrstuvwxyzABCDEFGHJMNPQRSTUVWXYZ';
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
     * @return  img
     */
    public function create($id = null)
    {

        $code= static::generateString($this->config['length']);

        Session::put('captchaHash', Hash::make($this->config['sensitive'] === true ? $code : Str::lower($code)));

        $bg_image = static::asset('backgrounds');

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
        for ($i = 0; $i < $codeLength; ++$i) {
            $color_cols = explode(',', $this->asset('colors'));
            $fg = imagecolorallocate($new_image, trim($color_cols[0]), trim($color_cols[1]), trim($color_cols[2]));
            imagettftext($new_image, $this->asset('fontsizes'), rand(-10, 15), 10 + ($i * $this->config['space']), rand($this->config['height'] - 10, $this->config['height'] - 5), $fg, $this->asset('fonts'), $code[$i]);
        }
        imagealphablending($new_image, false);

        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: image/jpg');
        header('Content-Disposition: inline; filename=captcha.jpg');
        imagejpeg($new_image, null, $this->config['quality']);
        imagedestroy($new_image);
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
            $file = $this->fonts[rand(0, count($this->fonts) - 1)];
        }
        if ($type == 'backgrounds') {
            $file = $this->backgrounds[rand(0, count($this->backgrounds) - 1)];
        }
        if ($type == 'fontsizes') {
            $file = $this->config['fontsizes'][rand(0, count($this->config['fontsizes']) - 1)];
        }
        if ($type == 'colors') {
            $file = $this->config['colors'][rand(0, count($this->config['colors']) - 1)];
        }
        return $file;
    }

    /**
     * Checks if the supplied captcha test value matches the stored one
     *
     * @param   string  $value
     * @access  public
     * @return  bool
     */
    public function check($value)
    {
        $captchaHash = Session::get('captchaHash');

        return $value != null && $captchaHash != null && Hash::check($this->config['sensitive'] === true ? $value : Str::lower($value), $captchaHash);
    }

    /**
     * Returns an URL to the captcha image
     * For example, you can use in your view something like
     * <img src="<?php echo Captcha::img(); ?>" alt="" />
     *
     * @access  public
     * @return  string
     */
    public function img()
    {
        return URL::to('captcha?' . mt_rand(100000, 999999));
    }
}
