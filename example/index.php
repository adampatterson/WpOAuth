<?php get_header(); ?>
<?php

use Carbon\Carbon;
use Example\AuthService;
use Illuminate\Support\Arr;

if (!class_exists(AuthService::class)) :
    exit;
endif;

// Create a new instance
$oAuthService = new AuthService();

?>

<h3>Hello <?= ($oAuthService->wpOAuth->authenticated()) ? Arr::get($oAuthService->getUser(), 'name') : '' ?> 👋</h3>
<?php
if ($oAuthService->wpOAuth->isTokenExpired() && current_user_can('administrator')) :
    echo $oAuthService->wpOAuth->makeAuthLink('btn btn-primary');
endif;
if ($oAuthService->wpOAuth->authenticated()):
    // This would be your API integration, in this case it's your GitHub profile data.
    $user = $oAuthService->getUser();

    $expires = (int)get_option('_transient_github_service_access_token', 0);
    $time_left = $expires - time();

    ?>
    <img src="<?= Arr::get($user, 'avatar_url') ?>" alt="<?= Arr::get($user, 'name') ?>">
    <h2><?= Arr::get($user, 'name') ?></h2>
    <h4><?= Arr::get($user, 'location') ?></h4>
    <p><?= Arr::get($user, 'bio') ?></p>
    <ul>
        <li><strong>Joined</strong> <?= Carbon::parse(Arr::get($user, 'created_at')) ?></li>
        <li><strong>Public Repos</strong> <?= Arr::get($user, 'public_repos') ?></li>
        <li><strong>Public Gists</strong> <?= Arr::get($user, 'public_gists') ?></li>
        <li><strong>Followers</strong> <?= Arr::get($user, 'followers') ?></li>
        <li><strong>Following</strong> <?= Arr::get($user, 'following') ?></li>
    </ul>
<?php endif; ?>

<ul class="mt-5">
    <li><a href="https://www.oauth.com/playground/authorization-code.html">OAuth 2.0 Authorization Code Flow</a></li>
    <li><a href="https://github.com/settings/developers">OAuth Apps</a>
        <ul>
            <li>
                <a href="https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/differences-between-github-apps-and-oauth-apps">
                    Learn More
                </a>
            </li>
        </ul>
    </li>
    <li><a href="https://github.com/settings/apps">GitHub Apps</a>
        <ul>
            <li>
                <a href="https://docs.github.com/en/apps/creating-github-apps/authenticating-with-a-github-app/about-authentication-with-a-github-app">
                    Learn More</a></li>
        </ul>
    </li>


</ul>

<?php get_footer(); ?>

