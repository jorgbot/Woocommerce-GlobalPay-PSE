<?php

require_once( dirname( __DIR__ ) . '/gp-woocommerce-plugin.php' );
require_once( dirname( __FILE__ ) . '/gp-woocommerce-http.php' );

class WC_DM_GlobalPay_PSE {
    
    public $request;

    public function created() {
        $jsonRequest = '{

            "carrier":{
         
               "id":"PSE",
         
               "extra_params":{
         
                  "bank_code":"1022",
         
                  "response_url":"https://example.your_url/",
         
                  "user":{
         
                     "name":"Diego Medina",
         
                     "fiscal_number":"12345678",
         
                     "type_fis_number":"CC",
         
                     "type":"N",
         
                     "ip_address":"'.WP_DM_GlobalPay_HTTP::get_ip().'"
         
                  }
         
               }
         
            },
         
            "user":{
         
               "id":13,
         
               "email":"diegomesa.1414@gmail.com"
         
            },
         
            "order":{
         
               "dev_reference":"approved",
         
               "amount":'.(isset($_GET['amount']) ? $_GET['amount'] : 10000).',
         
               "vat": 0.0,
         
               "description":"Prueba de integracion"
         
            }
        }';
        $this->request = json_decode($jsonRequest);
        $urlorder = API_ORDER;
        $ch = WP_DM_GlobalPay_HTTP::post($urlorder, $this->request);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}

