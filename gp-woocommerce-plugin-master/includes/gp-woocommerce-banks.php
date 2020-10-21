<?php

require_once( dirname( __DIR__ ) . '/gp-woocommerce-plugin.php' );
require_once( dirname( __FILE__ ) . '/gp-woocommerce-http.php' );

class WC_DM_GlobalPay_GetBanks {

    function getList($currier = "PSE"){
        $refundObj = new WC_Gateway_GlobalPay();
        $enviroment = $refundObj->enviroment;
        $urlbanks = ( $enviroment == 'yes') ? 'https://noccapi-stg.globalpay.com.co/banks/PSE/' : 'https://noccapi.globalpay.com.co/banks/PSE/';
        $ch = WP_DM_GlobalPay_HTTP::get($urlbanks);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}