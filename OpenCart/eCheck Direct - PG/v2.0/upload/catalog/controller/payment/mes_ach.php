<?php
/**
* Custom Payment Gateway for OpenCart v2
* Using Payment Gateway API v4.12
* For ACH Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMesach extends Controller {
	public function index() {
    	$this->language->load('payment/mes_ach');
		$data['text_testmode'] = $this->language->get('text_testmode');
		$data['text_ach'] = $this->language->get('text_ach');
		$data['text_wait'] = $this->language->get('text_wait');
        $data['entry_ach_tran'] = $this->language->get('entry_ach_tran');
        $data['entry_ach_acct'] = $this->language->get('entry_ach_acct');
        $data['entry_ach_type'] = $this->language->get('entry_ach_type');
		$data['testmode'] = $this->config->get('mes_ach_test');
		
		$this->template = $this->config->get('config_template') . '/template/payment/mes_ach.tpl';

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/mes_ach.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/mes_ach.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/mes_ach.tpl', $data);
		}
	}

	public function send() {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['custom'] = $this->session->data['order_id'];

		if( !isset($this->request->post['transit_num']) || preg_replace("/[^0-9]/", "", $this->request->post['transit_num']) == "" ) {
			$json['error'] = "Routing number is required.";
			$this->response->setOutput(json_encode($json));
		} else if( !isset($this->request->post['account_num']) || preg_replace("/[^0-9]/", "", $this->request->post['account_num']) == "" ) {
			$json['error'] = "Account number is required.";
			$this->response->setOutput(json_encode($json));
		} else {
			// Authentication
			$data['profile_key'] = $this->config->get('mes_ach_profile_key');
			$data['profile_id'] = $this->config->get('mes_ach_profile_id');
			$data['cust_id'] = $this->config->get('mes_ach_cust_id');
			// Order Data
			$data['transaction_type'] = 'H';
			$data['ref_num'] = $order_info['order_id'];
			$data['cust_name'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');;
			$data['auth_type'] = 'WEB';
			$data['ach_request'] = 'SALE';
			$data['invoice_number'] = $order_info['order_id'];
			$data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, FALSE);
			$data['currency_code'] = $order_info['currency_code'];
			// Payment Data
			$data['account_num'] = preg_replace("/[^0-9]/", "", $this->request->post['account_num']);
			$data['transit_num'] = preg_replace("/[^0-9]/", "", $this->request->post['transit_num']);
			$data['account_type'] = $this->request->post['account_type'];
			// Customer Data
			$data['cardholder_first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_street_address'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
			$data['country_code'] = $order_info['payment_iso_code_2'];
			$data['cardholder_email'] = html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_phone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
			$data['account_name'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');
			$data['account_email'] = html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8');
			$data['browser_language'] = html_entity_decode($order_info['language_code'], ENT_QUOTES, 'UTF-8');
			$data['ip_address'] = html_entity_decode($order_info['ip']);
			// Shipping Data
			$data['ship_to_first_name'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_last_name'] = html_entity_decode($order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_address'] = html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_zip'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');
			$data['dest_country_code'] = $order_info['shipping_iso_code_2'];
			if (!$this->config->get('mes_test')) {
				// Production api endpoint
				$api_endpoint = 'https://api.merchante-solutions.com/mes-api/tridentApi';
			} else {
				// Test api endpoint
				$api_endpoint = 'https://cert.cielo-us.com/mes-api/tridentApi';
			}

			$curl = curl_init($api_endpoint);
			curl_setopt_array($curl, array(
				CURLOPT_PORT => 443,
			    CURLOPT_RETURNTRANSFER => 1,
			    CURLOPT_SSL_VERIFYPEER => 0,
			    CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_HEADER => 0,
			    CURLOPT_POST => 1,
			    CURLOPT_POSTFIELDS => http_build_query($data)
			));

			$response = curl_exec($curl);
			curl_close($curl);

			$json = array();
			if (!$response) {
				$this->log->write('MeS ACH PG :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
				$json['error'] = 'Authorization attempt failed.<br />Details: '.curl_error($curl) . '(' . curl_errno($curl) . ')';
				$this->response->setOutput(json_encode($json));
			} else {
				$response_data = array();
				parse_str($response, $response_data);

				if($response_data['error_code'] == '000') {
					$message = '';
					if(isset($response_data['avs_result']))
						$message .= 'AVS Result: ' . $response_data['avs_result'] . "<br />";
					$message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
					$message .= 'Gateway Error Code: ' . $response_data['error_code'] . "<br />";
					$message .= 'Gateway Text Response: ' . $response_data['auth_response_text'] . "<br />";
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('mes_completed_status_id'), $message, TRUE);
					$json['success'] = $this->url->link('checkout/success', '', 'SSL');
					$this->response->setOutput(json_encode($json));
				}	else {
					$json['error'] =  $response_data['error_code'] . ' - ';
					$json['error'] .= '<br />' . 'Transaction Declined. Please provide valid eCheck details.';
					$this->response->setOutput(json_encode($json));
				}
			}
		}
	}
}
?>