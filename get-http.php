<?php
require_once './get-token.php';

class WP_DM_GlobalPay_HTTP {
 
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

    public static function getParam($url, $name, $value) {
        $_token = new WC_DM_GlobalPay_GetToken();
        $data_token =  $_token->generate();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Auth-Token:' . $data_token['AUTHTOKEN'],
            $name.":".$value
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
        $ip = "0.0.0.0";
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}