<?php
//  Date:	02/11/2010
//  File:	process.php
//  Author:	b.rice
//	Desc:	Display Payment page, run transactions, log results, and complete the order.
//  ©Merchant e-Solutions 2010


$useSSL = true;

include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/mes.php');
include_once(dirname(__FILE__).'/trident_gateway.inc');

$errors = array();
global $cart;
global $cookie;

if($_POST) // Run transaction
{
  Configuration::get('MES_CVVFLAG') == "yes" ? $require_cvv = TRUE : $require_cvv = FALSE;
  
  if( ($require_cvv && empty($_POST['cvv2'])) || empty($_POST['card_number']) )
  {
    header("Location: process.php?error=1");						//Card number is required; cvv2 may be required in admin settings.
  }
  
  //**** All this junk just to get billing address & zip
  $customer = new Customer(intval($cookie->id_customer));			//Get customer data
  $addresses = $customer->getAddresses(intval($cookie->id_lang));	//Load all addresses for this customer
  $billing_address_id = intval($cart->id_address_invoice);			//Get which address was selected for billing
  $billing_info = array();

  foreach($addresses as $address)									//Look for the billing address in available saved addresses
  {
    if($address['id_address'] == $billing_address_id)
      $billing_info = $address;
  }
  //****
	
  $api_url = Configuration::get('MES_APIURL');
  $transaction_type = Configuration::get('MES_TRANSACTIONTYPE');
  $profile_id = Configuration::get('MES_PROFILEID');
  $profile_key = Configuration::get('MES_PROFILEKEY');
  $transaction_amount = number_format(floatval($cart->getOrderTotal(true, 3)), 2, '.', '');
  $card_number = str_replace(" ", "", $_POST['card_number']);
  $card_exp_date = $_POST['MM'] . "/" . $_POST['YY'];
  
  if($transaction_type == "D")
    $transaction = new TpgSale($profile_id, $profile_key);
  else
    $transaction = new TpgPreAuth($profile_id, $profile_key);
  
  $transaction->setHost("https://".$api_url."/mes-api/tridentApi");
  $transaction->setAvsRequest($billing_info['address1'], $billing_info['postcode']);
  $transaction->setRequestField("invoice_number", intval($cart->id));
  if(!empty($_POST['cvv2']))
    $transaction->setRequestField("cvv2", trim($_POST['cvv2']));
  $transaction->setTransactionData($card_number, $card_exp_date, $transaction_amount);
  
  $transaction->execute();

  switch ($transaction->ResponseFields['error_code'])
  {
    case '000':
	  $id_order_state = _PS_OS_PAYMENT_;
	  break;
	default:
	  $id_order_state = _PS_OS_ERROR_;
  }

  if($transaction->ResponseFields['error_code'] == "000") //Approval
  {
    $mail_vars = array(
      '{transaction_id}' => $transaction->ResponseFields['transaction_id'],
	  '{auth_code}' => $transaction->ResponseFields['auth_code']
    );

    $admin_note =  "MeS Payment Gateway Response<br />";
    $admin_note .= "Error Code: ".$transaction->ResponseFields['error_code']."<br />";
    $admin_note .= "Approval Code: ".$transaction->ResponseFields['auth_code']."<br />";
    $admin_note .= "Transaction ID: ".$transaction->ResponseFields['transaction_id']."<br />";
    $admin_note .= "AVS Result: ".$transaction->ResponseFields['avs_result']."<br />";
    $admin_note .= "CVV Result: ".$transaction->ResponseFields['cvv2_result'];

    $mes = new mes();
	
    $mes->validateOrder($cart->id, _PS_OS_PAYMENT_, $transaction_amount, "Merchant e-Solutions", $admin_note, $mail_vars, $cookie->id_currency);
	$order = new Order($mes->currentOrder);

	Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$mes->id.'&id_order='.$mes->currentOrder.'&key='.$order->secure_key);
  }
  else //Decline
  {
    $error = "Your transaction was declined for the following reason:<br />";
    $error .= $transaction->ResponseFields['auth_response_text'];
    include(dirname(__FILE__).'/../../header.php');
    include(dirname(__FILE__).'/details.php');
    include(dirname(__FILE__).'/../../footer.php');
  }

}
else //Show Payment Page
{
  if($_GET['error'])
    $error = "Please fill in all required fields.";
  include(dirname(__FILE__).'/../../header.php');
  include(dirname(__FILE__).'/details.php');
  include(dirname(__FILE__).'/../../footer.php');
}
