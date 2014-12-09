<?php 
/**
 * Main payment module file for Wordpress e-Commerce
 * Written	02/11/2010
 * Updated	08/03/2011 (Updated for EPEC 3.8.6)
 * ©Merchant e-Solutions 2010
 * 
 * @author 	brice
 * 
 */
 
//error_reporting(E_ALL & ~E_NOTICE);
include("mes/trident_gateway.inc");

$nzshpcrt_gateways[$num]['name'] = 'Merchant e-Solutions';
$nzshpcrt_gateways[$num]['internalname'] = 'mes';
$nzshpcrt_gateways[$num]['function'] = 'gateway_mes';
$nzshpcrt_gateways[$num]['form'] = "form_mes";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_mes";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

if(in_array('mes',(array)get_option('custom_gateway_options')))
{	
	$warning = "style='color: red'";
	
	if($_POST['card_number'] == "")
		$numberWarning = $warning;
	if($_POST['card_code'] == "")
		$cvvWarning = $warning;
		
		
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr ".$numberWarning.">
		<td>Credit Card Number *</td>
		<td>
			<input type='text' value='' name='card_number' autocomplete='off' />
		</td>
	</tr>
	<tr>
		<td>Card Expiry Month *</td>
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
		</td>
	</tr>
	<tr>
		<td>Card Expiry Year *</td>
		<td>
		    <select name='exp_year'>
			  <option value='10'>2010</option>
			  <option value='11'>2011</option>
			  <option value='12'>2012</option>
			  <option value='13'>2013</option>
			  <option value='14'>2014</option>
			  <option value='15'>2015</option>
			  <option value='16'>2016</option>
			  <option value='17'>2017</option>
			  <option value='18'>2018</option>
			  <option value='19'>2019</option>
			  <option value='20'>2020</option>
			  <option value='21'>2021</option>
			</select>
		</td>
	</tr>
	<tr ".$cvvWarning.">
		<td>CVV *</td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' />
		</td>
	</tr>
";
}
  
function gateway_mes($seperator, $sessionid)
{
	global $wpdb, $wpsc_cart;
	$purchase_log = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1",ARRAY_A) ;
	$usersql = "SELECT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value, `".WPSC_TABLE_CHECKOUT_FORMS."`.`name`, `".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN `".WPSC_TABLE_SUBMITED_FORM_DATA."` ON `".WPSC_TABLE_CHECKOUT_FORMS."`.id = `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE  `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id']." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`order`";
	$userinfo = $wpdb->get_results($usersql, ARRAY_A);

	if (get_option('mes_testmode') == "on")
		$url = "https://cert.merchante-solutions.com/mes-api/tridentApi"; // Sandbox testing
	else
		$url = "https://api.merchante-solutions.com/mes-api/tridentApi"; // Live

	if(get_option('mes_tran_type') == "D") //Sale
		$tran = new TpgSale(get_option('mes_profile_id'), get_option('mes_profile_key'));
	else if(get_option('mes_tran_type') == "P") //Pre-Auth
		$tran = new TpgPreAuth(get_option('mes_profile_id'), get_option('mes_profile_key'));
	else
	{
		$_SESSION['wpsc_checkout_misc_error_messages'][] = __("Invalid admin setting for: <br />Transaction Type<br />Please contact the store admin to complete your order.", 'wpsc');
		$_SESSION['mes'] = 'fail';
	}
	
    $tran->setAvsRequest( $userinfo[2]['value'], $userinfo[5]['value'] );
    $tran->setRequestField('cvv2', $_POST['card_code']);	
    $tran->setRequestField('invoice_number',$purchase_log['id']);
    $tran->setRequestField('tax_amount',number_format($wpsc_cart->total_tax, 2));

    $tran->setTransactionData( $_POST['card_number'], $_POST['exp_month'] . "/" . $_POST['exp_year'], number_format($wpsc_cart->total_price,2) );
    $tran->setHost( $url );

    $tran->execute();
	$response = $tran->ResponseFields;
	
	if($response['error_code'] == '000' || $response['error_code'] == '085')
	{
		//redirect to transaction page and store in DB as a order with accepted payment
		$note  = "MeS Payment Gateway\n";
		$note .= "Responst Text: ".$response['auth_response_text']."\n";
		$note .= "Transaction ID: ".$response['transaction_id']."\n";
		$note .= "AVS Response: ".$response['avs_result']."\n";
		$note .= "CVV Response: ".$response['cvv2_result'];
		
		$sql = "UPDATE ".WPSC_TABLE_PURCHASE_LOGS." SET processed= '2', notes='".$note."' WHERE sessionid=".$sessionid;
		$wpdb->query($sql);

		$transact_url = get_option('transact_url');
		unset($_SESSION['WpscGatewayErrorMessage']);
		$_SESSION['mes'] = 'success';
		header("Location: ".get_option('transact_url').$seperator."sessionid=".$sessionid);
		exit();
	}
	else
	{
		//redirect back to checkout page with errors
		$sql = "UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid;
		$wpdb->query($sql);
		$transact_url = get_option('checkout_url');
		$_SESSION['wpsc_checkout_misc_error_messages'][] = __("Your transaction was declined for the following reason:<br />".$response['auth_response_text'], 'wpsc');
		$_SESSION['mes'] = 'fail';
	}

}

function submit_mes()
{

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

function form_mes(){
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
?>