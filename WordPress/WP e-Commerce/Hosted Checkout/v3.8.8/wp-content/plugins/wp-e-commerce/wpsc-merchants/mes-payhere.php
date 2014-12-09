<?php
/**
 * Main payment module file for Wordpress e-Commerce
 * Written  05/10/2012 (For WPEC 3.8.7.6.2)
 * ©Merchant e-Solutions 2012
 * 
 * @author 	brice
 * 
 */
 
$nzshpcrt_gateways[$num]['name'] = 'Merchant e-Solutions PayHere';
$nzshpcrt_gateways[$num]['submit_function'] = "submit_payhere";		// Admin form is submitted
$nzshpcrt_gateways[$num]['api_version'] = 2.0;
$nzshpcrt_gateways[$num]['class_name'] = 'wpsc_merchant_payhere';
$nzshpcrt_gateways[$num]['has_recurring_billing'] = false;
$nzshpcrt_gateways[$num]['wp_admin_cannot_cancel'] = false;
$nzshpcrt_gateways[$num]['display_name'] = 'payhere';
$nzshpcrt_gateways[$num]['image'] = WPSC_URL . '/images/cc.gif';
$nzshpcrt_gateways[$num]['requirements']['php_version'] = 4.3;
$nzshpcrt_gateways[$num]['requirements']['extra_modules'] = array();
$nzshpcrt_gateways[$num]['form'] = "form_payhere";					// Legacy (display admin form)
$nzshpcrt_gateways[$num]['internalname'] = 'payhere';				// Legacy
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";			// Legacy

class wpsc_merchant_payhere extends wpsc_merchant {
	var $response_values = array();

	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access public
	*/
	function construct_value_array() {
		$data = array( );
		$data['profile_id'] = get_option('payhere_profile_id');
		$data['transaction_type'] = 'D';
		$data['currency_code'] = translateCurrencyCode($this->cart_data['store_currency']);
		$data['invoice_number'] = $this->cart_data['session_id'];
		$data['client_reference_number'] = $this->cart_data['software_name'];
		
		// Have to reconstruct the results URL because PayHere requires an endpoint (index.php here) to be present for POST data to return.
		$data['return_url'] = get_option('siteurl')."/index.php".basename($this->cart_data['shopping_cart_url']);
		$data['return_url'] = add_query_arg('gateway', 'payhere', $data['return_url']);						// necessary for process_gateway_notification.
		$data['return_url'] = add_query_arg('wpsc_action', 'gateway_notification', $data['return_url']);	// necessary for process_gateway_notification.
		$data['cancel_url'] = $data['return_url'];	// Cancel to the cart page, with message
		$data['use_merch_receipt'] = 'Y';			// Skip hosted receipt page
		if(get_option('payhere_email_merchant') != "")
			$data['merchant_email'] = get_option('payhere_email_merchant');
		
		// Billing Data
		$data['cardholder_first_name'] = $this->cart_data['billing_address']['first_name'];
		$data['cardholder_last_name'] = $this->cart_data['billing_address']['last_name'];
		if(get_option('payhere_email_customer') == "on")
			$data['customer_email'] = $this->cart_data['email_address'];
		$data['cardholder_street_address'] = $this->cart_data['billing_address']['address'];
		$data['cardholder_zip'] = $this->cart_data['billing_address']['post_code'];
		
		// Shipping Data
		$data['ship_to_first_name'] = $this->cart_data['shipping_address']['first_name'];
		$data['ship_to_last_name'] = $this->cart_data['shipping_address']['last_name'];
		$data['ship_to_zip'] = $this->cart_data['shipping_address']['post_code'];
		
		// Transaction Data
		$data['transaction_amount'] = number_format(sprintf("%01.2f", $this->cart_data['total_price']), 2, '.', '');
		$data['transaction_key'] = md5(get_option('payhere_profile_key').get_option('payhere_security_code').$data['transaction_amount']);
		
		$this->collected_gateway_data = $data;
	}

	/**
	* submit method, sends the received data to the payment gateway
	* @access public
	*/
	function submit() {
		$name_value_pairs = array();
		foreach ($this->collected_gateway_data as $key => $value) {
			$name_value_pairs[] = $key . '=' . urlencode($value);
		}
		$payhere_values =  implode('&', $name_value_pairs);
		
		if (get_option('payhere_testmode') == "on")
			$url = "https://test.merchante-solutions.com/jsp/tpg/secure_checkout.jsp"; // Sandbox testing
		else
			$url = "https://www.merchante-solutions.com/jsp/tpg/secure_checkout.jsp"; // Live
		
		$url = $url."?".$payhere_values;
		wp_redirect($url);
		exit();
	}


	/**
	* parse_gateway_notification method, receives data from the payment gateway
	* @access private
	*/
	function parse_gateway_notification() {
		// Parse response
		$received_values = stripslashes_deep ($_POST);
		$this->response_values = $received_values;
		$this->session_id = $mesResponse['eresp_sessionid'];
	}

	/**
	* process_gateway_notification method, receives data from the payment gateway
	* @access public
	*/
	function process_gateway_notification() {
		global $wpdb;
		
		// Nowhere else to put gateway results, but in the notes.
		$note  = "\n--MeS PayHere Data--\n";
		$note .= "Responst Text: ".$this->response_values['resp_text']."\n";
		$note .= "Transaction ID: ".$this->response_values['tran_id']."\n";
		$note .= "Auth Code: ".$this->response_values['auth_code']."\n";
		$note .= "Card Type: ".$this->response_values['card_type']."\n";
		$note .= "Truncated Number: ".$this->response_values['acct_number']."\n";
		$note .= "Gateway Timestamp: ".$this->response_values['tran_date'];
		
		// Check for Approval
		if($this->response_values['resp_code'] == '000') {
			// Update order with notes and approval status
			$sql = "UPDATE ".WPSC_TABLE_PURCHASE_LOGS." SET notes='".$note."', processed='3' WHERE sessionid=".$this->response_values['invoice_number'];
			$wpdb->query($sql);
			$this->go_to_transaction_results( $this->response_values['invoice_number'] );
			
		}
		// Check for decline. PayHere should technically never return a non-000 error code at this time (possible future capability).
		else if(isset($this->response_values['resp_code'])) {
			switch($this->response_values['resp_code']) {
			case '0N7':
				$msg = "The CVV from your card did not match.";
				break;
			case '210': // Approved but threw a AVS filter which killed it.
				$msg = "Address and/or ZIP code did not match.";
				break;
			case '211': // Approved with no CVV match & filter killed it.
				$msg = "The CVV from your card did not match.";
				break;
			default:
				$msg = "Declined";
			}
			
			// Update order with notes and declined payment status
			$sql = "UPDATE ".WPSC_TABLE_PURCHASE_LOGS." SET notes='".$note."', processed='6' WHERE sessionid=".$this->response_values['invoice_number'];
			$wpdb->query($sql);
			$this->set_error_message("Your transaction was not accepted for the following reason<br />".$msg);
			$this->return_to_checkout();
		}
		// Check for resp_text in GET, meaning a configuration error
		else if(isset($_GET['resp_text'])) {
			$this->set_error_message("There was a security error while processing the transaction. Please contact the website administrator.<br />Response: ".$_GET['resp_text']);
			$this->return_to_checkout();
		}
		// Anything else lands here, such as cancelling a payment.
		else {
			$this->set_error_message("Transaction cancelled.<br />You may add to, or empty your cart.");
			$this->return_to_checkout();
		}
		
	}
	
}

/*
 * When admin form is submitted, these fields must be saved.
 */
function submit_payhere() {
	update_option('payhere_profile_id', $_POST['payhere']['profile_id']);
	update_option('payhere_security_code', $_POST['payhere']['security_code']);
	update_option('payhere_profile_key', $_POST['payhere']['profile_key']);
	update_option('payhere_email_merchant', $_POST['payhere']['email_merchant']);
	update_option('payhere_email_customer', $_POST['payhere']['email_customer']);
	update_option('payhere_testmode', $_POST['payhere']['testmode']);
	return true;
}  

/*
 * Admin form
 */
function form_payhere() {
	if(get_option('payhere_testmode') == "on")
		$test_selected = 'checked="checked"';
	else
		$test_selected = '';
		
	if(get_option('payhere_email_customer') == "on")
		$email_selected = 'checked="checked"';
	else
		$email_selected = '';

	get_option('payhere_tran_type') == "D" ? $D = " selected" : $P = " selected"; //Sale or Pre-Auth?
	$output = '

<tr>
	<td colspan="2">
		<h4>Security Settings</h4>
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_profile_id">'.__('Profile ID:').'</label>
	</td>
	<td>
		<input type="text" name="payhere[profile_id]" id="payhere_profile_id" value="'.get_option("payhere_profile_id").'" size="20" />
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_security_code">'.__('Security Code:').'</label>
	</td>
	<td>
		<input type="text" name="payhere[security_code]" id="payhere_security_code" value="'.get_option("payhere_security_code").'" size="20" />
		<br /><span style="font-size: 9px;">(Optional) If you entered a Security Code into the PayHere Web Administration, place the same password here. Using a Security Code helps secure the outgoing transactions.</span>
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_profile_key">'.__('Profile Key:').'</label>
	</td>
	<td>
		<input type="text" name="payhere[profile_key]" id="payhere_profile_key" value="'.get_option("payhere_profile_key").'" size="20" />
		<br /><span style="font-size: 9px;">(Optional) Enter the Profile Key associated with this Profile ID. This is only required if using the Security Code.</span>
	</td>
</tr>


<tr>
	<td colspan="2">
		<h4>Misc Options</h4>
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_email_merchant">'.__('Email Merchant Notification:').'</label>
	</td>
	<td>
		<input type="text" name="payhere[email_merchant]" id="payhere_email_merchant" value="'.get_option("payhere_email_merchant").'" size="20" />
		<br /><span style="font-size: 9px;">Enter an address if you wish to recieve an email each time an order is made.</span>
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_email_customer">'.__('Email Customer Receipt:').'</label>
	</td>
	<td>
		<input type="hidden" name="payhere[email_customer]" value="off" /><input type="checkbox" name="payhere[email_customer]" id="payhere_email_customer" value="on" '.$email_selected.' />
		<br /><span style="font-size: 9px;">Check to automatically send an email receipt to the cardholder upon purchase.</span>
	</td>
</tr>

<tr>
	<td>
		<label for="payhere_testmode">'.__('Test Mode Enabled:').'</label>
	</td>
	<td>
		<input type="hidden" name="payhere[testmode]" value="off" /><input type="checkbox" name="payhere[testmode]" id="payhere_testmode" value="on" '.$test_selected.' />
		<br /><span style="font-size: 9px;">Test Mode Issues fake, simulated transactions (use CVV 123 and any real card number for approvals)
		<br />Transactions in Test Mode are <b>not live</b> and will not be funded.</span>
	</td>
</tr>';
	return $output;
}

/*
 * Convert ISO Alpha currency codes to ISO numeric currency codes.
 */
function translateCurrencyCode($alphaCode) {
	switch($alphaCode) {
	case "USD": return 840;
	case "GBP": return 846;
	case "EUR": return 978;
	case "CAD": return 124;
	case "AUD": return 036;
	default: return "";
	}
}
?>
