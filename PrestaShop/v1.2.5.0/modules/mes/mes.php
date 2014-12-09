<?php
//  Date:	02/11/2010
//  File:	mes.php
//  Author:	b.rice
//	Desc:	Main payment module file.
//  ©Merchant e-Solutions 2010


class mes extends PaymentModule
{

	protected	$_apiURL;
	protected	$_profileID;
	protected	$_profileKey;

	public function __construct()
	{
		$this->name = 'mes';
		$this->tab = 'Payment';
		$this->version = '1.0';

		$this->currencies = true;
		$this->currencies_mode = 'radio';

		$config = Configuration::getMultiple(array('MES_APIURL', 'MES_PROFILEID', 'MES_PROFILEKEY'));
		if (isset($config['MES_APIURL']))
			$this->_apiURL = $config['MES_APIURL'];
			
		if (isset($config['MES_PROFILEID']))
			$this->_profileID = $config['MES_PROFILEID'];
			
		if (isset($config['MES_PROFILEKEY']))
			$this->_profileKey = $config['MES_PROFILEKEY'];
			
		parent::__construct();

		$this->displayName = 'Merchant e-Solutions';
		$this->description = 'Accept payments through the MeS Payment Gateway';
		$this->confirmUninstall = 'Are you sure you want to remove the MeS module?';

		if (empty($this->_profileKey) OR empty($this->_profileID) )
			$this->warning = 'You need to configure your Profile ID and Key!';
	}

	public function install()
	{
		if (!parent::install()
			OR !Configuration::updateValue('MES_APIURL', "cert.merchante-solutions.com")
			OR !Configuration::updateValue('MES_PROFILEID', "")
			OR !Configuration::updateValue('MES_PROFILEKEY', "")
			OR !Configuration::updateValue('MES_TRANSACTIONTYPE', "D")
			OR !Configuration::updateValue('MES_CVVFLAG',"yes")
			OR !Configuration::updateValue('MES_VISA', "on")
			OR !Configuration::updateValue('MES_MASTERCARD', "on")
			OR !Configuration::updateValue('MES_DISCOVER', "")
			OR !Configuration::updateValue('MES_AMEX', "")
			OR !$this->registerHook('paymentReturn')
			OR !$this->registerHook('payment')
			)
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall() 
			OR !Configuration::deleteByName('MES_APIURL')
			OR !Configuration::deleteByName('MES_PROFILEID')
			OR !Configuration::deleteByName('MES_PROFILEKEY') 
			OR !Configuration::deleteByName('MES_TRANSACTIONTYPE') 
			OR !Configuration::deleteByName('MES_CVVFLAG')
			OR !Configuration::deleteByName('MES_VISA') 
			OR !Configuration::deleteByName('MES_MASTERCARD') 
			OR !Configuration::deleteByName('MES_DISCOVER') 
			OR !Configuration::deleteByName('MES_AMEX') 
			)
			return false;
		return true;
	}

	  /************************************************************/
	 /******************* ADMIN CONFIGURATION ********************/
	/************************************************************/

	public function getContent()
	{
		$html = '<h2>'.$this->displayName.'</h2>';
		$err = array();
		
		if (!empty($_POST))
		{
			if (empty($_POST['profileID']))
				array_push($err, 'Profile ID is required.');
			if (empty($_POST['profileKey']))
				array_push($err, 'Profile Key is required.');
			
			if (!sizeof($err))
			{
				Configuration::updateValue('MES_PROFILEID', $_POST['profileID']);
				Configuration::updateValue('MES_PROFILEKEY', $_POST['profileKey']);
				Configuration::updateValue('MES_APIURL', $_POST['apiURL']);
				Configuration::updateValue('MES_CVVFLAG', $_POST['cvvflag']);
				Configuration::updateValue('MES_TRANSACTIONTYPE', $_POST['transaction_type']);
				Configuration::updateValue('MES_VISA', $_POST['visa']);
				Configuration::updateValue('MES_MASTERCARD', $_POST['mastercard']);
				Configuration::updateValue('MES_DISCOVER', $_POST['discover']);
				Configuration::updateValue('MES_AMEX', $_POST['amex']);
				
				$html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="ok" />Settings updated</div>';
			}
			else
				foreach ($err AS $error)
					$html .= '<div class="alert error">'. $error .'</div>';
		}
		else
			$html .= '<br />';
			
		include('mesAdmin.php');
		return $html;
	}
	
	  /************************************************************/
	 /************************* HOOKS ****************************/
	/************************************************************/

	public function hookPayment($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		$state = $params['objOrder']->getCurrentState();
		$success = FALSE; //False until proven true. I'm a glass half empty kind of person.
		if($state == _PS_OS_PAYMENT_ OR $state == _PS_OS_OUTOFSTOCK_)
			$success = TRUE;

		$html = "";
	    include('payment_return.php');
		return $html;
	}
}