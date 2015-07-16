<?php
/**
* Custom Payment Gateway for OpenCart v1.5.6.4
* Using Payment Gateway API v4.12
* For Credit Card Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMes extends Controller {
	protected function index() {
    	$this->language->load('payment/mes');
		$this->data['text_credit_card'] = $this->language->get('text_credit_card');
		$this->data['text_wait'] = $this->language->get('text_wait');
		$this->data['entry_cc_type'] = $this->language->get('entry_cc_type');
		$this->data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$this->data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$this->data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['button_back'] = $this->language->get('button_back');
		$this->id       = 'payment';
		$this->template = $this->config->get('config_template') . '/template/payment/mes.tpl';
		$this->render();
	}

	public function send() {
		$json = array();
		if(!$this->config->get('mes_test')) {
			$api_endpoint = 'https://api.merchante-solutions.com/mes-api/tridentApi';
		} else {
			$api_endpoint = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
		}
		if(!$this->config->get('mes_transaction')) {
			$payment_type = 'P';	
		} else {
			$payment_type = 'D';
		}
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if(!isset($this->request->post['cc_number']) || preg_replace("/[^0-9]/", "", $this->request->post['cc_number']) == "" ) {
			$json['error'] = "Card number is required.";
			$this->response->setOutput(json_encode($json));
		}
		else if(!isset($this->request->post['cc_cvv2']) || preg_replace("/[^0-9]/", "", $this->request->post['cc_cvv2']) == "" ) {
			$json['error'] = "Card Security Code is required.";
			$this->response->setOutput(json_encode($json));
		}
		else {
			$payment_data = array(
				'echo_cart'				=> 'OpenCart_' . VERSION,
				// Authentication
				'profile_key'    		=> $this->config->get('mes_profile_key'),
				'profile_id'     		=> $this->config->get('mes_profile_id'),
				// Card Data
				'card_number'           => preg_replace("/[^0-9]/", "", $this->request->post['cc_number']),
				'card_exp_date'			=> $this->request->post['cc_expire_month'] . $this->request->post['cc_expire_year'],
				'cvv2'           		=> preg_replace("/[^0-9]/", "", $this->request->post['cc_cvv2']),
				// Order data
				'invoice_number' 		=> $order_info['order_id'],
				'transaction_type'  	=> $payment_type,
				'transaction_amount'	=> $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, FALSE),
				'currency_code'			=> $order_info['currency_code'],
				// Customer Data
				'cardholder_first_name'	=> urlencode($order_info['payment_firstname']),
				'cardholder_last_name'	=> urlencode($order_info['payment_lastname']),
				'cardholder_street_address'	=> urlencode($order_info['payment_address_1']),
				'cardholder_zip'		=> urlencode($order_info['payment_postcode']),
				'country_code'			=> urlencode($order_info['payment_iso_code_2']),
				'cardholder_phone'		=> urlencode($order_info['telephone']),
				'cardholder_email'		=> urlencode($order_info['email']),
				'account_name'			=> urlencode($order_info['shipping_firstname']),
				'account_email'			=> urlencode($order_info['email']),
				'ip_address'			=> urlencode($order_info['ip']),
				// Shipping Data
				'ship_to_first_name'	=> urlencode($order_info['shipping_firstname']),
				'ship_to_last_name'		=> urlencode($order_info['shipping_lastname']),
				'ship_to_address'		=> urlencode($order_info['shipping_address_1']),
				'ship_to_zip'			=> urlencode($order_info['shipping_postcode']),
				'dest_country_code'		=> urlencode($order_info['shipping_iso_code_2']),
				// Misc Data
				'browser_language'		=> urlencode($order_info['language_code'])
			);
            // Add Kount extras to request, if customer added merch_id on backend
            if($this->config->get('mes_fraud_status')) {
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
					$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('mes_order_status_id'));
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
					$this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('mes_order_status_id'), $message, FALSE);
					$json['success'] = $this->url->link('checkout/success', '', 'SSL');
					$this->response->setOutput(json_encode($json));
				} elseif($response_data['error_code'] == '244') {
					$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('mes_fraud_status_id'));
					$message = '';
					$message .= 'Transaction ID: ' . $response_data['transaction_id'] . "<br />";
					$message .= 'Gateway Error Code: ' . $response_data['error_code'] . "<br />";
					$message .= 'Gateway Text Response: ' . $response_data['auth_response_text'] . "<br />";
					if(isset($response_data['fraud_result']))
						$message .= 'Fraud Result Response: ' . 'Decline';
					$this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('mes_fraud_status_id'), $message, FALSE);
					$json['error'] =  $response_data['error_code'] . ' - ';
                    if(isset($response_data['fraud_result']))
                    	$json['error'] .= $response_data['fraud_result'];
                    $json['error'] .= '<br />' . 'Transaction Declined. Please contact your account representative for more information.';
                    $this->response->setOutput(json_encode($json));
				} else {					
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