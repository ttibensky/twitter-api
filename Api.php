<?php

namespace TwitterApi;

use Exception;
use OAuthException;

class Api
{
    protected $oauth;
    protected $username;
    protected $consumerKey;
    protected $consumerSecret;
    protected $accessToken;
    protected $accessTokenSecret;
    
    public function __construct()
    {
    }
    
    protected function loadAccessTokens()
    {
        // if access tokens are pre-loaded, continue
        if (! empty($this->accessToken)
            and ! empty($this->accessTokenSecret)
        ) {
            return;
        }
        
        $accessTokenFile = __DIR__.'/tokens/'.$this->username.'_access_token';
        if (! file_exists($accessTokenFile)) {
            throw new \Exception(
                'File ' . $accessTokenFile . ' does not exists. Did you forgot to generate access token?'
            );
        }
        
        $accessTokenSecretFile = __DIR__.'/tokens/'.$this->username.'_access_token_secret';
        if (! file_exists($accessTokenSecretFile)) {
            throw new \Exception(
                'File ' . $accessTokenSecretFile . ' does not exists. Did you forgot to generate access token?'
            );
        }
        
        $this->accessToken = file_get_contents(
            $accessTokenFile
        );
        $this->accessTokenSecret = file_get_contents(
            $accessTokenSecretFile
        );
    }
    
    protected function initApi()
    {
        // load access tokens
        $this->loadAccessTokens();
        
        // validate tokens
        $this->validate();
        
        // create oauth instance
        $this->oauth = new \OAuth(
            $this->consumerKey, 
            $this->consumerSecret, 
            OAUTH_SIG_METHOD_HMACSHA1
        );
        
        // set access tokens
        $this->oauth->setToken(
            $this->accessToken, 
            $this->accessTokenSecret
        );
    }
    
    private function validate()
    {
        if (empty($this->username)) {
            throw new Exception('Username must be defined.');
        }
        if (empty($this->consumerKey)) {
            throw new Exception('Consumer key must be defined.');
        }
        if (empty($this->consumerSecret)) {
            throw new Exception('Consumer secret must be defined.');
        }
        if (empty($this->accessToken)) {
            throw new Exception('Access token must be defined.');
        }
        if (empty($this->accessTokenSecret)) {
            throw new Exception('Access token secret must be defined.');
        }
    }
    
    public function fetch($url, $args = null, $method = OAUTH_HTTP_METHOD_GET)
    {
        try {
            $this->oauth->fetch($url, $args, $method);
            return json_decode($this->oauth->getLastResponse());
        } catch (OAuthException $exception) {
            throw new Exception('Cannot fetch from twitter API: ' . $exception->getMessage());
        }
    }
    
    protected function saveToken($file, $data)
    {
        file_put_contents(
            $file, 
            $data,
            LOCK_EX
        );
    }
    
    /**
     * generate request token
     * print oauth link, access to application must be allowed manually
     * 
     * @param string $app twitter application identifier
     */
    public function generateRequestToken($app)
    {
        $this->oauth = new \OAuth(
            $this->consumerKey, 
            $this->consumerSecret, 
            OAUTH_SIG_METHOD_HMACSHA1, 
            OAUTH_AUTH_TYPE_URI
        );

        $request_token_info = $this->oauth->getRequestToken(
            'https://api.twitter.com/oauth/request_token'
        );

        $request_token = $request_token_info['oauth_token'];
        $request_token_secret = $request_token_info['oauth_token_secret'];

        // Save the token to a local file.
        $this->saveToken(
            ROOT_DIR . 'TwitterApi/tokens/'.$app.'_request_token', 
            $request_token
        );
        $this->saveToken(
            ROOT_DIR . 'TwitterApi/tokens/'.$app.'_request_token_secret', 
            $request_token_secret
        );
        
        // Generate a request link and output it
        print 'https://api.twitter.com/oauth/authorize?oauth_token=' . $request_token . "\n";
    }
    
    public function generateAccessToken($app, $pin, $username)
    {
        $this->oauth = new \OAuth(
            $this->consumerKey, 
            $this->consumerSecret, 
            OAUTH_SIG_METHOD_HMACSHA1, 
            OAUTH_AUTH_TYPE_URI
        );

        $request_token = file_get_contents(
            ROOT_DIR . 'TwitterApi/tokens/'.$app.'_request_token'
        );
        $request_token_secret = file_get_contents(
            ROOT_DIR . 'TwitterApi/tokens/'.$app.'_request_token_secret'
        );
        
        $this->oauth->setToken($request_token, $request_token_secret);
        $access_token_info = $this->oauth->getAccessToken(
            'https://api.twitter.com/oauth/access_token',
            null,
            $pin
        );
        $this->accessToken = $access_token_info['oauth_token'];
        $this->accessTokenSecret = $access_token_info['oauth_token_secret'];

        // Now store the access token into another file (or database or whatever):
        file_put_contents(
            ROOT_DIR.'TwitterApi/tokens/'.$app.'_'.$username.'_access_token', 
            $this->accessToken
        );
        file_put_contents(
            ROOT_DIR.'TwitterApi/tokens/'.$app.'_'.$username.'_access_token_secret', 
            $this->accessTokenSecret
        );

        // Give it a whirl.
        // use this access token to verify our credentials.
        $this->initApi();
        $response = $this->fetch(
            'https://api.twitter.com/1.1/account/verify_credentials.json'
        );
        
        print "Access token saved! Authorized as @" . (string)$response->screen_name . "\n";        
    }
}
