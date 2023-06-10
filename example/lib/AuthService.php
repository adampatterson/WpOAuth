<?php

namespace Example;

use Http\Http;
use WpOAuth\WpOAuth;

class AuthService
{
    public WpOAuth $wpOAuth;
    public string $baseUrl;
    public string $rootApi;

    public function __construct()
    {

        $this->rootApi = 'https://api.github.com/';

        // https://docs.github.com/en/apps/oauth-apps
        // oAuth config
        $wpOAuthParams = [
            "authUrl"          => "https://github.com/login/oauth/authorize",
            "tokenUrl"         => "https://github.com/login/oauth/access_token",
            "clientRedirect"   => get_site_url() . '?callback=wpoauth',
            "clientId"         => CLIENT_ID,
            "clientSecret"     => CLIENT_SECRET,
            "scope"            => "",
            "state"            => "random_state_string",
            "response_type"    => "code",
//            "expires_in"         => HOUR_IN_SECONDS - 1,
//            "refresh_expires_in" => (WEEK_IN_SECONDS * 2) - 1,
            "transient_prefix" => 'github_service',
            "should_log"       => true,
            "log_path"         => __DIR__ . '/_log.php',
        ];

        $this->baseUrl = $wpOAuthParams['clientRedirect'];

        // oAuth client
        $this->wpOAuth = new WpOAuth($wpOAuthParams);
        // Handle the re-authentication at this point.

        // Don't make calls while in /wp-admin
        $this->wpOAuth->authOrNot();
    }

    public function getUser()
    {
        return $this->results = $this->get('user');
    }

    /**
     * The primary Get method for all API activity and handles the tokens
     *
     * Best practice would be to cache these responses
     *
     * @param $resource
     * @param array $params
     *
     * @return mixed
     */
    public function get($resource, $params = [])
    {
        if (!$this->wpOAuth->authenticated()) {
            // Re-authenticate the request
            $this->wpOAuth->authOrNot();
        }

        // Clear any trailing slashes
        $url = rtrim($this->rootApi, '/') . '/' . $resource;
        $getResponse = Http::withToken($this->wpOAuth->getToken())
            ->get($url, $params);

        $response = $getResponse->json();
        $this->wpOAuth->responseStatus = $getResponse->status();

        $this->wpOAuth->log('Request: ', [$resource, $params]);

        return $response;
    }
}
