<?php

namespace WpOAuth;

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
    private $authParams;
    private $tokenParams;
    private $refreshParams;
    private $responseType;
    private $scope;
    private $transientPrefix;

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

    public function __construct($settings = [])
    {
        // Should validatet to make sure that all keys are used.
        $this->authUrl         = $settings["authUrl"];
        $this->tokenUrl        = $settings["tokenUrl"];
        $this->clientRedirect  = $settings["clientRedirect"];
        $this->clientId        = $settings["clientId"];
        $this->clientSecret    = $settings["clientSecret"];
        $this->scope           = $settings["scope"];
        $this->responseType    = $settings["response_type"];
        $this->transientPrefix = $settings["transient_prefix"];

        $this->expiresIn = $settings["expires_in"];

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

        // Handle the reauthntication at this point.
        $this->authOrNot();
    }

    /*
     * Handle the authentication flow, If everything is fine then return the tokens.
     * Otherwise negotiate refresh or reauthentication.
     */
    public function authOrNot()
    {
        // Are we wrking with a valid token?
        if ($this->isTokenExpured()) {
            // The token has expired, we need to refresh
            // Do we have a refresh token?
            if ($this->hasRefreshToken()) {
                // Post refresh token 🪃
                $this->postRefreshToken();
            } else {
                // Client needs to reauthenticate the app 💥
                if (current_user_can('administrator')) {
                    echo "<a href='{$this->makeAuthUrl()}'>Authorize</a>";
                    $this->postToken();
                }
            }
        }

        // Retrun new tokens.
        $this->token        = $this->getToken();
        $this->refreshToken = $this->getRefreshToken();
    }

    public function makePrefix($prefix)
    {
        return $this->transientPrefix.'_'.$prefix;
    }

    public function setTokens($response)
    {
        $this->token        = set_transient($this->makePrefix('token'), $response['access_token'], $this->expiresIn);
        $this->refreshToken = set_transient($this->makePrefix('refreshtoken'), $response['refresh_token'],
            YEAR_IN_SECONDS - 1);
    }

    public function isTokenExpured()
    {
        return ! get_transient($this->makePrefix('token'));
    }

    public function hasRefreshToken()
    {
        return ! ! $this->getRefreshToken();
    }

    public function getRefreshToken()
    {
        return get_transient($this->makePrefix('refreshtoken'));
    }

    public function getToken()
    {
        return get_transient($this->makePrefix('token'));
    }

    /*
     * Redirect to the authorization URL.
     */
    public function makeAuthUrl()
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

        $response = Http::asFormParams()->post($this->tokenUrl,
            array_merge(['code' => $code], $this->tokenParams))->json();

        $this->setTokens($response);
    }

    /*
     * Exchange the authorization code for an access token.
     */
    public function postRefreshToken()
    {
        $response = Http::asFormParams()->post($this->tokenUrl,
            array_merge(['refresh_token' => $this->getRefreshToken()], $this->refreshParams))->json();

        $this->setTokens($response);
    }
}