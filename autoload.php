<?php

namespace TwitterApi;

define('TWITTER_API_ROOT_DIR', __DIR__ . DIRECTORY_SEPARATOR);

function __autoload($class)
{
    $parts = explode('\\', $class);
    require TWITTER_API_ROOT_DIR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
}
