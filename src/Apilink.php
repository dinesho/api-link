<?php

namespace Dinesho\Apilink;

class Apilink {

    var $API_KEY = null;
    var $SECRET_KEY = null;
    var $SECURITY_TOKEN = null;
    var $REQUEST_URL = null;
    var $REQUEST_PARAMETERS = null;
    var $REQUEST_SIGNATURE = null;
    var $TTL = null;
    var $TIMESTAMP = null;

    public function __construct($api_key, $secret_key, $ttl) {
        $this->API_KEY = $api_key;
        $this->SECRET_KEY = $secret_key;
        $this->TTL = $ttl;
        $this->TIMESTAMP = time();
        $this->SECURITY_TOKEN = $this->API_KEY.$this->SECRET_KEY;
        return $this;
    }


    public function setRequestURL($url){
        $this->REQUEST_URL = $url;
    }

    public function setPayload(array $parameters){
        $this->REQUEST_PARAMETERS = $parameters;
        $this->REQUEST_PARAMETERS['signature-timestamp'] = $this->TIMESTAMP;
        $this->REQUEST_PARAMETERS['signature-user'] = $this->API_KEY;
    }

    public function signPayload()
    {
        $this->REQUEST_SIGNATURE = hash_hmac('sha256', implode("-",$this->REQUEST_PARAMETERS), $this->SECURITY_TOKEN);
        $this->REQUEST_PARAMETERS['signature-token'] = $this->REQUEST_SIGNATURE;
    }

    public function sendRequest(){

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($this->REQUEST_PARAMETERS)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($this->REQUEST_URL, false, $context);

        return $result;
    }

    public function authenticate($httpRequest){
        $requestTimestamp = $httpRequest['signature-timestamp'];
        $this->REQUEST_SIGNATURE = $httpRequest['signature-token'];

        unset($httpRequest['signature-token']);
        $this->REQUEST_PARAMETERS = $httpRequest;
        var_dump($this->REQUEST_PARAMETERS);
        $signatureToken = hash_hmac('sha256', implode("-",$this->REQUEST_PARAMETERS), $this->SECURITY_TOKEN);

        if(($this->TIMESTAMP - $requestTimestamp) < $this->TTL) {
            if(strcmp($this->REQUEST_SIGNATURE, $signatureToken) == 0){
                return array(
                    "CODE" => 200,
                    "INFO" => "Request is valid !"
                );
            }
            else{
                return array(
                    "CODE" => 500,
                    "INFO" => "Invalid request signature !"
                );
            }
        }
        else{
            return array(
                "CODE" => 600,
                "INFO" => "This request has expired !"
            );
        }
    }

} 
