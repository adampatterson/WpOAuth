# WordPress oAuth PHP

![PHP Composer](https://github.com/adampatterson/wpoauth/workflows/PHP%20Composer/badge.svg?branch=main)

A simple oAuth client meant for personal projects

This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/wpoauth)

## Basic Usage

```php
$wpOAuthParams = [
    "authUrl"            => "https://auth.com/connect/authorize",
    "tokenUrl"           => "https://auth.com/connect/token",
    "clientRedirect"     => "https://site.com/?callback=wpoauth",
    "clientId"           => CLIENT_ID,
    "clientSecret"       => CLIENT_SECRET,
    "scope"              => "read offline_access",
    "response_type"      => "code",
    "expires_in"         => HOUR_IN_SECONDS - 1,
    "refresh_expires_in" => (WEEK_IN_SECONDS * 2) - 1,
    "transient_prefix"   => 'change_me'
    "should_log"         => true,
    "log_path"           => __DIR__.'/_log.php',
];

$this->wpOAuth = new WpOAuth($wpOAuthParams);
```

`composer require adampatterson/wpoauth`

## Tests

```
$ composer global require phpunit/phpunit
$ export PATH=~/.composer/vendor/bin:$PATH
$ which phpunit
~/.composer/vendor/bin/phpunit
```

`composer run-script test`

## Local Dev

Run from the themes root.

`ln -s ~/Sites/personal/_packages/WpOAuth ./vendor/adampatterson/wpoauth`

https://wordpress.org/plugins/transients-manager/
