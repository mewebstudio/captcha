<?php namespace Mews\Captcha;

use Config, Str, Session, Hash, URL;

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

class Captcha {

    /**
     * @var  Captcha  singleton instance of the Useragent object
     */
    protected static $singleton;

    /**
     * @var  Captcha config instance of the Captcha::$config object
     */
    public static $config = array();

    private static $id;
    private static $assets;
    private static $fonts = array();
    private static $backgrounds = array();
    private static $char;

    public static function instance()
    {

    	if ( ! Captcha::$singleton)
    	{

    		self::$config = Config::get('captcha::config');
    		self::$assets = __DIR__ . '/../../../public/assets/';
    		self::$fonts = self::assets('fonts');
    		self::$backgrounds = self::assets('backgrounds');

    		Captcha::$singleton = new Captcha();

    	}

    	return Captcha::$singleton;

    }

    /**
     * Generates a captcha image, writing it to the output
     * It is used internally by this bundle when pointing to "/captcha" (see [vendor]\routes.php)
     * Typically, you won't use this function, but use the above img() function instead
     *
     * @access	public
     * @return	img
     */
    public static function create($id = null)
    {

        static::$char = Str::random(static::$config['length']);

        Session::put('captchaHash', Hash::make(static::$config['sensitive'] === true ? static::$char : Str::lower(static::$char)));

    	static::$id = $id ? $id : static::$config['id'];

        $bg_image = static::asset('backgrounds');

        $bg_image_info = getimagesize($bg_image);
        if ($bg_image_info['mime'] == 'image/jpg' || $bg_image_info['mime'] == 'image/jpeg')
        {
            $old_image = imagecreatefromjpeg($bg_image);
        }
        elseif ($bg_image_info['mime'] == 'image/gif')
        {
            $old_image = imagecreatefromgif($bg_image);
        }
        elseif ($bg_image_info['mime'] == 'image/png')
        {
            $old_image = imagecreatefrompng($bg_image);
        }

        $new_image = imagecreatetruecolor(static::$config['width'], static::$config['height']);
        $bg = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $bg);

        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, static::$config['width'], static::$config['height'], $bg_image_info[0], $bg_image_info[1]);

        $bg = imagecolorallocate($new_image, 255, 255, 255);
        for ($i = 0; $i < strlen(static::$char); $i++)
        {
            $color_cols = explode(',', static::asset('colors'));
            $fg = imagecolorallocate($new_image, trim($color_cols[0]), trim($color_cols[1]), trim($color_cols[2]));
            imagettftext($new_image, static::asset('fontsizes'), rand(-10, 15), 10 + ($i * static::$config['space']), rand(static::$config['height'] - 10, static::$config['height'] - 5), $fg, static::asset('fonts'), static::$char[$i]);
        }
        imagealphablending($new_image, false);

        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header("Content-type: image/jpg");
        header('Content-Disposition: inline; filename=' . static::$id . '.jpg');
        imagejpeg($new_image, null, static::$config['quality']);
        imagedestroy($new_image);

    }

    /**
     * Fonts
     *
     * @access  public
     * @param   string
     * @return  array
     */
    public static function assets($type = null) {

    	$files = array();

    	if ($type == 'fonts')
    	{
    		$ext = 'ttf';
    	}
    	elseif ($type == 'backgrounds')
    	{
    		$ext = 'png';
    	}

    	if ($type)
    	{
			foreach (glob(static::$assets . $type . '/*.' . $ext) as $filename)
			{
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
    public static function asset($type = null)
    {

    	$file = null;

    	if ($type == 'fonts')
    	{
    		$file = static::$fonts[rand(0, count(static::$fonts) - 1)];
    	}
    	if ($type == 'backgrounds')
    	{
    		$file = static::$backgrounds[rand(0, count(static::$backgrounds) - 1)];
    	}
    	if ($type == 'fontsizes')
    	{
    		$file = static::$config['fontsizes'][rand(0, count(static::$config['fontsizes']) - 1)];
    	}
    	if ($type == 'colors')
    	{
    		$file = static::$config['colors'][rand(0, count(static::$config['colors']) - 1)];
    	}
        return $file;

    }

    /**
     * Checks if the supplied captcha test value matches the stored one
     * 
     * @param	string	$value
     * @access	public
     * @return	bool
     */
    public static function check($value)
    {

		$captchaHash = Session::get('captchaHash');

        return $value != null && $captchaHash != null && Hash::check(static::$config['sensitive'] === true ? $value : Str::lower($value), $captchaHash);

    }

    /**
     * Returns an URL to the captcha image
     * For example, you can use in your view something like
     * <img src="<?php echo Captcha::img(); ?>" alt="" />
     *
     * @access	public
     * @return	string
     */
    public static function img() {

		return URL::to('captcha?' . mt_rand(100000, 999999));

    }

}
