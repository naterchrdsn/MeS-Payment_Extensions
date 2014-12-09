<?php
include("trident_gateway.inc");

/*
 * CubeCart Payment Module for MeS Payment Gateway
 * Copyright (c) 2010 Merchant e-Solutions
 * All rights reserved.
 * Author: Ben Rice <brice@merchante-solutions.com>
 */
 
if (!defined('CC_INI_SET')) die("Access Denied");
if(!detectSSL() && !$module['testMode']) die ("<strong>Critical Error:</strong> This page can ONLY be viewed under SSL!");

if($_GET['process']==1) {

	$transData['customer_id'] = $orderSum["customer_id"];
	$transData['order_id'] = $orderSum['cart_order_id'];
	$transData['amount'] = $orderSum['prod_total'];
	$transData['gateway'] = "Merchant e-Solutions";

	require("classes".CC_DS."validate".CC_DS."validateCard.php");	
	$card = new validateCard();
	
	$cardNo			= trim($_POST["cardNumber"]);
	$issueNo		= false;
	$issueDate		= false; 
	$issueFormat	= 4; 
	$expireDate		= trim($_POST["expirationYear"]).str_pad(trim($_POST["expirationMonth"]), 2, '0', STR_PAD_LEFT);  
	$expireFormat	= 4; 
	if($module['reqCvv']==1) {
		$scReqd			= TRUE;
	} else {
		$scReqd			= FALSE;
	} 
	$securityCode	= trim($_POST["cvv"]);
	
	$card = $card->check($cardNo, 
						$issueNo, 
						$issueDate, 
						$issueFormat, 
						$expireDate, 
						$expireFormat, 
						$scReqd, 
						$securityCode);

	if($module['validation']==1 && $card['response']=="FAIL") {
		
		$errorMsg = "";
		
		foreach($card['error'] as $val) {
			$errorMsg .= $val."<br />";
		}
		
		$transData['trans_id'] = "";
		$transData['status'] = "Fail";
		$transData['notes'] = $errorMsg;

	} else {

		$debug = "Merchant e-Solutions<br />
		<strong>Debug Info:</strong>";
		
		if($module['testMode'] == 1)
			$host 	= "https://cert.merchante-solutions.com/mes-api/tridentApi";
		else
			$host = "https://api.merchante-solutions.com/mes-api/tridentApi";
			
		//$type = 
		
		$tran = new TpgTransaction($module['profile_id'], $module['profile_key']);
		$tran->TranType = $module['tranType'];
		$tran->setHost($host);
		
		$tran->setTransactionData(trim($_POST["cardNumber"]), trim($_POST["expirationMonth"]).trim($_POST["expirationYear"]), $orderSum['prod_total']);
		$tran->setRequestField("invoice_number", "1234");
		$tran->setRequestField("client_reference_number", $orderSum['cart_order_id']);
		$tran->SetAvsRequest(trim($_POST["addr1"]), trim($_POST["postalCode"]));
		$tran->setRequestField("cvv2", trim($_POST['cvv']));		
		$tran->execute();

		$debug = "Response string: ".$tran->ResponseRaw;


		if($tran->ResponseFields['error_code'] == "000")
		{
			$fval="Approved";
			$module['tranType'] == "D" ? $type = "Sale" : $type = "Pre-Auth";
			$statusLog = "Approval - " . $type;
			$order->orderStatus(3,$orderSum['cart_order_id']);
			$jumpTo = "index.php?_g=co&_a=confirmed&s=2";
			$errorMsg = "Card has been approved.";
		}
		else
		{
			$fval="Declined";
			$statusLog = "Decline";
			$errorMsg = $lang['gateway']['card_declined'] . "<br />The following reason was provided: ".$tran->ResponseFields['auth_response_text'];
		}
		
		$transData['trans_id'] = $tran->ResponseFields['transaction_id'];
		$transData['status'] = $statusLog;
		$transData['notes'] = "Gateway Response:<br /> ".$tran->ResponseFields['auth_response_text'];
	}
	
	$order->storeTrans($transData);

	
	if($module['debug'] == 1)
		echo $debug;
	elseif(isset($jumpTo) && !empty($jumpTo))
		httpredir($jumpTo);
	
	unset($debug);
}


$formTemplate = new XTemplate ("modules".CC_DS."gateway".CC_DS.$_POST['gateway'].CC_DS."form.tpl",'',null,'main',true,true);

if(isset($errorMsg)) {
	
	$formTemplate->assign("LANG_ERROR",$errorMsg);
	$formTemplate->parse("form.error");

}
	
$billingName = makeName($orderSum['name']);
$deliveryName = makeName($orderSum['name_d']);

$formTemplate->assign("VAL_AMOUNT_DUE",sprintf($lang['gateway']['amount_due'],priceformat($orderSum['prod_total'],true)));
$formTemplate->assign("VAL_EMAIL_ADDRESS",$orderSum['email']);
$formTemplate->assign("VAL_ADD_1",$orderSum['add_1']);
$formTemplate->assign("VAL_ADD_2",$orderSum['add_2']);
$formTemplate->assign("VAL_CITY",$orderSum['town']);
$formTemplate->assign("VAL_COUNTY",$orderSum['county']);
$formTemplate->assign("VAL_POST_CODE",$orderSum['postcode']);

$countries = $db->select("SELECT id, iso, printable_name FROM ".$glob['dbprefix']."CubeCart_iso_countries ORDER BY printable_name");
	
for($i=0; $i<count($countries); $i++) {
				
	if($countries[$i]['id'] == $orderSum['country']) {
		$formTemplate->assign("COUNTRY_SELECTED","selected='selected'");
	} else {
		$formTemplate->assign("COUNTRY_SELECTED","");
	}
	
	$formTemplate->assign("VAL_COUNTRY_ISO",$countries[$i]['iso']);

	$countryName = "";
	$countryName = $countries[$i]['printable_name'];

	if(strlen($countryName)>20) {
		$countryName = substr($countryName,0,20)."&hellip;";
	}

	$formTemplate->assign("VAL_COUNTRY_NAME",$countryName);
	$formTemplate->parse("form.repeat_countries");
}
	
$formTemplate->assign("LANG_CC_INFO_TITLE",$lang['gateway']['cc_info_title']);
$formTemplate->assign("LANG_CARD_NUMBER",$lang['gateway']['card_number']);
	
if($module['reqCvv']==1) {
	$formTemplate->assign("LANG_CVV",$lang['gateway']['security_code']);
	$formTemplate->parse("form.cvv");
}

$formTemplate->assign("LANG_EXPIRES",$lang['gateway']['expires']);
$formTemplate->assign("LANG_MMYYYY",$lang['gateway']['mmyyyy']);
$formTemplate->assign("LANG_SECURITY_CODE",$lang['gateway']['security_code']);
$formTemplate->assign("LANG_CUST_INFO_TITLE",$lang['gateway']['customer_info']);
$formTemplate->assign("LANG_EMAIL",$lang['gateway']['email']);
$formTemplate->assign("LANG_ADDRESS",$lang['gateway']['address']);
$formTemplate->assign("LANG_CITY",$lang['gateway']['city']);
$formTemplate->assign("LANG_STATE",$lang['gateway']['state']);
$formTemplate->assign("LANG_ZIPCODE",$lang['gateway']['zipcode']);
$formTemplate->assign("LANG_COUNTRY",$lang['gateway']['country']);
$formTemplate->assign("LANG_OPTIONAL",$lang['gateway']['optional']);
	
$formTemplate->assign("VAL_GATEWAY",sanitizeVar($_POST['gateway']));

$formTemplate->parse("form");
$formTemplate = $formTemplate->text("form");
?>