<?php

require_once( dirname( __DIR__ ) . '/gp-woocommerce-plugin.php' );

class WC_DM_GlobalPay_GetToken {

    protected  $server_application_code;
    protected  $server_app_key;

    function __construct() {
        $refundObj = new WC_Gateway_GlobalPay();
        $this->server_application_code = $refundObj->app_code_server;
        $this->server_app_key = $refundObj->app_key_server;
    }

    public function generate(){
        $date = new DateTime();
        $unix_timestamp = $date->getTimestamp();
        // $unix_timestamp = "1546543146";
        $uniq_token_string =  $this->server_app_key.$unix_timestamp;
        $uniq_token_hash = hash('sha256', $uniq_token_string);
        $auth_token = base64_encode($this->server_application_code.";".$unix_timestamp.";".$uniq_token_hash);
        $data = array();
        $data['TIMESTAMP'] =  $unix_timestamp;
        $data['TIMESTAMP'] = $unix_timestamp;
        $data['UNIQTOKENST'] = $uniq_token_string;
        $data['UNIQTOHAS'] = $uniq_token_hash;
        $data['AUTHTOKEN'] = $auth_token;
        return $data;
    }
   
}

class  WP_DM_GlobalPay_HTTP {
 
    public static function get($url) {
        $_token = new WC_DM_GlobalPay_GetToken();
        $data_token =  $_token->generate();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Auth-Token:' . $data_token['AUTHTOKEN']));
        return $curl;
    }

    public static function getParam($url, $params) {
        $_token = new WC_DM_GlobalPay_GetToken();
        $data_token =  $_token->generate();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params ); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Auth-Token:' . $data_token['AUTHTOKEN']
        ));
        return $curl;
    }

    public static function post($url, $params) {
        $_token = new WC_DM_GlobalPay_GetToken();
        $data_token =  $_token->generate();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, (json_encode($params)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Auth-Token:' . $data_token['AUTHTOKEN']));
        return $curl;
    } 

    public static function get_ip() {
        $ipaddress = '0.0.0.0';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
    }

}