<?php

/*
Plugin Name: GlobalPay WooCommerce Plugin
Plugin URI: https://developers.globalpay.com.co/docs/payments/
Description: This module is a solution that allows WooCommerce users to easily process credit card payments.
Version: 1.0
Author: GlobalPay
Author URI: https://developers.globalpay.com.co/docs/payments/
Text Domain: gp_woocommerce
Domain Path: /languages
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
/*
Version: 1.2
Description: Support Payment PSE
Author: Diego Mesa
Author URI: https://dialmedu.github.io
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');

add_action( 'plugins_loaded', 'gp_woocommerce_plugin' );

include( dirname( __FILE__ ) . '/includes/gp-woocommerce-helper.php' );

register_activation_hook( __FILE__, array( 'WC_GlobalPay_Database_Helper', 'create_database' ) );
register_deactivation_hook( __FILE__, array( 'WC_GlobalPay_Database_Helper', 'delete_database' ) );

require( dirname( __FILE__ ) . '/includes/gp-woocommerce-refund.php' );

load_plugin_textdomain( 'gp_woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

include( dirname( __FILE__ ) . '/includes/gp-woocommerce-banks.php' );

define("GP_DOMAIN", "globalpay.com.co");
define("GP_REFUND", "/v2/transaction/refund/");

// TODO: Mover la function globalpay_woocommerce_order_refunded
// define the woocommerce_order_refunded callback
function globalpay_woocommerce_order_refunded($order_id, $refund_id) {
  $refund = new WC_GlobalPay_Refund();
  $refund->refund($order_id);
}

// add the action
add_action( 'woocommerce_order_refunded', 'globalpay_woocommerce_order_refunded', 10, 2 );

if (!function_exists('gp_woocommerce_plugin')) {
  function gp_woocommerce_plugin() {
    class WC_Gateway_GlobalPay extends WC_Payment_Gateway {
      public function __construct() {
        # $this->has_fields = true;
        $this->id = 'gp_woocommerce';
        $this->icon = apply_filters('woocomerce_globalpay_icon', plugins_url('/assets/imgs/favicon.ico', __FILE__));
        $this->method_title = 'GlobalPay Plugin';
        $this->method_description = __('This module is a solution that allows WooCommerce users to easily process credit card payments.', 'gp_woocommerce');

        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->checkout_language = $this->get_option('checkout_language');
        $this->enviroment = $this->get_option('staging');

        $this->app_code_client = $this->get_option('app_code_client');
        $this->app_key_client = $this->get_option('app_key_client');
        $this->app_code_server = $this->get_option('app_code_server');
        $this->app_key_server = $this->get_option('app_key_server');

        // Para guardar sus opciones, simplemente tiene que conectar la función process_admin_options en su constructor.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));

        add_action('woocommerce_receipt_gp_woocommerce', array(&$this, 'receipt_page'));
      }

      public function init_form_fields() {
        $this->form_fields = require( dirname( __FILE__ ) . '/includes/admin/gp-woocommerce-settings.php' );
      }

      function admin_options() {
        $logo = plugins_url('/assets/imgs/logo.jpg', __FILE__);
        ?>
        <p>
          <img style='width: 30%;position: relative;display: inherit;'src='<?php echo $logo;?>'>
        </p>
        <h2><?php _e('GlobalPay Gateway','gp_woocommerce'); ?></h2>
          <table class="form-table">
            <?php $this->generate_settings_html(); ?>
          </table>
        <?php
      }

      function receipt_page($order) {
        echo $this->generate_globalpay_form($order);
      }

      // TODO: Reposicionar la function get_params_post en otro archivo
      public function get_params_post($orderId) {
        $order = new WC_Order($orderId);
        $order_data = $order->get_data();
        $amount = $order_data['total'];
        $products = $order->get_items();
        $description = '';
        foreach ($products as $product) {
          $description .= $product['name'] . ',';
        }
        $subtotal = number_format(($order->get_subtotal()), 2, '.', '');
        $vat = number_format(($order->get_total_tax()), 2, '.', '');
        if (is_null($order_data['customer_id']) or empty($order_data['customer_id'])) {
            $uid = $orderId;
        } else {
            $uid = $order_data['customer_id'];
        }
        $parametersArgs = array(
          'purchase_order_id'    => $orderId,
          'purchase_amount'      => $amount,
          'purchase_description' => $description,
          'customer_phone'       => $order_data['billing']['phone'],
          'customer_email'       => $order_data['billing']['email'],
          'user_id'              => $uid,
          'vat'                  => $vat
        );

        return $parametersArgs;
      }

      public function generate_globalpay_form($orderId) {
        $webhook_p = plugins_url('/includes/gp-woocommerce-webhook.php', __FILE__);
        $css = plugins_url('/assets/css/styles.css', __FILE__);
        $checkout = plugins_url('/assets/js/globalpay_checkout.js', __FILE__);
        $orderData = $this->get_params_post($orderId);
        $orderDataJSON = json_encode($orderData);
		$logo_pse = plugins_url('/assets/imgs/logo_pse.png', __FILE__);
		$logo_global = plugins_url('/assets/imgs/logo_globalpay.png', __FILE__);
        ?>
          <link rel="stylesheet" type="text/css" href="<?php echo $css; ?>">

          <div id="mensajeSucccess" class="hide"> <p class="alert alert-success" ><?= __('Su pago ha sido realizado con éxito. Gracias por su compra.', 'gp_woocommerce'); ?></p> </div>
          <div id="mensajeFailed" class="hide"> <p class="alert alert-warning"><?php _e('Se produjo un error al procesar su pago y no se pudo realizar. Pruebe con otra tarjeta de crédito.', 'gp_woocommerce'); ?></p> </div>

          <div id="buttonreturn" class="hide">
            <p>
              <a class="btn-tienda" href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>"><?php _e( '
Regresar a la tienda', 'woocommerce' ) ?></a>
            </p>
          </div>

          <script src="https://cdn.globalpay.com.co/ccapi/sdk/payment_checkout_2.0.0.min.js"></script>
			<div class="payment-options">
		   	<button class="js-payment-checkout"><img src="<?php echo $logo_global;?>"><?php _e('Pago por tarjeta', 'gp_woocommerce'); ?></button>
          	<button class="js-payment-pse-checkout"><img src="<?php echo $logo_pse;?>"><?php _e('Pago por transferencia', 'gp_woocommerce'); ?></button>
			</div>
		  <?php $this->get_modal_form_pse_pyment($orderData) ?>
          <div id="orderDataJSON" class="hide">
            <?php echo $orderDataJSON; ?>
          </div>
          <script id="woocommerce_checkout_gp" webhook_p="<?php echo $webhook_p; ?>"
            app-key="<?php echo $this->app_key_client; ?>"
            app-code="<?php echo $this->app_code_client; ?>"
            checkout_language="<?php echo $this->checkout_language; ?>"
            enviroment="<?php echo $this->enviroment; ?>"
            src="<?php echo $checkout; ?>">
          </script>
        <?php
      }
	
	// TODO: Reposicionar html y javascript de la funcion get_modal_form_pse_pyment en otro archivo
	 public function get_modal_form_pse_pyment($orderData){
	      $logo_pse = plugins_url('/assets/imgs/logo_pse.png', __FILE__);
	      $bank = new WC_DM_GlobalPay_GetBanks();
	      $order_p = plugins_url('/includes/gp-woocommerce-order.php', __FILE__);
          $list_banks = $bank->getList()['banks'];
		 ?>
		<div class="payment-checkout-modal" id="js-payment-pse-modal" style="visibility: hidden;">
			<button class="payment-checkout-modal__close" onclick="document.querySelector('#js-payment-pse-modal').classList.remove('payment-checkout-modal--visible');" >
				<span class="payment-checkout-modal__closeIcon">×</span>
				<span class="payment-checkout-modal__closeLabel">Cerrar</span>
			</button>
			<div class="payment-checkout-modal-box">
				<div class="payment-checkout-modal-box__content">
					<form id="payment-checkout-form-pse" name="payment-checkout-form-pse" onsubmit="return newIntentOrder(event)">
						<header>
							<h1 style="margin-botton:0px;font-size: 1.5rem;">
								<img width="50px" src="<?php echo $logo_pse;?>"><?=_e('Pago con Transferencia', 'gp_woocommerce')?>
							</h1>
						</header>
						<div>
							<input name="payment-pse-order-id" hidden value="<?= $orderData['purchase_order_id']?>">
							<input name="payment-pse-customer-email" hidden value="<?= $orderData['customer_email']?>">
							<input name="payment-pse-user-id" hidden value="<?= $orderData['user_id']?>">
							<input name="payment-pse-purchase-amount" hidden value="<?= $orderData['purchase_amount']?>">
							<input name="payment-pse-vat" hidden value="<?= $orderData['vat']?>">
							<input name="payment-pse-purchase-description" hidden value="<?= $orderData['purchase_description']?>">
						</div>
						<div class="form-input">
						    <label for="payment-pse-bank_code"><?= _e('Banco', 'gp_woocommerce')?>:</label>
    						<select name="payment-pse-bank_code" class="payment-checkout-input" required>
    						    <?php
    						    foreach ($list_banks as $bank) {
    						    ?>
    							    <option value="<?=$bank['code']?>"><?= $bank['name']?></option>
    							<?php
    						    }
    							?>
    						</select>    
						</div>
						<div class="form-input">
						    <label id="payment-pse-name">Nombre del Titular:</label>
						    <input name="payment-pse-name" type="text" class="payment-checkout-input" required>
						</div>
						<div class="form-input">
						    <label id="payment-pse-type_fis_number">Documento:</label>
						    <select name="payment-pse-type_fis_number" class="payment-checkout-input" required>
						        <option value="">Selecciona un tipo de documento.</option>
						        <option value="CC">Cédula de ciudadanía.</option>
                                <option value="CE">Cédula de extranjería.</option>
                                <option value="NIT">Número de identificación tributario.</option>
                                <option value="TI">Tarjeta de identidad.</option>
                                <option value="PP">	Pasaporte.</option>
                                <option value="DE">	Documento de identificación extranjero.</option>
						    </select>
						</div>
						<div class="form-input">
						    <label id="payment-pse-fiscal_number">Num. documento:</label>
						    <input name="payment-pse-fiscal_number" type="number" class="payment-checkout-input" required>
						    <small>Ingreso solo números; No use puntos, comas u otro caracter especial.</small>
						</div>
						<div class="form-input">
						    <label id="payment-pse-type">Tipo de Persona:</label>
						    <select name="payment-pse-type" class="payment-checkout-input" required>
						        <option value="">Selecciona un tipo.</option>
						        <option value="N">Persona natural</option>
                                <option value="J">Persona juridica.</option>
						    </select>
						</div>
						<footer>
							<?php 
							 if( $this->enviroment == 'yes'){
							?>
							<select name="payment-force-status" class="payment-checkout-input" required>
								<option value="approved" selected><?=_e( 'Approved', 'woocommerce' )?></option>
						        <option value="pending"><?=_e( 'Pending', 'woocommerce' )?></option>
						        <option value="rejected"><?=_e( 'Rejected', 'woocommerce' )?></option>
                                <option value="failure"><?=_e( 'Failure', 'woocommerce' )?></option>
						    </select>
							<?php
							 }
							?>
						    <button id="payment-button-pse-action" class="btn payment-button-popup sucess">Pagar COP $<?= $orderData['purchase_amount'] ?></button>
							<button id="payment-button-pse-loading" class="btn payment-button-popup sucess" style="display:none" disabled="disabled"><img src="https://ccapi-stg.globalpay.com.co/static/img/spinner/Spin.svg" style="vertical-align: middle;" alt="spinner"></button>
						</footer>
					</form>
				</div>
			</div>
			<script>
				document.querySelector('.js-payment-pse-checkout').addEventListener('click',(event)=>{
					document.querySelector('#js-payment-pse-modal').classList.add('payment-checkout-modal--visible');
					event.stopPropagation();
				})
				function newIntentOrder(event) {
				    event.preventDefault();
				    let formElement = document.getElementById("payment-checkout-form-pse");
				    let formData = new FormData(formElement)
					let button = document.querySelector('#payment-button-pse-action');
					let loading = document.querySelector('#payment-button-pse-loading');
					button.style.display = "none"
					loading.style.display = "block"
				    let object = {};
                    formData.forEach(function(value, key){
                        object[key] = value;
                    });
                    fetch('<?=$order_p?>', { method: "POST", body: JSON.stringify(object) })
                    .then(function(response) { return response.json() })
					.then( function(data) { 
						document.querySelector('#js-payment-pse-modal').classList.remove('payment-checkout-modal--visible');
						if(data.payment_status){
							window.location.href = data.url;
						}
					})
                    .catch(function(myJson) { console.log("Error"); console.log(myJson); });
					return false;
                }
			</script>
		</div>
        <?php
	 }

      public function process_payment($orderId) {
          $order = new WC_Order($orderId);
          return array(
              'result' => 'success',
              'redirect' => $order->get_checkout_payment_url(true)
          );
      }
    }
  }
}

function add_gp_woocommerce_plugin( $methods ) {
    $methods[] = 'WC_Gateway_GlobalPay';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_gp_woocommerce_plugin' );
