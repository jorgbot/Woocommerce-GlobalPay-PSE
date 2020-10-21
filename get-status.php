<?php
require_once './get-api.php';
require_once './get-http.php';

class WC_DM_GlobalPay_GetStatus {

    function getStatus($id){
        $currier = API_ORDER_PSE;
        return $this->getStatusCurrier($id, $currier);
    }

    function getStatusCurrier($id, $currier = API_ORDER_PSE){
        $urlSource = $currier.$id;
        $ch = WP_DM_GlobalPay_HTTP::get($urlSource);
        $response = curl_exec($ch);
        $result = [
            'request' => [
                'url' => $urlSource,
                'id' => $id
            ],
            'response' => json_decode($response, true)
        ];
        curl_close($ch);
        return $result;
    }
}

if(isset($_GET['debbug'])){
    $status = new WC_DM_GlobalPay_GetStatus();
    if ( isset($_GET["id"]) ) {
        $response = $status->getStatus($_GET["id"]);
        echo(json_encode($response));
    } else {
        echo( json_encode('{"error": "404", "message": "id is requiered"}') );
    }
}

