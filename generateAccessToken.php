<?php

namespace TwitterApi;

if (empty($argv[1])) {
    throw new \Exception('Please provide twitter application identifier as parameter 1.');
}
if (empty($argv[2])) {
    throw new \Exception('Please provide PIN as parameter 2.');
}
if (empty($argv[3])) {
    throw new \Exception('Please provide username as parameter 3.');
}

use TwitterApi\Api;

require_once 'autoload.php';

$api = new Api();
$api->generateAccessToken($argv[1], $argv[2], $argv[3]);
