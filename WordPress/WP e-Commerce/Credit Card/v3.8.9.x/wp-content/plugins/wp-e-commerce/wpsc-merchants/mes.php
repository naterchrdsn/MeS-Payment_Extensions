<?php 
/**
 * Main payment module file for Wordpress e-Commerce
 * Written	02/11/2010
 * Updated	08/03/2011 (For WPEC 3.8.6)
 * Updated  04/12/2012 (For WPEC 3.8.7.6.2)
 * Updated 	02/14/2013 (For WPEC 3.8.9.5)
 * ©Merchant e-Solutions 2010
 * 
 * @author 	brice
 * 
 */
 
//error_reporting(E_ALL & ~E_NOTICE);

$nzshpcrt_gateways[$num]['name'] = 'Merchant e-Solutions';
$nzshpcrt_gateways[$num]['submit_function'] = "submit_mes";		// Admin form is submitted
$nzshpcrt_gateways[$num]['api_version'] = 2.0;
$nzshpcrt_gateways[$num]['class_name'] = 'wpsc_merchant_mes';
$nzshpcrt_gateways[$num]['has_recurring_billing'] = false;
$nzshpcrt_gateways[$num]['wp_admin_cannot_cancel'] = false;
$nzshpcrt_gateways[$num]['display_name'] = 'Merchant e-Solutions';
$nzshpcrt_gateways[$num]['image'] = WPSC_URL . '/images/cc.gif';
$nzshpcrt_gateways[$num]['requirements']['php_version'] = 4.3;
$nzshpcrt_gateways[$num]['requirements']['extra_modules'] = array();

$nzshpcrt_gateways[$num]['internalname'] = 'mes';				// Legacy
$nzshpcrt_gateways[$num]['form'] = "form_mes";					// Legacy (display admin form)
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";		// Legacy
//$nzshpcrt_gateways[$num]['function'] = 'gateway_mes';			// Legacy

/**
 * Merchant Class
 */
class wpsc_merchant_mes extends wpsc_merchant {
	
	/**
	 * construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	 * @access public
	 */
	function construct_value_array() {
		$data = array( );
		$data['profile_id'] = get_option('mes_profile_id');
		$data['profile_key'] = get_option('mes_profile_key');
		$data['transaction_type'] = get_option('mes_tran_type');
		$data['currency_code'] = $this->cart_data['store_currency'];
		$data['country_code'] = $this->cart_data['store_currency'];
		$data['invoice_number'] = $this->cart_data['session_id'];
		$data['ip_address'] = $_SERVER["REMOTE_ADDR"];
		
		// Billing Data
		if(isset($this->cart_data['billing_address']['first_name'])) $data['cardholder_first_name'] = $this->cart_data['billing_address']['first_name'];
		if(isset($this->cart_data['billing_address']['last_name'])) $data['cardholder_last_name'] = $this->cart_data['billing_address']['last_name'];
		if(isset($this->cart_data['email_address'])) $data['cardholder_email'] = $this->cart_data['email_address'];
		if(isset($this->cart_data['billing_address']['address'])) $data['cardholder_street_address'] = $this->cart_data['billing_address']['address'];
		if(isset($this->cart_data['billing_address']['post_code'])) $data['cardholder_zip'] = $this->cart_data['billing_address']['post_code'];
		if(isset($this->cart_data['billing_address']['phone'])) $data['cardholder_phone'] = $this->cart_data['billing_address']['phone'];

		// Shipping Data
		if(isset($this->cart_data['shipping_address']['first_name'])) $data['ship_to_first_name'] = $this->cart_data['shipping_address']['first_name'];
		if(isset($this->cart_data['shipping_address']['last_name'])) $data['ship_to_last_name'] = $this->cart_data['shipping_address']['last_name'];
		if(isset($this->cart_data['shipping_address']['last_name'])) $data['ship_to_zip'] = $this->cart_data['shipping_address']['post_code'];
		if(isset($this->cart_data['shipping_address']['address'])) $data['ship_to_address'] = $this->cart_data['shipping_address']['address'];
		if(isset($this->cart_data['shipping_address']['country'])) $data['dest_country_code'] = $this->cart_data['shipping_address']['country'];

		// Credit Card Data
		$data['card_number'] = $_POST['card_number'];
		$data['card_exp_date'] = $_POST['exp_month'] . $_POST['exp_year'];
		$data['cvv2'] = $_POST['card_code'];
		
		// Misc Data
		$data['tax_amount'] = wpsc_tax_isincluded() ? 0 : $this->cart_data['cart_tax'];
		$data['transaction_amount'] = $this->cart_data['total_price'];
		$data['account_name'] = $this->cart_data['email_address'];
		$data['account_email'] = $this->cart_data['email_address'];
		
		$data['digital_goods'] = "false";
		$data['subscription'] = "false";
		foreach($this->cart_items as $item) {
			if($item['is_downloadable'])
				$data['digital_goods'] = "true";
			if($item['is_subscription'])
				$data['subscription'] = "true";
		}
		$data['device_id'] = $_POST['mes_device_id'];
		
		$this->collected_gateway_data = $data;
	}
	
	/**
	 * submit method, sends the received data to the payment gateway
	 * @access public
	 */
	function submit() {
		global $wpdb;
		// For testing different HTTP codes
		//"http://httpstat.us/404";
		//"http://httpstat.us/403";
		//"http://httpstat.us/500";
		//"http://httpstat.us/503";
		
		if (get_option('mes_testmode') == "on")
			$url = "http://cert.merchante-solutions.com/mes-api/tridentApi"; // Sandbox testing
		else
			$url = "https://api.merchante-solutions.com/mes-api/tridentApi"; // Live
		
		// Set cURL options
		$options = array(
			'timeout' => 30,
			'body' => $this->collected_gateway_data,
			'user-agent' => $this->cart_data['software_name'] . " " . get_bloginfo( 'url' ),
			'sslverify' => false,
		);
		
		// Post request
		$response = wp_remote_post( $url, $options );
		
		// Check for cURL error
		if($response->errors != null) {
			if( array_key_exists('http_request_failed', $response->errors) ) {
				foreach($response->errors['http_request_failed'] as $error)
					$msg .= $error;
				$this->set_error_message($msg);
				$this->return_to_checkout();
			}
		}
		
		// Check for HTTP code other than 200
		if( $response['response']['code'] != 200 ) {
			$this->set_error_message("Bad HTTP code recieved from gateway request:<br />".$response['response']['code']." ".$response['response']['message']."<br />Please contact us to complete the order, or try again shortly.");
			$this->return_to_checkout();
		}
		
		// Parse response body
		$mesResponse = array();
		$exp = explode("&", $response['body']);
		foreach($exp as $row) {
			$npv = explode("=", $row);
			$mesResponse[$npv[0]] = $npv[1];
		}
		
		// Nowhere else to put gateway results, but in the notes.
		$note  = "MeS Payment Gateway\n";
		$note .= "Responst Text: ".$mesResponse['auth_response_text']."\n";
		$note .= "Transaction ID: ".$mesResponse['transaction_id']."\n";
		$note .= "AVS Response: ".$mesResponse['avs_result']."\n";
		$note .= "CVV Response: ".$mesResponse['cvv2_result'];
		
		$sql = "UPDATE ".WPSC_TABLE_PURCHASE_LOGS." SET notes='".$note."' WHERE sessionid=".$this->cart_data['session_id'];
		$wpdb->query($sql);
		
		// Check for decline
		if($mesResponse['error_code'] != '000' && $mesResponse['error_code'] != '085') {
			// Some customized error messages
			switch($mesResponse['error_code']) {
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
			$this->set_error_message("Your transaction was not accepted for the following reason<br />".$msg);
			$this->set_transaction_details( $mesResponse['transaction_id'], 6 );
			$this->return_to_checkout();
		}
		else {
			$this->set_transaction_details( $mesResponse['transaction_id'], 3 );
			$this->go_to_transaction_results( $this->cart_data['session_id'] );
		}
		
	}
	
	/**
	 * parse_gateway_notification method, receives data from the payment gateway
	 * @access private
	 */
	function parse_gateway_notification() {
		die("dead parse_gateway_notification");
	}
	
	function process_gateway_notification() {
		die("dead process_gateway_notification");
	}
}

function submit_mes() {
	if($_POST['mes']['profile_id'] != null)
		update_option('mes_profile_id', $_POST['mes']['profile_id']);
	if($_POST['mes']['profile_key'] != null)
		update_option('mes_profile_key', $_POST['mes']['profile_key']);
	if($_POST['mes']['testmode'] != null)
		update_option('mes_testmode', $_POST['mes']['testmode']);
	if($_POST['mes']['tran_type'] != null)
		update_option('mes_tran_type', $_POST['mes']['tran_type']);
	return true;
}

function form_mes() {
	if(get_option('mes_testmode') == "on")
		$selected = 'checked="checked"';
	else
		$selected = '';

	get_option('mes_tran_type') == "D" ? $D = " selected" : $P = " selected"; //Sale or Pre-Auth?
	$output = '
<tr>
	<td>
		<label for="mes_profile_id">'.__('Profile ID:').'</label>
	</td>
	<td>
		<input type="text" name="mes[profile_id]" id="mes_profile_id" value="'.get_option("mes_profile_id").'" size="20" />
	</td>
</tr>
<tr>
	<td>
		<label for="mes_profile_key">'.__('Profile Key:').'</label>
	</td>
	<td>
		<input type="text" name="mes[profile_key]" id="mes_profile_key" value="'.get_option('mes_profile_key').'" size="32" />
	</td>
</tr>
<tr>
	<td>
		<label for="mes_tran_type">'.__('Transaction Type:').'</label>
	</td>
	<td>
	    <select name="mes[tran_type]" id="mes_tran_type">
			<option value="D"'.$D.'>Sale</option>
			<option value="P"'.$P.'>Pre-Authorization</option>
		</select>
		<br /><span style="font-size: 9px;">Pre-auths must be settled in the MeS Back Office.</span>
	</td>
</tr>
<tr>
	<td>
		<label for="mes_testmode">'.__('Test Mode Enabled:').'</label>
	</td>
	<td>
		<input type="hidden" name="mes[testmode]" value="off" /><input type="checkbox" name="mes[testmode]" id="mes_testmode" value="on" '.$selected.' />					
		<br /><span style="font-size: 9px;">Test Mode Issues fake, simulated transactions (use CVV 123 and any real card number for approvals)
		<br />Transactions in Test Mode are <b>not live</b> and will not be funded.</span>
	</td>
</tr>';
	return $output;
}



if(in_array('mes',(array)get_option('custom_gateway_options')))
{	

	//$warning = "style='color: red'";
	
	if($_POST['card_number'] == "")
		$numberWarning = $warning;
	if($_POST['card_code'] == "")
		$cvvWarning = $warning;
	
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td class='wpsc_CC_details'>Credit Card Number <span class='asterix'>*</span></td>
		<td>
			<input type='text' value='' name='card_number' autocomplete='off' />
		</td>
	</tr>
	<tr>
		<td>Card Expiry Date <span class='asterix'>*</span></td>
		<td>
		    <select name='exp_month'>
			  <option value='01'>Jan</option>
			  <option value='02'>Feb</option>
			  <option value='03'>Mar</option>
			  <option value='04'>Apr</option>
			  <option value='05'>May</option>
			  <option value='06'>Jun</option>
			  <option value='07'>Jul</option>
			  <option value='08'>Aug</option>
			  <option value='09'>Sep</option>
			  <option value='10'>Oct</option>
			  <option value='11'>Nov</option>
			  <option value='12'>Dec</option>
			</select>
			<select name='exp_year'>";
			
			$year = date('y');
			for($i=0; $i<12; $i++)
				$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= "<option value='".($year+$i)."'>20".($year+$i)."</option>";

$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= "
			</select>
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>CVV <span class='asterix'>*</span></td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' />
		</td>
	</tr>
	<tr>
		<td>
			<input type='hidden' id='mes_device_id' name='mes_device_id'/>
			<script type=\"text/javascript\" src=\"https://ds.bluecava.com/V50/LD/BCLD5.js\"></script>
			<script type=\"text/javascript\">
				BCLD.getSnapshot(successFunc, errorFunc);
				function successFunc(fp, warningMessage) {
					var fingerprintData = document.getElementById('mes_device_id');
					fingerprintData.value = fp;
				}
				function errorFunc(errorMessage) {
					// no error handling
				}
			</script>
			<div id='BCLDGuidDiv' style='border: 0px; width: 0px; height: 0px;'></div>
			<div id='BCLDflashplayer' style='border: 0px; width: 0px; height: 0px;'></div>
		</td>
	</tr>
";
}

?>