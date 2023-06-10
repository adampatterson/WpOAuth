<?php

use Example\AuthService;

include_once(ABSPATH.'wp-admin/includes/plugin.php');

// Load Composer
if (file_exists(__DIR__.'/vendor/autoload.php')) :
    require_once __DIR__.'/vendor/autoload.php';
endif;
