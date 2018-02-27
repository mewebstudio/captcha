<?php namespace Mews\Captcha;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

/**
 * Laravel 4 Captcha package
 *
 * @author Muharrem ERİN <me@mewebstudio.com>
 * @copyright Copyright (c) 2014 MeWebStudio
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @version 1.0.1
 * @contact me@mewebstudio.com
 * @link http://www.mewebstudio.com
 * @date 2014-01-25
 */
class Captcha
{
    protected $config = array();
    protected $hashKey;

    /**
     * @var \Illuminate\Hashing\HasherInterface
     */
    protected $hasher;
    /**
     * @var \Illuminate\Session\Store
     */
    protected $session;
    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
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

        $this->assets = !is_dir($publishPath) ? realpath(__DIR__ . '/../../../public') . '/assets'
            : (is_dir($publishPath . '/assets') ? $publishPath . '/assets' : $publishPath);
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
     * @param   string $formId
     * @return  \Symfony\Component\HttpFoundation\Response
     * @access  public
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

        $bgImage = $this->asset('backgrounds');
        $bgImageInfo = getimagesize($bgImage);

        if ($bgImageInfo['mime'] == 'image/jpg' || $bgImageInfo['mime'] == 'image/jpeg') {
            $oldImage = imagecreatefromjpeg($bgImage);
        } elseif ($bgImageInfo['mime'] == 'image/gif') {
            $oldImage = imagecreatefromgif($bgImage);
        } elseif ($bgImageInfo['mime'] == 'image/png') {
            $oldImage = imagecreatefrompng($bgImage);
        }

        $newImage = imagecreatetruecolor($iw = $this->config['width'], $ih = $this->config['height']);
        $bg = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $bg);

        if (isset($oldImage)) {
            imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $iw, $ih, $bgImageInfo[0], $bgImageInfo[1]);
            imagedestroy($oldImage);
        }

        $codeLength = strlen($code);
        $spaces = (array)$this->config['space'];
        $space = $spaces[array_rand($spaces)];

        for ($i = 0; $i < $codeLength; $i++) {
            $colorCols = explode(',', $this->asset('colors'));
            $fg = imagecolorallocate($newImage, trim($colorCols[0]), trim($colorCols[1]), trim($colorCols[2]));

            imagettftext($newImage, $this->asset('fontsizes'), mt_rand(-10, 15), ($i * $space + 10), ($ih - mt_rand(5, 10)), $fg, $this->asset('fonts'), $code[$i]);
        }
        imagealphablending($newImage, false);

        $quality = $this->config['quality'];
        $headers = array(
            'cache-control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'pragma' => 'no-cache',
            'content-type' => 'image/jpeg',
            'content-disposition' => 'inline; filename=captcha.jpg',
        );

        return Response::stream(function () use ($newImage, $quality) {
            imagejpeg($newImage, null, $quality);
            imagedestroy($newImage);
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
     * @param   string $type
     * @return  array
     * @access  public
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
     * @param   string $type
     * @return  string
     * @access  public
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
     * @return  bool
     * @access  public
     */
    public function check($value, $formId = null)
    {
        if (!$formId) {
            $formId = hash('sha256', $this->url->previous());
        }
        $captchaHash = $this->session->get('captchaHash.' . $formId);

        // must be of the same length
        $result = !empty($value)
            && !empty($captchaHash)
            && strlen($value) === $this->config['length']
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
     * @param   string $formId
     * @return  string
     * @access  public
     */
    public function img($formId = null)
    {
        return $this->url->to('captcha?' . ($formId ? 'id=' . $formId . '&' : '') . mt_rand(100000, 999999));
    }
}
