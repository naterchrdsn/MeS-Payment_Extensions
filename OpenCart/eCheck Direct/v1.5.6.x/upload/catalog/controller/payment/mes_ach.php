<?php
/**
* Custom Payment Gateway for OpenCart v1.5.6.4
* Using Payment Gateway API v4.12
* For ACH Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMesach extends Controller {
    protected function index() {
        $this->language->load('payment/mes_ach');
        $this->data['text_ach'] = $this->language->get('text_ach');
        $this->data['text_wait'] = $this->language->get('text_wait');
        $this->data['entry_ach_tran'] = $this->language->get('entry_ach_tran');
        $this->data['entry_ach_acct'] = $this->language->get('entry_ach_acct');
        $this->data['entry_ach_type'] = $this->language->get('entry_ach_type');
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['button_back'] = $this->language->get('button_back');
        $this->id       = 'payment';
        $this->template = $this->config->get('config_template') . '/template/payment/mes_ach.tpl';
        $this->render();
    }

    public function send() {
        $json = array();
        if(!$this->config->get('mes_ach_test')) {
            $api_endpoint = 'https://api.merchante-solutions.com/mes-api/tridentApi';
        } else {
            $api_endpoint = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
        }
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if(!isset($this->request->post['transit_num']) || preg_replace("/[^0-9]/", "", $this->request->post['transit_num']) == "" ) {
            $json['error'] = "Routing number is required.";
            $this->response->setOutput(json_encode($json));
        } else if(!isset($this->request->post['account_num']) || preg_replace("/[^0-9]/", "", $this->request->post['account_num']) == "" ) {
            $json['error'] = "Account number is required.";
            $this->response->setOutput(json_encode($json));
        } else {
            $payment_data = array(
                'echo_cart'             => 'OpenCart_' . VERSION,
                // Authentication
                'profile_id'            => $this->config->get('mes_ach_profile_id'),
                'profile_key'           => $this->config->get('mes_ach_profile_key'),
                // ACH Data
                'transaction_type'      => 'H',
                'ach_request'           => 'SALE',
                'auth_type'             => 'WEB',
                'transit_num'           => $this->request->post['transit_num'],
                'account_num'           => $this->request->post['account_num'],
                'account_type'          => $this->request->post['account_type'], //fill this with account type from select box
                // Order data
                'amount'                => $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, FALSE),
                'ref_num'               => $order_info['order_id'],
                'currency_code'         => urlencode($order_info['currency_code']),
                'invoice_number'        => $order_info['order_id'],
                // Customer Data
                'cust_id'               => urlencode($this->config->get('mes_ach_cust_id')),
                'ip_address'            => urlencode($order_info['ip']),
                'cust_name'             => urlencode($order_info['shipping_firstname']),
                'country_code'          => urlencode($order_info['payment_iso_code_2']),
                'cardholder_first_name' => urlencode($order_info['payment_firstname']),
                'cardholder_last_name'  => urlencode($order_info['payment_lastname']),
                'cardholder_street_address' => urlencode($order_info['payment_address_1']),
                'cardholder_zip'        => urlencode($order_info['payment_postcode']),
                'cardholder_phone'      => urlencode($order_info['telephone']),
                'cardholder_email'      => urlencode($order_info['email']),
                // Shipping Data
                'ship_to_first_name'    => urlencode($order_info['shipping_firstname']),
                'ship_to_last_name'     => urlencode($order_info['shipping_lastname']),
                'ship_to_address'       => urlencode($order_info['shipping_address_1']),
                'ship_to_zip'           => urlencode($order_info['shipping_postcode']),
                'dest_country_code'     => urlencode($order_info['shipping_iso_code_2']),
                // Misc Data
                'browser_language'      => $order_info['language_code']
            );
            // Add Kount extras to request, if customer added merch_id on backend
            if($this->config->get('mes_ach_fraud_status')) {
                $payment_data['device_id'] = $this->request->post['device_id'];
            };
            
            $curl = curl_init($api_endpoint);
            curl_setopt($curl, CURLOPT_PORT, 443);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payment_data));
            $response = curl_exec($curl);
            curl_close($curl);

            if(!$response) {
                $json['error'] = 'Authorization attempt failed.<br />Details: '.curl_error($curl) . '(' . curl_errno($curl) . ')';
                $this->response->setOutput(json_encode($json));
            } else {
                $response_data = array();
                parse_str($response, $response_data);
                if($response_data['error_code'] == '000') {
                    $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('mes_ach_order_status_id'));
                    $message = '';
                    if(isset($response_data['avs_result']))
                        $message .= 'AVS Result: ' . $response_data['avs_result'] . "<br />";
                    $message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
                    $message .= 'Gateway Error Code: ' . $response_data['error_code'] . "<br />";
                    $message .= 'Gateway Text Response: ' . $response_data['auth_response_text'] . "<br />";
                    if(isset($response_data['fraud_result'])) {
                        switch ($response_data['fraud_result']) {
                            case 'A':
                                $message .= 'Fraud Result Response: ' . 'Approve';
                                break;
                            case 'E':
                                $message .= 'Fraud Result Response: ' . 'Escalate';
                                break;
                            case 'R':
                                $message .= 'Fraud Result Response: ' . 'Review';
                                break;
                            case 'D':
                                $message .= 'Fraud Result Response: ' . 'Decline';
                                break;
                        };
                    }
                    if(isset($response_data['fraud_result_codes']))
                        $message .= '<br />Fraud Result Codes: '.$response_data['fraud_result_codes'];
                    $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('mes_ach_order_status_id'), $message, FALSE);
                    $json['success'] = $this->url->link('checkout/success', '', 'SSL');
                    $this->response->setOutput(json_encode($json));
                } elseif($response_data['error_code'] == '244') {
                    $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('mes_ach_fraud_status'));
                    $message = '';
                    $message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
                    $message .= 'Gateway Error Code: ' . $response_data['error_code'] . "<br />";
                    $message .= 'Gateway Text Response: ' . $response_data['auth_response_text'] . "<br />";
                    if(isset($response_data['fraud_result']))
                        $message .= 'Fraud Result Response: ' . 'Decline';
                    $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('mes_ach_fraud_status'), $message, FALSE);
                    $json['error'] =  $response_data['error_code'] . ' - ';
                    if(isset($response_data['fraud_result']))
                    	$json['error'] .= $response_data['fraud_result'];
                    $json['error'] .= '<br />' . 'Transaction Declined. Please contact your account representative for more information.';
                    $this->response->setOutput(json_encode($json));
                } else {
                    $json['error'] =  $response_data['error_code'] . ' - ';
                    if(isset($response_data['fraud_result']))
                    	$json['error'] .= $response_data['fraud_result'];
                    $json['error'] .= '<br />' . 'Transaction Declined. Please provide valid eCheck details';
                    $this->response->setOutput(json_encode($json));
                }
            }
        }
    }
}
?>