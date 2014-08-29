<?php

namespace TTibensky\TwitterApi;

use Exception;
use OAuthException;

class Api
{
    protected $tokensDir;
    protected $oauth;
    protected $appname;
    protected $username;
    protected $consumerKey;
    protected $consumerSecret;
    protected $accessToken;
    protected $accessTokenSecret;

    /**
     * set twitter application name
     * this will be used in the token file names
     *
     * @param string $appname internal twitter app identifier
     */
    public function setAppName($appname)
    {
        $this->appname = $appname;
    }

    /**
     * get twitter application name
     *
     * @return string appname
     */
    public function getAppName()
    {
        return $this->appname;
    }

    /**
     * set twitter username which you want to authenticate
     *
     * @param string $username twitter username
     */
    public function setUserName($username)
    {
        $this->username = $username;
    }

    /**
     * get twitter username
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * set path to directory where all tokens will be saved
     *
     * @param string $dir
     */
    public function setTokensDir($dir)
    {
        $this->tokensDir = $dir;
    }

    /**
     * get path to tokens directory
     *
     * @return string path
     */
    public function getTokensDir()
    {
        return $this->tokensDir;
    }

    /**
     * set twitter application consumer key
     * it might be known as API key
     * you can find it on http://dev.twitter.com/ page
     *
     * @param $consumerKey
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
    }

    /**
     * get twitter application consumer key
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * set twitter application consumer secret
     * it might be known as API secret
     * you can find it on http://dev.twitter.com/ page
     *
     * @param $consumerSecret
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;
    }

    /**
     * get twitter application consumer secret
     *
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * validate all required input parameters
     *
     * this method is called internally when needed
     *
     * @throws \Exception if some parameter is not valid
     */
    protected function validate()
    {
        if (empty($this->appname)) {
            throw new Exception('App name must be defined.');
        }
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

    /**
     * retrieve token from token file by filepath
     *
     * @param  string     $filePath path to token file
     * @return string               token
     * @throws \Exception           if file does not exists
     */
    protected function getToken($filePath)
    {
        if (! file_exists($filePath)) {
            throw new \Exception('File ' . $filePath . ' does not exists.');
        }

        return file_get_contents($filePath);
    }

    /**
     * save token to token file
     *
     * @param string $filePath path to token file
     * @param string $data     token itself
     */
    protected function setToken($filePath, $data)
    {
        file_put_contents(
            $filePath,
            $data,
            LOCK_EX
        );
    }

    /**
     * this method will load access tokens
     * by previously set application name and username
     * it will do nothing if access tokens are already provided
     *
     * this method is called internally when needed
     */
    protected function loadAccessTokens()
    {
        // if access tokens are pre-loaded, continue
        if (! empty($this->accessToken)
            and ! empty($this->accessTokenSecret)
        ) {
            return;
        }

        $accessTokenFile = $this->tokensDir . '/'.$this->appname.'_'.$this->username.'_access_token';
        $this->accessToken = $this->getToken($accessTokenFile);

        $accessTokenSecretFile = $this->tokensDir . '//'.$this->appname.'_'.$this->username.'_access_token_secret';
        $this->accessTokenSecret = $this->getToken($accessTokenSecretFile);
    }

    /**
     * init OAuth connection to twitter api
     * load required tokens
     * validate all input parameters
     *
     * this method must be called before working with API
     */
    public function init()
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

    /**
     * fetch data from twitter API
     * this method is used for all communication with twitter API
     *
     * @see http://php.net/manual/en/oauth.fetch.php for documentation
     *
     * @param  string $url    url of twitter api which will be called
     * @param  array  $args   arguments which will be sent to twitter api
     * @param  string $method get, post, put...
     * @return array          response from twitter parsed from JSON to array
     * @throws \Exception     on error
     */
    public function fetch($url, $args = null, $method = OAUTH_HTTP_METHOD_GET)
    {
        try {
            $this->oauth->fetch($url, $args, $method);
            return json_decode($this->oauth->getLastResponse(), true);
        } catch (OAuthException $exception) {
            throw new Exception('Cannot fetch from twitter API: ' . $exception->lastResponse);
        }
    }
    
    /**
     * generate request token
     * it will return oauth link and you have to go to that page,
     * because, access to application must be allowed manually
     * it will generate PIN, save it,
     * you will need to pass it as only parameter to generateAccessToken() method
     *
     * @return string oauth link
     */
    public function generateRequestToken()
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
        $this->setToken(
            $this->tokensDir . '/'.$this->appname.'_request_token',
            $request_token
        );
        $this->setToken(
            $this->tokensDir . '/'.$this->appname.'_request_token_secret',
            $request_token_secret
        );
        
        // Generate a request link
        return 'https://api.twitter.com/oauth/authorize?oauth_token=' . $request_token . "\n";
    }

    /**
     * generate access tokens
     * this method will grant access to twitter application,
     * so it might access provided user data from now on
     *
     * @param  string $pin key for allowing access to user for twitter app
     * @return bool        true on success
     */
    public function generateAccessToken($pin)
    {
        $this->oauth = new \OAuth(
            $this->consumerKey, 
            $this->consumerSecret, 
            OAUTH_SIG_METHOD_HMACSHA1, 
            OAUTH_AUTH_TYPE_URI
        );

        // get request tokens
        $request_token = $this->getToken(
            $this->tokensDir . '/'.$this->appname.'_request_token'
        );
        $request_token_secret = $this->getToken(
            $this->tokensDir . '/'.$this->appname.'_request_token_secret'
        );

        // get access tokens
        $this->oauth->setToken($request_token, $request_token_secret);
        $access_token_info = $this->oauth->getAccessToken(
            'https://api.twitter.com/oauth/access_token',
            null,
            $pin
        );
        $this->accessToken = $access_token_info['oauth_token'];
        $this->accessTokenSecret = $access_token_info['oauth_token_secret'];

        // Now store the access token into another file (or database or whatever):
        $this->setToken(
            $this->tokensDir . '/'.$this->appname.'_'.$this->username.'_access_token',
            $this->accessToken
        );
        $this->getToken(
            $this->tokensDir . '/'.$this->appname.'_'.$this->username.'_access_token_secret',
            $this->accessTokenSecret
        );

        // use this access token to verify our credentials.
        $this->init();
        $this->fetch(
            'https://api.twitter.com/1.1/account/verify_credentials.json'
        );

        return true;
    }

    /**
     * retweet a tweet by provided ID
     *
     * @param  int  $id tweet ID
     * @return bool     true on success, false on failure
     */
    public function retweet($id)
    {
        try {
            $this->fetch(
                'https://api.twitter.com/1.1/statuses/retweet/'.$id.'.json',
                null,
                OAUTH_HTTP_METHOD_POST
            );
        } catch (Exception $exc) {
            return false;
        }

        return true;
    }

    /**
     * follow twitter user by provided ID
     *
     * @param  int  $id twitter user ID
     * @return bool     true on success, false on failure
     */
    public function follow($id)
    {
        try {
            $args = array(
                'user_id' => $id,
                'follow'  => true
            );
            $this->fetch(
                'https://api.twitter.com/1.1/friendships/create.json',
                $args,
                OAUTH_HTTP_METHOD_POST
            );
        } catch (Exception $exc) {
            return false;
        }

        return true;
    }
}
