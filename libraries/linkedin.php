<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Linked API Class
 *
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @author          Muhammad Hafeez
 */
class Linkedin {

    function __construct() {
        
    }

    public function getAuthorizationCode() {
        $params = array('response_type' => 'code',
            'client_id' => API_KEY, // API_KEY is defined in config/constants.php
            'scope' => SCOPE, // SCOPE is defined in config/constants.php
            'state' => uniqid('', true), // unique long string
            'redirect_uri' => REDIRECT_URI, // REDIRECT_URI is defined in config/constants.php
        );
        // Authentication request
        $url = 'https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params);

        // Needed to identify request when it returns to us
        $_SESSION['state'] = $params['state'];

        // Redirect user to authenticate
        header("Location: $url");
        exit;
    }

    public function getAccessToken() {
        $params = array('grant_type' => 'authorization_code',
            'client_id' => API_KEY,
            'client_secret' => API_SECRET,
            'code' => $_GET['code'],
            'redirect_uri' => REDIRECT_URI,
        );
        // Access Token request
        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);

        // Tell streams to make a POST request
        $context = stream_context_create(
                array('http' =>
                    array('method' => 'POST',
                    )
                )
        );

        // Retrieve access token information
        $response = file_get_contents($url, false, $context);

        // Native PHP object, please
        $token = json_decode($response);

        // Store access token and expiration time
        $_SESSION['access_token'] = $token->access_token; // guard this! 
        $_SESSION['expires_in'] = $token->expires_in; // relative time (in seconds)
        $noOfDaysLeft = round($token->expires_in / ( 24 * 60 * 60 ));
        $_SESSION['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . $noOfDaysLeft . ' days')); // relative time (in seconds)
        return true;
    }

    /*
      Function to deal with all API calls to LinkedIn post authentication

      method - GET, POST, PUT or DELETE
      resource - linkedin resource
      token, if not provided will use one from the session
      body - for POST requests like messaging api
      failureRedirect - where to send the user after signup if we find an expired or invalid token
     */

    public function fetch($method, $resource, $token = NULL, $body = NULL) {
        if ($token == NULL) {
            $token = $_SESSION['access_token'];
        } else {
            $token = $token;
        }
        $params = array('oauth2_access_token' => $token,
            'format' => 'json',
        );
        // Need to use HTTPS
        $url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params) . $body;

        // Tell streams to make a (GET, POST, PUT, or DELETE) request
        $context = stream_context_create(
                array('http' =>
                    array('method' => $method,
                    )
                )
        );
        // get data through curl
        $content = $this->get_data($url);
        $content = json_decode($content);

        try {
            // handling linkedin reponse 
            if ($content->status == '401') {

                throw new Exception('API_401');
            } elseif ($content->status == '400') {

                throw new Exception('API_400');
            } elseif ($content->status == '403') {

                throw new Exception('API_403');
            } elseif ($content->status == '404') {

                throw new Exception('API_404');
            } elseif ($content->status == '500') {

                throw new Exception('API_500');
            }
        } catch (Exception $e) {
            // your catch code here, suppose you want to send email here
            // available messages here
            //$e->getMessage()
            //$content->message
            //$content->status
        }

        return $content;
    }

    public function get_data($url) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}

/* End of file Linked.php */
/* Location: ./application/libraries/linkedin.php */

