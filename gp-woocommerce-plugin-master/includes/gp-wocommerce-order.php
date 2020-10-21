<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set("UTC");
require_once('../../../../wp-load.php');
require_once( dirname( __FILE__ ) . '/gp-woocommerce-helper.php' );
require_once( dirname( __DIR__ ) . '/gp-woocommerce-plugin.php' );
require_once( dirname( __FILE__ ) . '/gp-woocommerce-http.php' );

$requestBody = file_get_contents('php://input');
$requestBodyJs = json_decode($requestBody, true);

global $woocommerce;

$webhookObj = new WC_Gateway_GlobalPay();
$app_code_client = $webhookObj->app_code_client;
$app_key_client = $webhookObj->app_key_client;
$requestBodyJs['code_client'] = $app_code_client;
$requestBodyJs['key_client'] = $app_key_client;
$enviroment = $webhookObj->enviroment;
$urlorder = ( $enviroment == 'yes') ? 'https://noccapi-stg.globalpay.com.co/order/' : 'https://noccapi.globalpay.com.co/order/';
$bank_code = ( $enviroment == 'yes') ? '1022' : $requestBodyJs['payment-pse-bank_code'];

$webhook_p = plugins_url('/includes/gp-woocommerce-order-response.php', __FILE__);

$jsonRequest = '{
            "carrier":{
               "id":"PSE",
               "extra_params":{
                  "bank_code":"'.$bank_code.'",
                  "response_url":"'.$webhook_p.'",
                  "user":{
                     "name":"'.$requestBodyJs['payment-pse-name'].'",
                     "fiscal_number":"'.$requestBodyJs['payment-pse-fiscal_number'].'",
                     "type_fis_number":"'.$requestBodyJs['payment-pse-type_fis_number'].'",
                     "type":"'.$requestBodyJs['payment-pse-type'].'",
                     "ip_address":"'.WP_DM_GlobalPay_HTTP::get_ip().'"
                  }
               }
            },
            "user":{
               "id":'.$requestBodyJs['payment-pse-user-id'].',
               "email":"'.$requestBodyJs['payment-pse-customer-email'].'"
            },
            "order":{
               "dev_reference":"'.$requestBodyJs['payment-pse-order-id'].'",
               "amount":'.$requestBodyJs['payment-pse-purchase-amount'].',
               "vat": '.$requestBodyJs['payment-pse-vat'].',
               "description":"'.$requestBodyJs['payment-pse-purchase-description'].'"
            }
        }';
$request = json_decode($jsonRequest);
$ch = WP_DM_GlobalPay_HTTP::post($urlorder, $request);
$response = curl_exec($ch);
curl_close($ch);
//return json_decode($response, true);
$responseObject = json_decode($response, true);
$status = true;
if(isset($responseObject['error'])){
	$status = false;
}
$result = ['request' => $request, 'response' => $responseObject, 'status' => $status, 'url' => $responseObject];
wp_send_json($result);
//WC_GlobalPay_Database_Helper::insert_data($status, $comments, $description, $dev_reference, $transaction_id);
