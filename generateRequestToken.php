<?php

namespace TwitterApi;

if (empty($argv[1])) {
    throw new \Exception('Please provide twitter application identifier as parameter 1.');
}

use TwitterApi\Api;

require_once 'autoload.php';

$api = new TwitterApi\Api();
$api->generateRequestToken($argv[1]);
