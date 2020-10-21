<?php

date_default_timezone_set("UTC");
require_once('../../../../wp-load.php');
require_once( dirname( __FILE__ ) . '/gp-woocommerce-helper.php' );
require_once( dirname( __DIR__ ) . '/gp-woocommerce-plugin.php' );
require_once( dirname( __FILE__ ) . '/gp-woocommerce-http.php' );

$requestBody = file_get_contents('php://input');
$requestBodyJs = json_decode($requestBody, true);

global $woocommerce;
$webhookObj = new WC_Gateway_GlobalPay();
$enviroment = $webhookObj->enviroment;
$urlstatus = ( $enviroment == 'yes') ? 'https://noccapi-stg.globalpay.com.co/pse/order/' : 'https://noccapi.globalpay.com.co/pse/order/';
$detailPayment = array(
  1  => "Verification required",
  2  => "Paid partially",
  3  => "Paid",
  6  => "Fraud",
  7  => "Refund",
  8  => "Chargeback",
  9  => "Rejected by carrier",
  10 => "System error",
  11 => "GlobalPay fraud",
  12 => "GlobalPay blacklist",
  13 => "Time tolerance",
  14 => "Expired by GlobalPay",
  19 => "Invalid Authorization Code",
  20 => "Authorization code expired",
  29 => "Annulled",
  30 => "Transaction seated",
  31 => "Waiting for OTP",
  32 => "OTP successfully validated",
  33 => "OTP not validated",
  35 => "3DS method requested, waiting to continue",
  36 => "3DS challenge requested, waiting CRES",
  37 => "Rejected by 3DS",
  'pending ' => 'Pending',
  'rejected' => 'Rejected',
  'failure' => 'Failure',
  'approved' => 'Approved'
);

$requestBodyJs['status'] = 'Estado Correcto';
if(isset($_GET['dev_reference'])){		
	global $woocommerce;
	$dev_reference = $_GET['dev_reference'];
	$requestBodyJs['order_id'] = $dev_reference;
	$order = new WC_Order($dev_reference);
	$statusOrder = $order->get_status();
	$_transaction_id = get_post_meta($order->id, '_transaction_id', true);
	$urlstatus .= $_transaction_id;

	$ch = WP_DM_GlobalPay_HTTP::get($urlstatus);
	$response = curl_exec($ch);
	curl_close($ch);
	$responseObject = json_decode($response, true);
    $requestBodyJs['status'] = $responseObject;
	
	$status = $responseObject["transaction"]['status'];
	$status_detail = isset($responseObject["transaction"]['status_detail']) ? $responseObject["transaction"]['status_detail']: $responseObject["transaction"]['status'];
	$transaction_id = $responseObject["transaction"]['id'];
	$authorization_code = isset($responseObject["transaction"]['authorization_code']) ?  $responseObject["transaction"]['authorization_code'] : $responseObject["transaction"]['trazability_code'];
	$dev_reference = $responseObject["transaction"]['dev_reference'];
	$globalpay_message = isset($responseObject["transaction"]['message']) ? isset($responseObject["transaction"]['message']) : $responseObject["transaction"]['status_bank'];
	$globalpayStoken = isset($responseObject["transaction"]['stoken']) ? isset($responseObject["transaction"]['stoken']) : '';
	$payment_date = isset($responseObject["transaction"]['paid_date']) ? strtotime($responseObject["transaction"]['paid_date']) : strtotime(date("Y-m-d H:i:s",time()));
	$actual_date = strtotime(date("Y-m-d H:i:s",time()));
	$time_difference = ceil(($actual_date - $payment_date)/60);
	
	//update_post_meta($order->id, '_transaction_id', $transaction_id);
	$description = "Request ".$actual_date;
	if(isset($_SERVER['HTTP_REFERER'])) {
    	$description .= ' referer '.$_SERVER['HTTP_REFERER'];
	}
	
	$comments = "";
	$userId = $responseObject["user"]["id"];
	if ($status_detail == 8 || $status_detail == 'rejected') {
		$description = "Chargeback";
		$comments = __("Payment Cancelled", "gp_woocommerce");
		$order->update_status('cancelled');
		$order->add_order_note( __('Your payment was cancelled. Transaction Code: ', 'gp_woocommerce') . $transaction_id . __(' the reason is chargeback. ', 'gp_woocommerce'));
	} elseif ($status_detail == 3 && $statusOrder == "completed") {
		header("HTTP/1.0 204 transaction_id already received");
	}
	if (!in_array($statusOrder, ['completed', 'cancelled', 'refunded'])) {
		$description = __("GlobalPay Response: Status: ", "gp_woocommerce") . $status_detail .
			__(" | Status_detail: ", "gp_woocommerce") . $status_detail .
			__(" | Dev_Reference: ", "gp_woocommerce") . $dev_reference .
			__(" | Authorization_Code: ", "gp_woocommerce") . $authorization_code .
			__(" | Transaction_Code: ", "gp_woocommerce") . $transaction_id;

		if ($status == 'success' || $status == 'approved') {
			$comments = __("Successful Payment", "gp_woocommerce");
			$order->update_status('completed');
			$order->reduce_order_stock();
			$woocommerce->cart->empty_cart();
			$order->add_order_note( __('Your payment has been made successfully. Transaction Code: ', 'gp_woocommerce') . $transaction_id . __(' and its Authorization Code is: ', 'gp_woocommerce') . $authorization_code);

		} elseif ($status == 'failure' || $status == 'pending' || $status == 'rejected') {
			$comments = __("Payment Failed", "gp_woocommerce");
			$order->update_status('failed');
			$order->add_order_note( __('Your payment has failed. Transaction Code: ', 'gp_woocommerce') . $transaction_id . __(' the reason is: ', 'gp_woocommerce') . $globalpay_message);
		} else {
			$comments = __("Failed Payment", "gp_woocommerce");
			$order->add_order_note( __('The payment fails.: ', 'gp_woocommerce') );
		}
	}
	WC_GlobalPay_Database_Helper::insert_data($status, $comments, $description, $dev_reference, $transaction_id);
	$requestBodyJs['order'] = $order;
	if($enviroment == 'yes'){
		$requestBodyJs['redirect'] = $order->get_checkout_order_received_url();
		wp_send_json($requestBodyJs);
	} else {
		wp_redirect( $order->get_checkout_order_received_url() );
	}
}