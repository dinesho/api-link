<?php

namespace Dinesho\Apilink;

class Apilink {

    var $API_KEY = null;
    var $SECRET_KEY = null;
    var $SECURITY_TOKEN = null;
    var $PAYLOAD = null;
    var $SIGNATURE = null;
    var $TTL = null;
    var $TIMESTAMP = null;

    /*
     * Creates and returns an Apilink object for a given
     * API KEY, SECRET KEY and a TTL
     */
    public function __construct($api_key, $secret_key, $ttl) {
        $this->API_KEY = $api_key;
        $this->SECRET_KEY = $secret_key;
        $this->TTL = $ttl;
        $this->TIMESTAMP = time();
        $this->SECURITY_TOKEN = $this->API_KEY.$this->SECRET_KEY;
        return $this;
    }

    /*
     * Creates and Sends a request to the API end point for a given
     * endpoint url and a payload array.
     * Returns the result for the request if success
     */
    public function sendRequest($url, array $payload){

        // Set payload array and add timestamp and api user
        // to payload parameters
        $this->PAYLOAD = $payload;
        $this->PAYLOAD['signature-timestamp'] = $this->TIMESTAMP;
        $this->PAYLOAD['signature-user'] = $this->API_KEY;

        // Sign payload for the current request parameters
        $this->SIGNATURE = hash_hmac('sha256', implode("-",$this->PAYLOAD), $this->SECURITY_TOKEN);

        // Add signature token to the request parameters
        $this->PAYLOAD['signature-token'] = $this->SIGNATURE;

        // Configure request parameters and get the response
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($this->PAYLOAD)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($this->$url, false, $context);

        return $result;
    }

    /*
     * Authenticate a API request when the http request parameter array is passed.
     * Pass POST/REQUEST array direct into the function after creating a
     * Apilink object for the request user
     */
    public function authenticate($httpRequest){

        // Store original timestamp and signature
        // token came with the request
        $requestTimestamp = $httpRequest['signature-timestamp'];
        $this->SIGNATURE = $httpRequest['signature-token'];

        // Remove the signature token from request parameters array
        unset($httpRequest['signature-token']);

        // Set payload to the link and generate signature token
        $this->PAYLOAD = $httpRequest;
        $signatureToken = hash_hmac('sha256', implode("-",$this->PAYLOAD), $this->SECURITY_TOKEN);

        // Check the timestamp for expired or not regard to the ttl
        if(($this->TIMESTAMP - $requestTimestamp) < $this->TTL) {
            // If request is not expired check the signature validity
            if(strcmp($this->SIGNATURE, $signatureToken) == 0){
                // Request signature is valid
                return array(
                    "CODE" => 200,
                    "INFO" => "Request is valid !"
                );
            }
            else{
                // Invalid signature
                return array(
                    "CODE" => 300,
                    "INFO" => "Invalid request signature !"
                );
            }
        }
        else{
            // If signature expired already
            return array(
                "CODE" => 400,
                "INFO" => "This request has expired !"
            );
        }
    }

} 
