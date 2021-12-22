<?php

namespace WpOAuth;

/**
 * Class WpOAuth
 * @package WpOAuth
 * @author Adam Patterson <http://github.com/adampatterson>
 * @link  https://github.com/adampatterson/WpOAuth
 */
class WpOAuth
{

    /**
     * @var string
     */
    private $authUrl;
    private $tokenUrl;
    private $clientRedirect;
    private $clientId;
    private $clientSecret;
    private $token;
    private $refreshToken;
    private $expiresIn;
    private $refreshExpiresIn;
    private $authParams;
    private $tokenParams;
    private $refreshParams;
    private $responseType;
    public $responseStatus;
    private $scope;
    private $transientPrefix;
    private $shouldLog;
    private $logPath;

    private $requiredSettings = [
        "authUrl",
        "tokenUrl",
        "clientRedirect",
        "clientId",
        "clientSecret",
        "scope",
        "response_type",
        "transient_prefix"
    ];

    /*
    $wpOAuthParams = [
        "authUrl"            => "https://auth.com/connect/authorize",
        "tokenUrl"           => "https://auth.com/connect/token",
        "clientRedirect"     => "https://site.com/?callback=wpoauth",
        "clientId"           => "",
        "clientSecret"       => "",
        "scope"              => "read offline_access",
        "response_type"      => "code",
        "expires_in"         => HOUR_IN_SECONDS - 1,
        "refresh_expires_in" => (WEEK_IN_SECONDS * 2) - 1,
        "transient_prefix"   => 'changeme'
        "should_log"         => true,
        "log_path"           => __DIR__.'/_log.php',
    ];

    $this->wpOAuth = new WpOAuth($wpOAuthParams);
     */
    public function __construct($settings = [])
    {
        // Should validate to make sure that all keys are used.
        $this->authUrl          = $settings["authUrl"];
        $this->tokenUrl         = $settings["tokenUrl"];
        $this->clientRedirect   = $settings["clientRedirect"];
        $this->clientId         = $settings["clientId"];
        $this->clientSecret     = $settings["clientSecret"];
        $this->scope            = $settings["scope"];
        $this->responseType     = $settings["response_type"];
        $this->responseStatus  = null;

        $this->transientPrefix  = $settings["transient_prefix"];
        
        // Token Expiry times
        $this->expiresIn        = $settings["expires_in"];
        $this->refreshExpiresIn = $settings["refresh_expires_in"];

        // Optional logging
        $this->shouldLog = (array_key_exists('should_log', $settings) && (bool) $settings["should_log"]) ? true : false;
        $this->logPath   = (array_key_exists('log_path',
                $settings) && (bool) $settings["log_path"]) ? $settings["log_path"] : false;

        // offline_access is required for refresh tokens.
        $this->authParams = [
            "response_type" => $this->responseType,
            "scope"         => $this->scope,
            "client_id"     => $this->clientId,
            "redirect_uri"  => $this->clientRedirect
        ];

        $this->tokenParams = [
            "client_id"     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            "redirect_uri"  => $this->clientRedirect,
        ];

        $this->refreshParams = [
            "client_id"     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
        ];
    }

    /*
     * Handle the authentication flow, If everything is fine then return the tokens.
     * Otherwise negotiate refresh or reauthenticate.
     */
    public function authOrNot()
    {
        // Are we wrking with a valid token?
        if ($this->isTokenExpired()) {
            // The token has expired, we need to refresh
            $this->log('The token has expired, we need to refresh');
            // Do we have a refresh token?
            if ($this->hasRefreshToken()) {
                // Post refresh token 🪃
                $this->log('Post refresh token');
                $this->postRefreshToken();
            } else {
                // Client needs to reauthenticate the app 💥
                $this->log('Client needs to reauthenticate the app');
//                if (current_user_can('administrator')) {
                    $this->log('Post Token');
//                    $this->makeAuthLink();
                    $this->postToken();
//                }
            }
        }

        // Return old tokens.
        $this->token        = $this->getToken();
        $this->refreshToken = $this->getRefreshToken();
    }

    public function makePrefix($prefix)
    {
        return $this->transientPrefix.'_'.$prefix;
    }

    public function setTokens($response)
    {
        if ($this->responseStatus) {
            $this->token        = set_transient($this->makePrefix('access_token'), $response['access_token'],
                $this->expiresIn);
            $this->refreshToken = set_transient($this->makePrefix('refresh_token'), $response['refresh_token'],
                $this->refreshExpiresIn);
        } else {
            $this->log('Set Token: Did not return 200 success, re-apply the old Refresh Token if possible.');
            $this->refreshToken = $this->getRefreshToken();
        }
    }

    public function updateRefreshToken()
    {
        return $this->expiresIn;
    }

    public function isAuthenticating()
    {
        $code = Request::get('code', false);

        if ( ! $code) {
            return false;
        }

        return true;
    }

    public function isTokenExpired()
    {
        return ! get_transient($this->makePrefix('access_token'));
    }

    public function hasRefreshToken()
    {
        return ! ! $this->getRefreshToken();
    }

    public function getRefreshToken()
    {
        return get_transient($this->makePrefix('refresh_token'));
    }

    public function getToken()
    {
        return get_transient($this->makePrefix('access_token'));
    }

    public function makeAuthLink()
    {
        if ( ! $this->isAuthenticating()) {
            echo "<a href='{$this->getAuthUrl()}'>Authorize</a>";
        }
    }

    /*
     * Redirect to the authorization URL.
     */
    public function getAuthUrl()
    {
        return $this->authUrl.'?'.http_build_query($this->authParams);
    }

    /*
     * Receive an authorization code
     */
    public function postToken()
    {
        $code = Request::get('code', false);

        if ( ! $code) {
            return;
        }

        $postResponse = Http::asFormParams()->post($this->tokenUrl,
            array_merge(['code' => $code], $this->tokenParams));

        $response             = $postResponse->json();
        $this->responseStatus = $postResponse->status();

        $this->setTokens($response);

        $this->log('Receive an authorization code', $response);

        $this->redirectTo($this->clientRedirect);
    }

    /*
     * Exchange the authorization code for an access token.
     */
    public function postRefreshToken()
    {
        $postResponse = Http::asFormParams()->post(
            $this->tokenUrl, array_merge(['refresh_token' => $this->getRefreshToken()], $this->refreshParams)
        );

        $response             = $postResponse->json();
        $this->responseStatus = $postResponse->status();

        $this->log('Exchange the authorization code for an access token', $response);

        $this->setTokens($response);
    }

    /**
     * @param $url
     */
    public function redirectTo($url)
    {
        header('Location: '.$url);
        exit;
    }
    
/**
     * Prints to WordPress log file
     *
     * @param $message
     */
    public function log($message, $data = null)
    {
        if ($this->shouldLog && $this->logPath) {
            $this->writeLog($message, $data);
        }
    }

    public function getDebugDetals()
    {
        return [
            'refresh'                         => $this->getRefreshToken(),
            'refresh_expires_in_seconds'      => $this->getExpiryTime($this->makePrefix('refresh_token')),
            'access_token'                    => $this->getToken(),
            'access_token_expires_in_seconds' => $this->getExpiryTime($this->makePrefix('access_token')),
            'response_status'                 => $this->responseStatus,
        ];
    }

    public function getExpiryTime($key)
    {
        $transient = get_option('_transient_timeout_'.$key, 0);

        return ($transient === 0) ? 'not set' : get_option('_transient_timeout_'.$key, 0) - time();
    }

    /**
     * @param $message
     */
    public function writeLog($message, $data)
    {
        if ( ! file_exists($this->logPath)) {
            $open  = fopen($this->logPath, "a");
            $write = fputs($open, "<?php \r\n die; \r\n?>\r\n");
            fclose($open);
        }

        $time = date("F jS Y, H:i", time() + 25200);

        $logMessage = "#============================================\r\n";
        $logMessage .= "# $time\r\n$message\r\n\r\n";

        if (is_array($data)) {
            $logMessage .= "# Data:\r\n".print_r($data, true)."\r\n";
        }

        $logMessage .= "# Debug:\r\n".print_r($this->getDebugDetals(), true)."\r\n";

        $open  = fopen($this->logPath, "a");
        $write = fputs($open, $logMessage);
        fclose($open);
    }
}
