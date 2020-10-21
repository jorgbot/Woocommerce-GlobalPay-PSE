<?php
require_once 'get-api.php';

Class WC_DM_GlobalPay_GetToken {

    protected  $server_application_code;
    protected  $server_app_key;

    function __construct() {
        $this->server_application_code = API_LOGIN_DEV;
        $this->server_app_key = API_KEY_DEV ;
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


