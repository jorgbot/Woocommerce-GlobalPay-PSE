<?php
require_once './get-api.php';
require_once './get-http.php';

class WC_DM_GlobalPay_GetBanks {

    function getList($currier = API_CURRIER){
        $urlbanks = API_BANKS_PSE;
        $ch = WP_DM_GlobalPay_HTTP::get($urlbanks);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}

if(isset($_GET['debbug'])){
    $banks = new WC_DM_GlobalPay_GetBanks();
    $list = $banks->getList();
    echo(json_encode($list));
}

