<?php
//  Date:	11/2015
//  File:	mes.php
//  Author:	naterchrdsn
//	Desc:	Main payment module file.
//  ©Merchant e-Solutions

if (!defined('_PS_VERSION_'))
	exit;

include_once(dirname(__FILE__).'/mes_sdk.php');

class mes extends PaymentModule
{
	protected $hooks = array(
		'payment',
		'orderConfirmation'
	);
	protected	$_apiURL;
	protected	$_profileID;
	protected	$_profileKey;
	private 	$html;

	public function __construct()
	{
		$this->name = 'mes';
		$this->tab = 'payments_gateways';
		$this->version = '1.0';
		$this->author = 'Merchant e-Solutions';
		$this->bootstrap = true;

		parent::__construct();

		if (!Configuration::get('MES_PROFILEID'))
			$this->warning = $this->l('your Merchant e-Solutions Profile ID must be configured in order to use this module correctly');
		if (!Configuration::get('MES_PROFILEKEY'))
			$this->warning = $this->l('your Merchant e-Solutions Profile Key must be configured in order to use this module correctly');
		if (!Configuration::get('MES_TRANTYPE') || !Configuration::get('MES_MODE'))
			$this->warning = $this->l('you need to complete setup of the Merchant e-Solutions module for it to work properly');

		$this->displayName = 'Merchant e-Solutions - Credit Card';
		$this->description = 'Accept payments through the MeS Payment Gateway';
		$this->confirmUninstall = 'Are you sure you want to remove the MeS module?';
	}

	public function install()
	{
		$install = parent::install();
		if($install)
		{
			foreach ($this->hooks as $hook)
			{
				if (!$this->registerHook($hook))
					return false;
			}
		}

		return $install;
	}

	public function uninstall()
	{
		Configuration::deleteByName('MES_MODE');
		Configuration::deleteByName('MES_TESTMODE');
		Configuration::deleteByName('MES_PROFILEID');
		Configuration::deleteByName('MES_PROFILEKEY');
		Configuration::deleteByName('MES_TRANTYPE');
		Configuration::deleteByName('MES_SECURITYKEY');
		foreach ($this->hooks as $hook)
		{
			if (!$this->unregisterHook($hook))
				return false;
		}

		return parent::uninstall();
	}

	  /************************************************************/
	 /******************* ADMIN CONFIGURATION ********************/
	/************************************************************/

	public function getContent()
	{
		$helper = $this->initForm();
		$this->postProcess();
		foreach ($this->fields_form as $field_form)
		{
			if (isset($field_form['form']['input']))
			{
				foreach ($field_form['form']['input'] as $input)
					$helper->fields_value[$input['name']] = Configuration::get(Tools::strtoupper($input['name']));
			}
		}
		$this->html .= '
			<div class="panel">
				<h3>Merchant e-Solutions Credit Card Module Info</h3>
				<p>Thank you for choosing the Merchant e-Solutions module for Credit Card transactions!<br />This payment gateway module allows payment via either the PayHere or Payment Gateway APIs provided by Merchant e-Solutions.</p>
			</div>
		';
		$this->html .= $helper->generateForm($this->fields_form);
		return $this->html;
	}


	private function initForm()
	{
		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->toolbar_btn = $this->initToolbar();
		$helper->title = $this->displayName;
		$helper->submit_action = 'submitUpdate';
		$modeoptions = array(
		  array(
		    'id_option' => 'PG',
		    'name' => 'Payment Gateway'
		    ),
		  array(
		    'id_option' => 'PH',
		    'name' => 'PayHere'
		  ),
		);
		$tranoptions = array(
		  array(
		    'id_option' => 'Sale',
		    'name' => 'Sale'
		    ),
		  array(
		    'id_option' => 'Auth',
		    'name' => 'Pre-Authorization'
		  ),
		);

		$this->fields_form[0]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('MERCHANT E-SOLUTIONS CREDIT CARD MODULE DETAILS'), 'image' => $this->_path.'logo_s.png'),
			'submit' => array(
				'title' => $this->l('   Save   ')
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Profile ID'),
					'name' => 'MES_PROFILEID',
					'required' => true,
					'hint' => $this->l('Get your API keys from your MeS account details page.'),
				),
				array(
					'type' => 'text',
					'label' => $this->l('Profile Key'),
					'name' => 'MES_PROFILEKEY',
					'required' => true,
					'hint' => $this->l('Get your API keys from your MeS account details page.'),
				),
				array(
					'type' => 'select',
					'options' => array(
					    'query' => $tranoptions,
					    'id' => 'id_option', 
					    'name' => 'name', 
					),
					'is_bool' => false,
					'required' => true,
					'class' => 't',
					'label' => $this->l('Transaction Type'),
					'name' => 'MES_TRANTYPE',
					'hint' => $this->l('Use the Payment Gateway to process Pre-Authorization or Sale transactions'),
				),
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Yes'), 'value' => 1, 'id' => 'sandbox_on'),
						array('label' => $this->l('No'), 'value' => 0, 'id' => 'sandbox_off'),
					),
					'is_bool' => true,
					'required' => true,
					'class' => 't',
					'label' => $this->l('Sandbox Mode'),
					'name' => 'MES_TESTMODE',
					'desc' => array(
						$this->l('Place the payment gateway in test mode using your API keys (real payments will not be taken).'),
					),
				),
				array(
					'type' => 'select',
					'options' => array(
					    'query' => $modeoptions,
					    'id' => 'id_option', 
					    'name' => 'name',
					),
					'is_bool' => false,
					'required' => true,
					'class' => 't',
					'label' => $this->l('API Mode'),
					'name' => 'MES_MODE',
					'desc' => array(
						$this->l('Choose Payment Gateway to use the MeS Payment Gateway API via a regular credit card form displayed to your customers.'),
						$this->l('Choose PayHere to use the MeS PayHere API and redirect the customer to the MeS PayHere hosted page.'),
						$this->l('Note: You must have either Payment Gateway or PayHere API access on your account!'),
					),
				),
				array(
					'type' => 'text',
					'label' => $this->l('Security Key'),
					'desc' => array(
						$this->l('This value is required only if you set it in your PayHere Configuration page, via the MeS Back Office'),
					),
					'name' => 'MES_SECURITYKEY',
					'class' => 'fixed-width-lg',
				),
			),
		);

		return $helper;
	}

	private function initToolbar()
	{
		$toolbar_btn = array();
		$toolbar_btn['save'] = array('href' => '#', 'desc' => $this->l('Save'));
		return $toolbar_btn;
	}

	/**
	 * save configuration values
	 */
	protected function postProcess()
	{
		if (Tools::isSubmit('submitUpdate'))
		{
			foreach ($this->fields_form as $field_form)
			{
				foreach ($field_form['form']['input'] as $input)
				{
					$value = Tools::getValue(Tools::strtoupper($input['name']));
					if (in_array($input['name'], array('MES_PROFILEID')) && empty($value))
						continue;
					Configuration::updateValue(Tools::strtoupper($input['name']), $value);
				}
			}

			Tools::redirectAdmin('index.php?tab=AdminModules&conf=4&configure='.$this->name.'&token='.Tools::getAdminToken('AdminModules'.(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id));
		}
	}

	  /************************************************************/
	 /******************** GETTERS & SETTERS *********************/
	/************************************************************/

	public function setSessionMessage($key, $value)
	{
		$this->context->cookie->{$key} = $value;
	}

	public function getSessionMessage($key)
	{
		if (isset($this->context->cookie->$key))
			return $this->context->cookie->$key;

		return '';
	}

	public function getAndCleanSessionMessage($key)
	{
		$message = $this->getSessionMessage($key);
		unset($this->context->cookie->$key);
		return $message;
	}
	
	  /************************************************************/
	 /************************* HOOKS ****************************/
	/************************************************************/

	public function hookPayment($params)
	{
		if (!$this->active)
			return ;
		$html = '';
		if (Configuration::get('MES_MODE') == "PG")
		{
			$params = array();
			$params['err_message'] = $this->getAndCleanSessionMessage('mes_message');
			$this->context->smarty->assign($params);
			$html .= $this->display(__FILE__, 'pgpayment.tpl');
		}
		else
		{
			$cart = $this->context->cart;
			$invoice = new Address((int)$cart->id_address_invoice);
			$params = array(
				'mes_profile_id' => Configuration::get('MES_PROFILEID'),
				'mes_transaction_amount' => number_format(floatval($cart->getOrderTotal(true, 3)), 2, '.', ''),
				'mes_invoice_number' => (int)$cart->id,
				'mes_use_merch_receipt' => 'Y',
				'mes_cardholder_street_address' => $invoice->address1,
				'mes_cardholder_zip' => $invoice->postcode,
				'mes_echo_redirurl' => urlencode(Tools::getShopDomain(true).__PS_BASE_URI__),
				'mes_return_url' => Tools::getShopDomain(true).__PS_BASE_URI__.'modules/mes/mes_cc_redirect.php',
				'mes_cancel_url' => $this->context->link->getPageLink('order.php',''),
			);
			if (Configuration::get('MES_TESTMODE') == false)
			{
				$params['mes_form_action'] = 'https://www.merchante-solutions.com/jsp/tpg/secure_checkout.jsp';
			}
			else
			{
				$params['mes_form_action'] = 'https://test.cielo-us.com/jsp/tpg/secure_checkout.jsp';
			};
			if (Configuration::get('MES_SECURITYKEY'))
			{
				$tran_key = md5(Configuration::get('MES_PROFILEKEY').Configuration::get('MES_SECURITYKEY').$cart->getOrderTotal(true));
				$params['mes_transaction_key'] = $tran_key;
			};
			$this->context->smarty->assign($params);
			$html .= $this->display(__FILE__, 'phpayment.tpl');
		}
		return $html;
	}

	public function processPayment()
	{
		$cart = $this->context->cart;
		$user = $this->context->customer;
		if (!Validate::isLoadedObject($cart) || !Validate::isLoadedObject($user))
		{
			$this->setSessionMessage('mes_message', $this->l('Payment Authorization Failed: Your shopping cart or user is not valid'));
			Tools::redirect($this->context->link->getPageLink('order.php',''));
		}
		$delivery = new Address((int)$cart->id_address_delivery);
		$invoice = new Address((int)$cart->id_address_invoice);
		if (!Validate::isLoadedObject($delivery) || !Validate::isLoadedObject($invoice))
		{
			$this->setSessionMessage('mes_message', $this->l('Payment Authorization Failed: Your delivery or invoice address is not valid'));
			Tools::redirect($this->context->link->getPageLink('order.php',''));
		}

		if (Configuration::get('MES_MODE') == 'PH')
		{
			$mail_vars = array(
			  '{transaction_id}' => Tools::getValue('tran_id'),
			  '{auth_code}' => Tools::getValue('auth_code')
			);

			$tran_amount = Tools::getValue('tran_amount');

			$admin_note =  "MeS PayHere Response\n";
			$admin_note .= "Response Code: ".Tools::getValue('resp_code')."\n";
			$admin_note .= "Approval Code: ".Tools::getValue('auth_code')."\n";
			$admin_note .= "Transaction ID: ".Tools::getValue('tran_id')."\n";
			$admin_note .= "Text Result: ".Tools::getValue('resp_text')."\n";

			$link_params = array(
				'key' => $user->secure_key,
				'id_cart' => (int)$cart->id,
				'id_module' => (int)$this->id,
				'id_order' => (int)$this->currentOrder,
			);
			$this->validateOrder($cart->id, _PS_OS_PAYMENT_, $tran_amount, 'MeS CC - PH', $admin_note, $mail_vars, NULL, false, $user->secure_key);
			Tools::redirect($this->context->link->getPageLink('order-confirmation', null, null, $link_params));
		}
		else
		{
			$currencies = Currency::getCurrencies();
			$authorized_currencies = array_flip(explode(',', $this->currencies));
			$currencies_used = array();
			foreach ($currencies as $key => $currency)
			{
				if (isset($authorized_currencies[$currency['id_currency']]))
					$currencies_used[] = $currencies[$key];
			}
			foreach ($currencies_used as $currency)
			{
				if ($currency['id_currency'] == $cart->id_currency)
					$order_currency = $currency['iso_code'];
			}
			if (Configuration::get('MES_TESTMODE') == false)
			{
				$api_url = 'https://api.merchante-solutions.com/mes-api/tridentApi';
			} else {
				$api_url = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
			};
			$transaction_type = Configuration::get('MES_TRANTYPE');
			$profile_id = Configuration::get('MES_PROFILEID');
			$profile_key = Configuration::get('MES_PROFILEKEY');
			$transaction_amount = number_format(floatval($cart->getOrderTotal(true, 3)), 2, '.', '');
			$card_number = str_replace(" ", "", Tools::getValue('ccNo'));
			$card_exp_date = Tools::getValue('expMonth') . "/" . Tools::getValue('expYear');

			if($transaction_type == "Sale")
			{
				$transaction = new TpgSale($profile_id, $profile_key);
			} 
			else
			{
				$transaction = new TpgPreAuth($profile_id, $profile_key);
			};

			$transaction->setHost($api_url);
			$transaction->setAvsRequest($invoice->address1, $invoice->postcode);
			$transaction->setRequestField("invoice_number", intval($cart->id));
			$transaction->setRequestField("cvv2", trim(Tools::getValue('cvv')));
			$transaction->setRequestField('currency_code', $order_currency);
			$transaction->setRequestField('dest_country_code', $delivery->country);
			$transaction->setRequestField('ship_to_zip', $delivery->postcode);
			$transaction->setRequestField('ship_to_address', $invoice->address1);
			$transaction->setRequestField('ship_to_last_name', $delivery->lastname);
			$transaction->setRequestField('ship_to_first_name', $delivery->firstname);
			$transaction->setRequestField('country_code', $invoice->country);
			$transaction->setRequestField('custom', $cart->id);
			$transaction->setRequestField('cardholder_first_name', $invoice->firstname);
			$transaction->setRequestField('cardholder_last_name', $invoice->lastname);
			$transaction->setRequestField('cardholder_email', $user->email);
			$transaction->setRequestField('cardholder_phone', $invoice->phone);
			$transaction->setTransactionData($card_number, $card_exp_date, $transaction_amount);
			$transaction->execute();

			if (false == $transaction->isApproved())
			{
				$this->setSessionMessage('mes_message', $this->l('Payment Authorization Failed: Please verify your Credit Card details are entered correctly and try again, or try another payment method. Error message: ').$transaction->ResponseFields['auth_response_text']);
				Tools::redirect($this->context->link->getPageLink('order.php',''));
			}
			else
			{
				$mail_vars = array(
				  '{transaction_id}' => $transaction->ResponseFields['transaction_id'],
				  '{auth_code}' => $transaction->ResponseFields['auth_code']
				);

				$admin_note =  "MeS Payment Gateway Response\n";
				$admin_note .= "Error Code: ".$transaction->ResponseFields['error_code']."\n";
				$admin_note .= "Approval Code: ".$transaction->ResponseFields['auth_code']."\n";
				$admin_note .= "Transaction ID: ".$transaction->ResponseFields['transaction_id']."\n";
				$admin_note .= "AVS Result: ".$transaction->ResponseFields['avs_result']."\n";
				$admin_note .= "CVV Result: ".$transaction->ResponseFields['cvv2_result']."\n";

				$link_params = array(
					'key' => $user->secure_key,
					'id_cart' => (int)$cart->id,
					'id_module' => (int)$this->id,
					'id_order' => (int)$this->currentOrder,
				);
				$this->validateOrder($cart->id, _PS_OS_PAYMENT_, $transaction_amount, 'MeS CC - PG', $admin_note, $mail_vars, NULL, false, $user->secure_key);
				Tools::redirect($this->context->link->getPageLink('order-confirmation', null, null, $link_params));
			}
		}
	}
	
	public function hookOrderConfirmation($params)
	{
		if (!$this->active)
			return;
		if ($params['objOrder']->module != $this->name)
			return;

		$this->smarty->assign('mes_order', array('id' => $params['objOrder']->id, 'reference' => $params['objOrder']->reference, 'valid' => $params['objOrder']->valid));
		return $this->display(__FILE__, 'order-confirmation.tpl');

	}
}