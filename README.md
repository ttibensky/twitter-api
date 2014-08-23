==========
twitter-api
==========

php library for communicating with Twitter API

# Installation

installation using composer


    "require": {
        ...
        "ttibensky/twitter-api": "dev-master"
        ...
    },
    

# Usage

First of all, you need to initialize Api class with some parameters:


    <?php

    use TTibensky\TwitterApi\Api;

    $api = new Api();
    $api->setTokensDir(__DIR__.'/../tokens');
    $api->setConsumerKey('xxxxxxxxxxxxxxxxxxxxxxxxx');
    $api->setConsumerSecret('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    
    
- token dir is a directory where request tokens and access tokens will be saved, be sure to put it away of web root
- consumer key (API key) is your twitter app identifier, you can find it in your app details on http://dev.twitter.com
- consumer secret is kinda password for using your app through api, you can find it in your app details on http://dev.twitter.com

To use your app, you will need to generate request token. To do so, use method below:


    $api->generateRequestToken('MyTestTwitterAppCustomName');


I suggest to create new file including only functionality for generating request token.
You can find an examle here: https://gist.github.com/ttibensky/bd26217b4f47bcb65de7
