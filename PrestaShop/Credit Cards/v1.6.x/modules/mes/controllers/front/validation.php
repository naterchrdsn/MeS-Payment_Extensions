<?php

class mesvalidationModuleFrontController extends ModuleFrontController
{
	public function __construct()
	{
		parent::__construct();

		$this->context = Context::getContext();
	}

	public function initContent()
	{
		$mes = new mes();
		if ($mes->active && Tools::isSubmit('token') && Configuration::get('MES_MODE') == "PG")
			$mes->processPayment();
		else
			die($mes->l('You must submit a valid token to use the MeS Payment API.'));
	}
}

