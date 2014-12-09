<?php
/**
* Custom Payment Gateway for OpenCart v2
* Using Payment Gateway API v4.12
* For Credit Card Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMes extends Controller {
	public function index() {
    	$this->load->language('payment/mes');
		$data['text_testmode'] = $this->language->get('text_testmode');
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['text_wait'] = $this->language->get('text_wait');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
		if ($this->config->get('mes_merch_id'))
			$data['merch_id'] = $this->config->get('mes_merch_id');

		if ($this->config->get('mes_test')) {
			$data['testmode'] = TRUE;
		}

		$this->template = $this->config->get('config_template') . '/template/payment/mes.tpl';

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/mes.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/mes.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/mes.tpl', $data);
		}
	}

	public function send() {
		$this->load->model('checkout/order');
		if (!$this->config->get('mes_transaction')) {
			$payment_type = 'P';	
		} else {
			$payment_type = 'D';
		}

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['custom'] = $this->session->data['order_id'];

		if( !isset($this->request->post['cc_number']) || preg_replace("/[^0-9]/", "", $this->request->post['cc_number']) == "" ) {
			$json['error'] = "Card number is required.";
			$this->response->setOutput(json_encode($json));
		} else if( !isset($this->request->post['cc_cvv2']) || preg_replace("/[^0-9]/", "", $this->request->post['cc_cvv2']) == "" ) {
			$json['error'] = "Card Security Code is required.";
			$this->response->setOutput(json_encode($json));
		} else {
			if(count($this->cart->getProducts()) === 1) {
				$product_data = '';
				foreach ($this->cart->getProducts() as $product) {
					$product_data = $product['name'].'<|>'.$product['model'].'<|>'.$product['product_id'].'<|>'.$product['quantity'].'<|>'.$product['price'].'<|>';
				};
				$data['line_item'] = $product_data;
			}else {
				$product_data = array();
				foreach ($this->cart->getProducts() as $product) {
					$product_data[] = $product['name'].'<|>'.$product['model'].'<|>'.$product['product_id'].'<|>'.$product['quantity'].'<|>'.$product['price'].'<|>';
				};
				$data['line_item'] = implode('&', $product_data);
			}
			// Authentication
			$data['profile_key'] = $this->config->get('mes_profile_key');
			$data['profile_id'] = $this->config->get('mes_profile_id');
			// Customer Data
			$data['cardholder_first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_street_address'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
			$data['cardholder_zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
			$data['country_code'] = $order_info['payment_iso_code_2'];
			$data['cardholder_email'] = $order_info['email'];
			$data['cardholder_phone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
			$data['account_name'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');
			$data['account_email'] = html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8');
			$data['ip_address'] = html_entity_decode($order_info['ip'], ENT_QUOTES, 'UTF-8');

			$data['browser_language'] = html_entity_decode($order_info['language_code'], ENT_QUOTES, 'UTF-8');
			// Shipping Data
			$data['ship_to_first_name'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_last_name'] = html_entity_decode($order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_address'] = html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8');
			$data['ship_to_zip'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');
			$data['dest_country_code'] = $order_info['shipping_iso_code_2'];
			// Order Data
			$data['transaction_type'] = $payment_type;
			$data['invoice_number'] = $order_info['order_id'];
			$data['transaction_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, FALSE);
			$data['currency_code'] = $order_info['currency_code'];
			// Payment Data
			$data['card_number'] = preg_replace("/[^0-9]/", "", $this->request->post['cc_number']);
			$data['card_exp_date']	= $this->request->post['cc_expire_date'];
			$data['cvv2'] = preg_replace("/[^0-9]/", "", $this->request->post['cc_cvv2']);
			// Add Kount extras to request, if customer added merch_id on backend
            if ($this->config->get('mes_merch_id')) {
                $data['device_id'] = $this->request->post['device_id'];
            };

			if (!$this->config->get('mes_test')) {
				$api_endpoint = 'https://api.merchante-solutions.com/mes-api/tridentApi';
			} else {
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
				$this->log->write('MeS PG :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
				$json['error'] = 'Authorization attempt failed.<br />Details: '.curl_error($curl) . '(' . curl_errno($curl) . ')';
				$this->response->setOutput(json_encode($json));
			} else {
				$response_data = array();
				parse_str($response, $response_data);

				if($response_data['error_code'] == '000') {
					$message = '';
					if(isset($response_data['avs_result']))
						$message .= 'AVS Result: ' . $response_data['avs_result'] . "<br />";
					if(isset($response_data['cvv2_result']))
						$message .= 'Cvv Result: ' . $response_data['cvv2_result'] . "<br />";
					$message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
					if(isset($response_data['auth_code']))
						$message .= 'Approval Code: ' . $response_data['auth_code'] . "<br />";
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
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('mes_completed_status_id'), $message, TRUE);
					$json['success'] = $this->url->link('checkout/success', '', 'SSL');
					$this->response->setOutput(json_encode($json));
				} elseif($response_data['error_code'] == '244') {
					$message = '';
					$message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
					$message .= 'Gateway Error Code: ' . $response_data['error_code'] . "<br />";
					$message .= 'Gateway Text Response: ' . $response_data['auth_response_text'] . "<br />";
					if(isset($response_data['fraud_result']))
						$message .= 'Fraud Result Response: ' . 'Decline';	
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('mes_ach_review_status_id'), $message, FALSE);
					$json['error'] =  $response_data['error_code'] . ' - ';
					if(isset($response_data['fraud_result']))
						$json['error'] .= $response_data['fraud_result'];
					$json['error'] .= '<br />' . 'Transaction Declined. Please contact your account representative for more information.';
					$this->response->setOutput(json_encode($json));
				}	else {
					$json['error'] =  $response_data['error_code'] . ' - ';
					if(isset($response_data['fraud_result']))
						$json['error'] .= $response_data['fraud_result'];
					$json['error'] .= '<br />' . 'Transaction Declined. Please provide a valid credit card.';
					$this->response->setOutput(json_encode($json));
				}
			}
		}
	}
}
?>