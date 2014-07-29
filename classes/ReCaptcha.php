<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace makorecaptcha;

// Mako

use \mako\config\Config;

// Monolog

use \Psr\Log\LoggerInterface;


/**
 * ReCaptcha.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class ReCaptcha
{
    //---------------------------------------------
    // Class constants
    //---------------------------------------------

    /**
     * The reCAPTCHA server URL's
     */

    const API_SERVER        = 'http://www.google.com/recaptcha/api';
    const API_SECURE_SERVER = 'https://www.google.com/recaptcha/api';
    const VERIFY_SERVER     = 'www.google.com';

    //---------------------------------------------
    // Class properties
    //---------------------------------------------

    /**
     * Config instance.
     *
     * @var \mako\core\Config
     */

    protected $config;

    /**
     * Logger Interface.
     *
     * @var \Psr\Log\LoggerInterface
     */

    protected $logger;

    /**
     * reCaptcha Public Key.
     *
     * @var string
     */

    protected $publicKey;

    /**
     * reCaptcha Private Key.
     *
     * @var string
     */

    protected $privateKey;

    /**
     * Captcha response
     *
     * @var array
     */

    protected $response;

    //---------------------------------------------
    // Class constructor, destructor etc ...
    //---------------------------------------------

    /**
     * Constructor.
     *
     * @access  public
     * @param   \mako\core\Config         $config  Config instance
     * @param   \Psr\Log\LoggerInterface  $logger  Logger Interface
     */

    public function __construct(Config $config, LoggerInterface $logger)
    {
        // Config Instance

        $this->config = $config;

        // Logger Interface

        $this->logger = $logger;

        // Set publick key

        $this->setPublicKey($this->config->get('makorecaptcha::config.public_key'));

        // Set private key

        $this->setPrivateKey($this->config->get('makorecaptcha::config.private_key'));
    }

    //---------------------------------------------
    // Class methods
    //---------------------------------------------

    /**
     * Set publick key
     *
     * @access  private
     * @param   string  $key  ReCaptcha service public key
     */

    private function setPublicKey($key)
    {
        if(trim($key) == false)
        {
            throw new \RuntimeException('To use reCAPTCHA you must get an API key from https://www.google.com/recaptcha/admin/create');
        }

        $this->publicKey = $key;
    }

    /**
     * Set private key
     *
     * @access  private
     * @param   string  $key  ReCaptcha service private key
     */

    private function setPrivateKey($key)
    {
        if(trim($key) == false)
        {
            throw new \RuntimeException('To use reCAPTCHA you must get an API key from https://www.google.com/recaptcha/admin/create');
        }

        $this->privateKey = $key;
    }

    /**
     * Generate Server Url using or not SSL
     *
     * @access  private
     * @return  string
     */

    private function getServerUrl()
    {
        return ($this->config->get('makorecaptcha::config.use_ssl') == true) ? self::API_SECURE_SERVER : self::API_SERVER;
    }

    /**
     * Gets the challenge HTML (javascript and non-javascript version).
     *
     * @return string
     */

    public function html()
    {
        // Recaptcha Options

        $htmlContent = '<script type="text/javascript">
            var RecaptchaOptions =
            {
                theme : "' . $this->config->get('makorecaptcha::config.theme') . '",
                lang : "' . $this->config->get('makorecaptcha::config.lang') . '"
            };
        </script>';

        // Form HTML Content

        $htmlContent .= '<script type="text/javascript" src="'. $this->getServerUrl() . '/challenge?k=' . $this->publicKey . '"></script>
        <noscript>
            <iframe src="'. $this->getServerUrl() . '/noscript?k=' . $this->publicKey . '" height="300" width="500" frameborder="0"></iframe><br/>
            <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
            <input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
        </noscript>';

        // Return HTML

        return $htmlContent;
    }

    /**
     * Calls an HTTP POST function to verify if the user's guess was correct
     *
     * @param  string  $remoteIp
     * @param  string  $challenge
     * @param  string  $response
     * @param  array   $extra_params an array of extra variables to post to the server
     */

    public function check($remoteIp, $challenge, $response, $extra_params = [])
    {
        // Discard spam submissions

        if(trim($remoteIp) == false ||trim($challenge) == false || trim($response) == false)
        {
            // Set answer

            $this->response[0] = 'false';

            return $this;
        }

        // Verify user submission

        $response = $this->verify(['privatekey' => $this->privateKey, 'remoteip' => $remoteIp, 'challenge' => $challenge, 'response' => $response] + $extra_params);

        // Explode response

        $this->response = explode("\n", $response[1]);

        return $this;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server
     *
     * @param   array  $data  User submited data
     * @return  mixed
     */

    protected function verify($data)
    {
        // Verify server

        $host = self::VERIFY_SERVER;

        // Request data

        $requestData = http_build_query($data);

        // HTTP Post Request

        $httpRequest  = "POST /recaptcha/api/verify HTTP/1.0\r\n";
        $httpRequest .= "Host: $host\r\n";
        $httpRequest .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $httpRequest .= "Content-Length: " . strlen($requestData) . "\r\n";
        $httpRequest .= "User-Agent: reCAPTCHA/PHP\r\n";
        $httpRequest .= "\r\n";
        $httpRequest .= $requestData;

        // Open Connection

        $conn = @fsockopen($host, 80, $errno, $errstr, 10);

        // Check connection

        if($conn == false)
        {
            // Log error

            $this->logger->error('Could not open socket');

            return false;
        }
        else
        {
            // Start response

            $response = '';

            // Put http request data

            fwrite($conn, $httpRequest);

            // Build response

            while(!feof($conn))
            {
                // One TCP-IP packet

                $response .= fgets($conn, 1160);
            }

            // Close connection

            fclose($conn);

            // Return response

            return explode("\r\n\r\n", $response, 2);
        }
    }

    /**
     * Returns TRUE if captcha check passed and FALSE if captcha check failed.
     *
     * @access  public
     * @return  boolean
     */

    public function isValid()
    {
        return trim($this->response[0]) == 'true';
    }

    /**
     * Returns FALSE captcha check passed and TRUE if captcha check failed.
     *
     * @access  public
     * @return  boolean
     */

    public function isInvalid()
    {
        return !$this->isValid();
    }
}