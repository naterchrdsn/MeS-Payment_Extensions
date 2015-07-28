<?php
/*
 * CubeCart Payment Module for MeS Payment Gateway Credit Card Transactions
 * Copyright (c) 2015 Merchant e-Solutions
 * All rights reserved.
 * Author: Nate Richardson <nrichardson@merchante-solutions.com>
 */
include("mes_sdk.php");
class Gateway {
	private $_config;
	private $_module;
	private $_basket;
	private $_url;

	public function __construct($module = false, $basket = false) {
		$this->_config	=& $GLOBALS['config'];
		$this->_session	=& $GLOBALS['user'];

		$this->_module	= $module;
		$this->_basket =& $GLOBALS['cart']->basket;
		
		if($this->_module['payment_mode'] == 'ph') {
			if($this->_module['test_mode'] == 1) {
				$this->_url = 'https://test.cielo-us.com/jsp/tpg/secure_checkout.jsp';
			} else {
				$this->_url = 'https://www.merchante-solutions.com/jsp/tpg/secure_checkout.jsp';
			}
		} else {
			if($this->_module['test_mode'] == 1) {
				$this->_url = 'https://cert.cielo-us.com/mes-api/tridentApi';
			} else {
				$this->_url = 'https://api.merchante-solutions.com/mes-api/tridentApi';
			}
		}
	}

	##################################################

	public function transfer() {
		$transfer	= array(
			'action'	=> ($this->_module['payment_mode'] == 'ph') ? $this->_url : currentPage(),
			'method'	=> 'post',
			'target'	=> '_self',
			'submit'	=> ($this->_module['payment_mode'] =='ph') ? 'auto'  : 'manual',
		);
		return $transfer;
	}

	public function repeatVariables() {
		return false;
	}

	public function fixedVariables() {
		$default_currency = $GLOBALS['config']->get('config', 'default_currency');

		if($this->_module['payment_mode'] == 'ph') {
			$hidden	= array(
				'profile_id'				=> $this->_module['profile_id'],
				'transaction_amount'		=> $this->_basket['total'],
				'invoice_number'			=> '1234',
				'client_reference_number'	=> $this->_basket['cart_order_id'],
				'use_merch_receipt'			=> 'Y',
				'cardholder_street_address'	=> $this->_basket['billing_address']['line1'],
				'cardholder_zip'			=> $this->_basket['billing_address']['postcode'],
				'return_url'				=> $GLOBALS['storeURL'].'/index.php?_g=rm&type=gateway&cmd=process&module=MeS',
				'cancel_url'				=> $GLOBALS['storeURL'].'/index.php?_a=confirm'
			);
			if(isset($this->_module['security_key'])) {
				$tran_key = md5($this->_module['profile_key'].$this->_module['security_key'].$this->_basket['total']);
				$hidden['transaction_key'] = $tran_key;
			}
		} else {
			$hidden['gateway']	= basename(dirname(__FILE__));
		}
		return $hidden;
	}

	##################################################

	public function call() {
		return false;
	}

	public function process() {

		$order				= Order::getInstance();
		$cart_order_id		= $this->_basket['cart_order_id'];
		$order_summary		= $order->getSummary($cart_order_id);
		$status 			= '';
		$transData = '';

		if($this->_module['payment_mode'] == 'ph') {
			if ( isset( $_POST['invoice_number'] ) ) {
				$transData['trans_id'] = $_POST['tran_id'];
				$transData['notes'] = 'Gateway Error Code: ' . $_POST['resp_code'] . "<br />";
				$transData['notes'] .= 'Gateway Text Response: ' . $_POST['resp_text'] . "<br />";
				$transData['notes'] .= 'Approval Code: ' . $_POST['auth_code'] . "<br />";
				$transData['gateway'] = 'MeS - ph';
				$order->orderStatus(Order::ORDER_PROCESS, $this->_basket['cart_order_id']);
				$status = 'Approved';
				$order->paymentStatus(Order::PAYMENT_SUCCESS, $this->_basket['cart_order_id']);
			};
		} elseif ($this->_module['payment_mode'] == 'pg') {
			if ("sale" == $this->_module['transaction_type']) {
				$tran = new TpgSale($this->_module['profile_id'], $this->_module['profile_key']);
			} else {
				$tran = new TpgPreAuth($this->_module['profile_id'], $this->_module['profile_key']);
			};
			$tran->setHost($this->_url);
			$tran->setAvsRequest($_POST['addr1'], trim($_POST['postcode']));
			$tran->setRequestField('cvv2', trim($_POST['cvv2']));
			$tran->setRequestField('invoice_number', '1234');
			$tran->setRequestField("client_reference_number", $this->_basket['cart_order_id']);
			$tran->setRequestField('currency_code', strtoupper( $GLOBALS['config']->get('config', 'default_currency') ));
			$tran->setRequestField('dest_country_code', $this->_basket['delivery_address']['country_iso']);
			$tran->setRequestField('ship_to_zip', $this->_basket['delivery_address']['postcode']);
			$tran->setRequestField('ship_to_address', $this->_basket['delivery_address']['line1']);
			$tran->setRequestField('ship_to_last_name', $this->_basket['delivery_address']['last_name']);
			$tran->setRequestField('ship_to_first_name', $this->_basket['delivery_address']['first_name']);
			$tran->setRequestField('country_code', $this->_basket['delivery_address']['country_iso']);
			$tran->setRequestField('cardholder_first_name', trim($_POST['firstName']));
			$tran->setRequestField('cardholder_last_name', trim($_POST['lastName']));
			$tran->setRequestField('cardholder_email', trim($_POST['emailAddress']));
			$tran->setRequestField('cardholder_phone', $this->_basket['billing_address']['phone']);
			$tran->setRequestField('account_name', $this->_basket['billing_address']['first_name'].' '.$this->_basket['billing_address']['last_name']);
			$tran->setRequestField('account_email', $this->_basket['billing_address']['email']);
			$tran->setTransactionData(trim($_POST['card_number']), str_pad($_POST['expirationMonth'], 2, '0', STR_PAD_LEFT).substr($_POST["expirationYear"],2,2), $this->_basket['total']);
			$tran->execute();

			if($tran->isApproved()) {
				$order->orderStatus(Order::ORDER_PROCESS, $this->_basket['cart_order_id']);
				$status = 'Approved';
				$order->paymentStatus(Order::PAYMENT_SUCCESS, $this->_basket['cart_order_id']);
			} else {
				$order->orderStatus(Order::ORDER_PENDING, $this->_basket['cart_order_id']);
				$status = 'Declined';
				$order->paymentStatus(Order::PAYMENT_PENDING, $this->_basket['cart_order_id']);
			};
			$transData['gateway'] = 'MeS - pg';
			$transData['trans_id'] = $tran->getResponseField('transaction_id');
			$transData['notes'] = "Gateway Response:<br /> ".$tran->getResponseField('auth_response_text');
		};
		$transData['order_id']		= $cart_order_id;
		$transData['customer_id']	= $order_summary['customer_id'];
		$transData['status']		= $status;
		$transData['amount'] 		= $this->_basket['total'];
		$transData['extra']			= '';
		$order->logTransaction($transData);

		if($status=='Approved') {
			httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'complete')));
		}
	}

	private function formatMonth($val) {
		return $val." - ".strftime("%b", mktime(0,0,0,$val,1 ,2009));
	}

	public function form() {
		
		## Process transaction
		if (isset($_POST['card_number'])) {
			$return	= $this->process();
		}

		// Display payment result message
		if (!empty($this->_result_message))	{
			$GLOBALS['gui']->setError($this->_result_message);
		}

		//Show Expire Months
		$selectedMonth	= (isset($_POST['expirationMonth'])) ? $_POST['expirationMonth'] : date('m');
		for($i = 1; $i <= 12; ++$i) {
			$val = sprintf('%02d',$i);
			$smarty_data['card']['months'][]	= array(
				'selected'	=> ($val == $selectedMonth) ? 'selected="selected"' : '',
				'value'		=> $val,
				'display'	=> $this->formatMonth($val),
			);
		}

		## Show Expire Years
		$thisYear = date("Y");
		$maxYear = $thisYear + 10;
		$selectedYear = isset($_POST['expirationYear']) ? $_POST['expirationYear'] : ($thisYear+2);
		for($i = $thisYear; $i <= $maxYear; ++$i) {
			$smarty_data['card']['years'][]	= array(
				'selected'	=> ($i == $selectedYear) ? 'selected="selected"' : '',
				'value'		=> $i,
			);
		}
		$GLOBALS['smarty']->assign('CARD', $smarty_data['card']);
		
		$smarty_data['customer'] = array(
			'first_name' => isset($_POST['firstName']) ? $_POST['firstName'] : $this->_basket['billing_address']['first_name'],
			'last_name'	 => isset($_POST['lastName']) ? $_POST['lastName'] : $this->_basket['billing_address']['last_name'],
			'email'      => isset($_POST['emailAddress']) ? $_POST['emailAddress'] : $this->_basket['billing_address']['email'],
			'add1'		 => isset($_POST['addr1']) ? $_POST['addr1'] : $this->_basket['billing_address']['line1'],
			'add2'		 => isset($_POST['addr2']) ? $_POST['addr2'] : $this->_basket['billing_address']['line2'],
			'city'		 => isset($_POST['city']) ? $_POST['city'] : $this->_basket['billing_address']['town'],
			'state'		 => isset($_POST['state']) ? $_POST['state'] : $this->_basket['billing_address']['state'],
			'postcode'		 => isset($_POST['postcode']) ? $_POST['postcode'] : $this->_basket['billing_address']['postcode']
		);
		
		$GLOBALS['smarty']->assign('CUSTOMER', $smarty_data['customer']);
		
		## Country list
		$countries = $GLOBALS['db']->select('CubeCart_geo_country', false, false, array('name' => 'ASC'));
		if ($countries) {
			$currentIso = isset($_POST['country']) ? $_POST['country'] : $this->_basket['billing_address']['country_iso'];
			foreach ($countries as $country) {
				$country['selected']	= ($country['iso'] == $currentIso) ? 'selected="selected"' : '';
				$smarty_data['countries'][]	= $country;
			}
			$GLOBALS['smarty']->assign('COUNTRIES', $smarty_data['countries']);
		}
		
		## Check for custom template for module in skin folder
		$file_name = 'form.tpl';
		$form_file = $GLOBALS['gui']->getCustomModuleSkin('gateway', dirname(__FILE__), $file_name);
		$GLOBALS['gui']->changeTemplateDir($form_file);
		$ret = $GLOBALS['smarty']->fetch($file_name);
		$GLOBALS['gui']->changeTemplateDir();
		return $ret;
	}
}