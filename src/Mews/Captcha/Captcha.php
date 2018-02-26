<?php namespace Mews\Captcha;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

/**
 * Laravel 4 Captcha package
 *
 * @copyright Copyright (c) 2014 MeWebStudio
 * @version 1.0.1
 * @author Muharrem ERİN
 * @contact me@mewebstudio.com
 * @link http://www.mewebstudio.com
 * @date 2014-01-25
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Captcha
{
    protected $config = array();
    protected $hashKey;

    /** @var \Illuminate\Hashing\HasherInterface */
    protected $hasher;
    /** @var \Illuminate\Session\Store */
    protected $session;
    /** @var \Illuminate\Routing\UrlGenerator */
    protected $url;

    private $assets;
    private $fonts = array();
    private $backgrounds = array();

    /**
     * @param   array $config
     * @param   string $hashKey
     * @param   string $publishPath
     * @param   \Illuminate\Hashing\HasherInterface $hasher
     * @param   \Illuminate\Session\Store $session
     * @param   \Illuminate\Routing\UrlGenerator $url
     */
    public function __construct(array $config, $hashKey = '', $publishPath = '', $hasher = null, $session = null, $url = null)
    {
        $this->config = $config;
        $this->hashKey = $hashKey;

        $this->hasher = $hasher ?: app('hash');
        $this->session = $session ?: app('session');
        $this->url = $url ?: app('url');

        $this->assets = is_dir($publishPath) ? (is_dir($publishPath . '/assets') ? $publishPath . '/assets' : $publishPath)
            : realpath(__DIR__ . '/../../../public') . '/assets';
        $this->fonts = $this->assets('fonts');
        $this->backgrounds = $this->assets('backgrounds');
    }

    /**
     * @param   int $length
     * @param   string $characters
     * @return  string
     */
    protected static function generateString($length, $characters = '2346789abcdefghjmnpqrtuxyzABCDEFGHJMNPQRTUXYZ')
    {
        $charLength = (strlen($characters) - 1);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charLength)];
        }
        return $randomString;
    }

    /**
     * Generates a captcha image, writing it to the output
     * It is used internally by this bundle when pointing to "/captcha"
     * Typically, you won't use this function, but use the above img() function instead
     *
     * @access  public
     * @param   string $formId
     * @return  \Symfony\Component\HttpFoundation\Response
     */
    public function create($formId = null)
    {
        if ($this->config['type'] == 'num') {
            $code = static::generateString($this->config['length'], '1234567890');
        } else {
            $code = static::generateString($this->config['length']);
        }

        if (!$formId) {
            $formId = hash('sha256', $this->url->previous());
        }
        $this->session->put('captchaHash.' . $formId, $this->hashMake($code));

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

        if (isset($old_image)) {
            imagecopyresampled($new_image, $old_image, 0, 0, 0, 0,
                $this->config['width'], $this->config['height'], $bg_image_info[0], $bg_image_info[1]);
            imagedestroy($old_image);
        }

        $codeLength = strlen($code);
        $spaces = (array)$this->config['space'];
        $space = $spaces[array_rand($spaces)];

        for ($i = 0; $i < $codeLength; $i++) {
            $color_cols = explode(',', $this->asset('colors'));
            $fg = imagecolorallocate($new_image, trim($color_cols[0]), trim($color_cols[1]), trim($color_cols[2]));

            imagettftext($new_image, $this->asset('fontsizes'), mt_rand(-10, 15),
                (10 + $i * $space), ($this->config['height'] - mt_rand(5, 10)), $fg, $this->asset('fonts'), $code[$i]);
        }
        imagealphablending($new_image, false);

        $quality = $this->config['quality'];
        $headers = array(
            'cache-control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'pragma' => 'no-cache',
            'content-type' => 'image/jpeg',
            'content-disposition' => 'inline; filename=captcha.jpg',
        );

        return Response::stream(function () use ($new_image, $quality) {
            imagejpeg($new_image, null, $quality);
            imagedestroy($new_image);
        }, 200, $headers);
    }

    /**
     * @param   string $code
     * @return  string
     */
    protected function hashMake($code)
    {
        if (!$this->config['sensitive']) {
            $code = Str::lower($code);
        }
        return $this->hasher->make($code . $this->hashKey);
    }

    /**
     * @param   string $code
     * @param   string $hash
     * @return  bool
     */
    protected function hashCheck($code, $hash)
    {
        if (!$this->config['sensitive']) {
            $code = Str::lower($code);
        }
        return $this->hasher->check($code . $this->hashKey, $hash);
    }

    /**
     * Fonts
     *
     * @access  public
     * @param   string $type
     * @return  array
     */
    public function assets($type = null)
    {
        $files = array();

        if ($type && is_dir($path = $this->assets . '/' . $type)) {
            foreach (glob($path . '/*.*') as $filename) {
                $files[] = $filename;
            }
        }

        return $files;
    }

    /**
     * Select asset
     *
     * @access  public
     * @param   string $type
     * @return  string
     */
    public function asset($type = null)
    {
        if ($type == 'fonts') {
            return $this->fonts[array_rand($this->fonts)];
        }
        if ($type == 'backgrounds') {
            return $this->backgrounds[array_rand($this->backgrounds)];
        }
        if ($type == 'fontsizes') {
            return $this->config['fontsizes'][array_rand($this->config['fontsizes'])];
        }
        if ($type == 'colors') {
            return $this->config['colors'][array_rand($this->config['colors'])];
        }

        return null;
    }

    /**
     * Checks if the supplied captcha test value matches the stored one
     *
     * @param   string $value
     * @param   string $formId
     * @access  public
     * @return  bool
     */
    public function check($value, $formId = null)
    {
        if (!$formId) {
            $formId = hash('sha256', $this->url->previous());
        }
        $captchaHash = $this->session->get('captchaHash.' . $formId);

        $result = !empty($value)
            && !empty($captchaHash)
            && strlen($value) === $this->config['length'] // must be of the same length right?
            && $this->hashCheck($value, $captchaHash);

        // forget the hash to prevent replay
        $this->session->forget('captchaHash');
        return $result;
    }

    /**
     * Returns an URL to the captcha image
     * For example, you can use in your view something like
     * <img src="<?php echo Captcha::img(); ?>" alt="" />
     *
     * @access  public
     * @param   string $formId
     * @return  string
     */
    public function img($formId = null)
    {
        return $this->url->to('captcha?' . ($formId ? 'id=' . $formId . '&' : '') . mt_rand(100000, 999999));
    }
}
