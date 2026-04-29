# WordPress oAuth PHP

![PHP Composer](https://github.com/adampatterson/wpoauth/workflows/PHP%20Composer/badge.svg?branch=main)

A simple oAuth client meant for personal projects

> [!NOTE]
> This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/wpoauth)

```bash
composer require adampatterson/wpoauth
```

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

## Tests

```bash
composer install
composer test
```

## Local Dev

Run from the theme root.

```bash
ln -s ~/Sites/packages/WpOAuth ./vendor/adampatterson/wpoauth
```
