<?php

/**
 * Merchant e-Solutions Payment Module Logic
 *
 * @copyright 2015 Merchant e-Solutions
 *
 */


class mes {
    var $code, $title, $description, $enabled;

	## Constructor
    function mes() {
      global $order;
	  
      $this->signature = 'mes|mes|2.0|1.2';
	  
      $this->Gresponse = array();
      $this->code = 'mes';
      $this->title = MODULE_PAYMENT_MES_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_MES_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_MES_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_MES_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_MES_STATUS == 'True') ? true : false);
	  

      if ( MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere' ) {
        if ( MODULE_PAYMENT_MES_TEST_MODE == 'Live' ) {
          $this->form_action_url = 'https://merchante-solutions.com/jsp/tpg/secure_checkout.jsp';
        } else {
          $this->form_action_url = 'https://test.cielo-us.com/jsp/tpg/secure_checkout.jsp';
        }
      }

      if ((int)MODULE_PAYMENT_MES_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_MES_ORDER_STATUS_ID;
      }
	  
      if (is_object($order)) $this->update_status();
    }

	## class methods
    function update_status() {
      global $order;
	  
      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_MES_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_MES_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }
		
        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      if (MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere') {
        global $cartID, $cart, $order;

        if (empty($cart->cartID)) {
          $cartID = $cart->cartID = $cart->generate_cart_id();
        }

        $order->info['payment_method_raw'] = $order->info['payment_method'];
        $order->info['payment_method'] = '<img src="https://www.merchante-solutions.com/wp-content/themes/Foundation-master/img/logo2.png" border="0" alt="Merchant e-Solutions Logo" style="padding: 3px;" width="100px" />';
      } else {
        return false;
      }
    }

    function confirmation() {
      if (MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere') {
        return false;
      } else {
        global $order;

        for ($i=1; $i<13; $i++) {
          $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
        }

        $today = getdate(); 
        for ($i=$today['year']; $i < $today['year']+10; $i++) {
          $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
        }

        $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_MES_CREDIT_CARD_OWNER,
                                                      'field' => tep_draw_input_field('cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                                array('title' => MODULE_PAYMENT_MES_CREDIT_CARD_NUMBER,
                                                      'field' => tep_draw_input_field('cc_number_nh-dns')),
                                                array('title' => MODULE_PAYMENT_MES_CREDIT_CARD_EXPIRES,
                                                      'field' => tep_draw_pull_down_menu('cc_expires_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('cc_expires_year', $expires_year)),
                                                array('title' => MODULE_PAYMENT_MES_CREDIT_CARD_CVC,
                                                      'field' => tep_draw_input_field('cc_cvc_nh-dns', '', 'size="5" maxlength="4"'))));
        return $confirmation;
      }
    }

    function process_button() {
      if (MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere') {
        global $order, $cartID;
        $process_button_string = tep_draw_hidden_field('profile_id', substr(MODULE_PAYMENT_MES_LOGIN_ID, 0, 20));
        $process_button_string .= tep_draw_hidden_field('transaction_amount', $this->format_raw($order->info['total']));
        $process_button_string .= tep_draw_hidden_field('invoice_number', $cartID);
        $process_button_string .= tep_draw_hidden_field('use_merch_receipt', 'Y');
        if (MODULE_PAYMENT_MES_SECURITY_KEY) {
          $tran_key = md5(MODULE_PAYMENT_MES_TRANSACTION_KEY.MODULE_PAYMENT_MES_SECURITY_KEY.$this->format_raw($order->info['total']));
          $process_button_string .= tep_draw_hidden_field('transaction_key', $tran_key);
        }
        $process_button_string .= tep_draw_hidden_field('transaction_type', ((MODULE_PAYMENT_MES_TRANSACTION_METHOD == 'Sale') ? 'D' : 'P'));
        $process_button_string .= tep_draw_hidden_field('cardholder_street_address', substr($order->billing['street_address'], 0, 60));
        $process_button_string .= tep_draw_hidden_field('cardholder_zip', substr($order->billing['postcode'], 0, 20));
        $process_button_string .= tep_draw_hidden_field('return_url', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true));
        $process_button_string .= tep_draw_hidden_field('cancel_url', tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code.'&error='.urlencode('CANCELED'), 'NONSSL', true, false));
        return $process_button_string;
      } else {
        return false;
      }
    }

    function before_process() {
      if (MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere') {
        return false;
      } else {
        global $HTTP_POST_VARS, $customer_id, $order, $sendto, $currency;
  	  
        $params = array('profile_id' => substr(MODULE_PAYMENT_MES_LOGIN_ID, 0, 20),
                        'profile_key' => substr(MODULE_PAYMENT_MES_TRANSACTION_KEY, 0, 32),
                        'cardholder_street_address' => substr($order->billing['street_address'], 0, 60),
                        'cardholder_zip' => substr($order->billing['postcode'], 0, 20),
                        'invoice_number' => substr($customer_id, 0, 17),
                        'client_reference_number' => $this->getClientReferenceNumber(),
                        'transaction_amount' => substr($this->format_raw($order->info['total']), 0, 15),
                        'currency_code' => 840,
                        'transaction_type' => ((MODULE_PAYMENT_MES_TRANSACTION_METHOD == 'Sale') ? 'D' : 'P'),
                        'card_number' => substr($HTTP_POST_VARS['cc_number_nh-dns'], 0, 22),
                        'card_exp_date' => $HTTP_POST_VARS['cc_expires_month'] . $HTTP_POST_VARS['cc_expires_year'],
                        'cvv2' => substr($HTTP_POST_VARS['cc_cvc_nh-dns'], 0, 4));
  	  
        $tax_value = 0;
  	  
        foreach ($order->info['tax_groups'] as $key => $value) {
          if ($value > 0) {
            $tax_value += $this->format_raw($value);
          }
        }
  	  
        if ($tax_value > 0) {
          $params['tax_amount'] = $this->format_raw($tax_value);
        }
  	  
        $post_string = '';
        foreach ($params as $key => $value) {
          $post_string .= $key . '=' . urlencode(trim($value)) . '&';
        }
        $post_string = substr($post_string, 0, -1);
  	  
        switch (MODULE_PAYMENT_MES_TEST_MODE) {
          case 'Live':
            $gateway_url = 'https://api.merchante-solutions.com/mes-api/tridentApi';
            break;
  		
          default:
            $gateway_url = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
            break;
        }
  	  
        $transaction_response = $this->sendTransactionToGateway($gateway_url, $post_string);
  	  
        $rFields = explode("&",$transaction_response); 
        $responseFields = array();
  	  
        foreach($rFields as $field) { 
          $nameValue = explode("=",$field); 
          $responseFields[$nameValue[0]] = $nameValue[1]; 
        } 
        $this->Gresponse = $responseFields;
  	  
        if ($responseFields['error_code'] != "000") {
          tep_redirect(FILENAME_CHECKOUT_PAYMENT . '?error=' . $responseFields['error_code'] . '&payment_error=mes');
        }
      }

    }

    function after_process() {
      if (MODULE_PAYMENT_MES_PAYMENT_MODE == 'PayHere') {
        global $HTTP_POST_VARS, $cart, $order, $currency, $currencies, $customer_id, $insert_id;
        if (!isset($HTTP_POST_VARS['tran_id']) || !isset($HTTP_POST_VARS['auth_code']) || !isset($HTTP_POST_VARS['resp_code'])) {
          tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode('No payment information found.'), 'NONSSL', true, false));
        }
        $comment =  "Credit Card " . MODULE_PAYMENT_MES_TRANSACTION_METHOD . ".\n";
        $comment .= "Mode: " . MODULE_PAYMENT_MES_PAYMENT_MODE . "\n";
        $comment .= "  Transaction ID: " . $HTTP_POST_VARS['tran_id'] . "\n";
        $comment .= "  OrderID: " . $insert_id . "\n";
        $comment .= "\nMeS Gateway Response\n";
        $comment .= "  Auth Code: " . $HTTP_POST_VARS['auth_code'] . "\n";
        $comment .= "  Gateway Plain Text Response: " . $HTTP_POST_VARS['resp_text'] . "\n";
      
        $sql_data_array = array('orders_id' => (int)$insert_id, 
                                'orders_status_id' => (int)$order->info['order_status'], 
                                'date_added' => 'now()', 
                                'customer_notified' => '0',
                                'comments' => $comment);
      
        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      } else {
        global $order, $insert_id;
        $comment =  "Credit Card " . MODULE_PAYMENT_MES_TRANSACTION_METHOD . ".\n";
        $comment .= "Mode: " . MODULE_PAYMENT_MES_PAYMENT_MODE . "\n";
        $comment .= "  Transaction ID: " . $this->Gresponse['transaction_id'] . "\n";
        $comment .= "  OrderID: " . $insert_id . "\n";
        $comment .= "\nMeS Gateway Response\n";
        $comment .= "  AVS Response: " . $this->Gresponse['avs_result'] . "\n";
        $comment .= "  CVV Response: " . $this->Gresponse['cvv2_result'] . "\n";
        $comment .= "  Auth Code: " . $this->Gresponse['auth_code'] . "\n";
        $comment .= "  Error Code: " . $this->Gresponse['error_code'] . "\n";
        $comment .= "  Gateway Plain Text Response: " . $this->Gresponse['auth_response_text'] . "\n";
  	  
        $sql_data_array = array('orders_id' => (int)$insert_id, 
                                'orders_status_id' => (int)$order->info['order_status'], 
                                'date_added' => 'now()', 
                                'customer_notified' => '0',
                                'comments' => $comment);
  	  
        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      }
    }

    function do_tid_tran($transaction_id, $orderid, $amount, $tran_type) {

      $params = array('profile_id' => substr(MODULE_PAYMENT_MES_LOGIN_ID, 0, 20),
                      'profile_key' => substr(MODULE_PAYMENT_MES_TRANSACTION_KEY, 0, 32),
                      'invoice_number' => "Order " . $orderid,
                      'transaction_id' => $transaction_id,
                      'transaction_amount' => $amount,
                      'transaction_type' => $tran_type);
	  
      $post_string = '';
      foreach ($params as $key => $value) {
        $post_string .= $key . '=' . urlencode(trim($value)) . '&';
      }
      $post_string = substr($post_string, 0, -1);
	  
      switch (MODULE_PAYMENT_MES_TEST_MODE) {
        case 'Live':
          $gateway_url = 'https://api.merchante-solutions.com/mes-api/tridentApi';
          break;
		
        default:
          $gateway_url = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
          break;
      }
	  
      $transaction_response = $this->sendTransactionToGateway($gateway_url, $post_string);
	  
      $rFields = explode("&",$transaction_response); 
      $responseFields = array();
	  
      foreach($rFields as $field) { 
        $nameValue = explode("=",$field); 
        $responseFields[$nameValue[0]] = $nameValue[1]; 
      } 
      $this->Gresponse = $responseFields;
	  
      $type = "";
      switch($tran_type) {
        case "U": $type = "Refund"; break;
        case "S": $type = "Settlement"; break;
        case "V": $type = "Void"; break;
      }
	  
      $comment =  "Credit Card " . $type . ".\n";
      $comment .= "  Transaction ID: " . $this->Gresponse['transaction_id'] . "\n";
      $comment .= "  OrderID: " . $orderid . "\n";
      $comment .= "\nMeS Gateway Response\n";
      $comment .= "  Error Code: " . $this->Gresponse['error_code'] . "\n";
      $comment .= "  Gateway Plain Text Response: " . $this->Gresponse['auth_response_text'] . "\n";
	  
      $sql_data_array = array('orders_id' => $orderid, 
                              'orders_status_id' => 2, 
                              'date_added' => 'now()', 
                              'customer_notified' => '0',
                              'comments' => $comment);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    }

    function get_error() {
      global $HTTP_GET_VARS;

      $error_message = MODULE_PAYMENT_MES_ERROR_GENERAL;
      $title = MODULE_PAYMENT_MES_ERROR_TITLE;
      $error = $HTTP_GET_VARS['error'];

      switch($error) {
	  
        case "0N7":
        	$error_message = MODULE_PAYMENT_MES_ERROR_CVV;
        	break;
        case "210":
        	$error_message = MODULE_PAYMENT_MES_ERROR_AVS_FILTER;
        	break;
        case "054":
        	$error_message = MODULE_PAYMENT_MES_ERROR_EXPIRED;
        	break;
        case "115":
        	$error_message = MODULE_PAYMENT_MES_ERROR_CARD_ERROR;
        	break;
        case "117":
        	$error_message = MODULE_PAYMENT_MES_ERROR_NOT_ACCEPTED;
        	break;
        case "http":
        	$error_message = MODULE_PAYMENT_MES_ERROR_HTTP;
        	break;
        case "curl":
        	$error_message = MODULE_PAYMENT_MES_ERROR_CURL;
        	break;
        case (int)$error < 101:
        	$error_message = MODULE_PAYMENT_MES_ERROR_DECLINED;
        	break;
        default:
        	$error_message = MODULE_PAYMENT_MES_ERROR_GENERAL;
        	break;
      }
	  
      $error = array('title' => $title, 'error' => $error_message);
      return $error;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MES_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Merchant e-Solutions Credit Card Module', 'MODULE_PAYMENT_MES_STATUS', 'False', 'Do you want to accept payments through Merchant e-Solutions?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Profile ID', 'MODULE_PAYMENT_MES_LOGIN_ID', '', 'The Profile ID used for MeS', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Profile Key', 'MODULE_PAYMENT_MES_TRANSACTION_KEY', '', 'Get your API keys from your MeS account details page.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test Mode', 'MODULE_PAYMENT_MES_TEST_MODE', 'Live', 'Use the live or testing (sandbox) gateway server to process transactions?', '6', '0', 'tep_cfg_select_option(array(\'Live\', \'Test\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment Mode', 'MODULE_PAYMENT_MES_PAYMENT_MODE', 'Payment Gateway', 'Choose Payment Gateway to use the MeS Payment Gateway API via a regular credit card form displayed to your customers.<br />Choose PayHere to use the MeS PayHere API and redirect the customer to the MeS PayHere hosted page.<br />Note: You must have either Payment Gateway or PayHere API access on your account!', '6', '0', 'tep_cfg_select_option(array(\'Payment Gateway\', \'PayHere\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Type', 'MODULE_PAYMENT_MES_TRANSACTION_METHOD', 'Pre-Authorization', 'Use the live gateway server to process Pre-Authorizations or Sales Transactions?', '6', '0', 'tep_cfg_select_option(array(\'Pre-Authorization\', \'Sale\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_MES_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_MES_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_MES_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Use SSL', 'MODULE_PAYMENT_MES_SSL', 'True', 'This requires SSL when performing a transaction. Be sure you have a SSL certificate installed.<br />This is useful when a development environment may not have a valid SSL certificate installed.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Security Key', 'MODULE_PAYMENT_MES_SECURITY_KEY', '', 'Your Security Key is a unique PIN you set up when enrolling your merchant account in PayHere. Consult your account rep for this info.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Custom reference number', 'MODULE_PAYMENT_MES_CLIENT_REFERENCE_NUMBER', 'Customer #[customerid]', 'The Client reference number shows in all MeS web Reporting.<br />Several keywords exist: [ip], [name], [email], [phone], [company], [customerid], [shippingmethod], [currency]<br />Example: Customer #[customerid], [name] ([email])', '6', '0', now())"); 
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array(
        'MODULE_PAYMENT_MES_STATUS',
				'MODULE_PAYMENT_MES_LOGIN_ID',
				'MODULE_PAYMENT_MES_TRANSACTION_KEY',
				'MODULE_PAYMENT_MES_TEST_MODE',
        'MODULE_PAYMENT_MES_PAYMENT_MODE',
				'MODULE_PAYMENT_MES_TRANSACTION_METHOD',
				'MODULE_PAYMENT_MES_ZONE',
				'MODULE_PAYMENT_MES_ORDER_STATUS_ID',
				'MODULE_PAYMENT_MES_SORT_ORDER',
				'MODULE_PAYMENT_MES_SSL',
        'MODULE_PAYMENT_MES_SECURITY_KEY',
				'MODULE_PAYMENT_MES_CLIENT_REFERENCE_NUMBER'
      );
    }

    function sendTransactionToGateway($url, $parameters) {
  	  $ch = curl_init(); 
  	  curl_setopt($ch, CURLOPT_POST, TRUE); 
  	  curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
  	  curl_setopt($ch, CURLOPT_URL, $url);
  	  
  	  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  	  curl_setopt($ch, CURLOPT_FRESH_CONNECT,TRUE);
  	  
  	  ## Use SSL?
  	  if( strtoupper(MODULE_PAYMENT_MES_SSL) == "TRUE" ) {
  		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
  		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE);
  	  }
  	  else {
  		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  	  }
  	  
  	  curl_setopt($ch, CURLOPT_HEADER, 0);
  	  curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
  	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  		
  	/* NYI
  	  if( strtoupper(MODULE_PAYMENT_MES_PROXY) == "TRUE" ) {
  		curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
  		curl_setopt ($ch, CURLOPT_PROXY, MODULE_PAYMENT_MES_PROXY_URL);
  	  }
  	*/
  		
  	  $result = curl_exec($ch);
  	  
  	  $header = curl_getinfo($ch);
  	  $errmsg = curl_error($ch);
  	  $curlerrnum = curl_errno($ch);
  	  curl_close($ch);
  	  
  	  ## cURL error
  	  if( $curlerrnum != 0 ) {
  		tep_redirect(FILENAME_CHECKOUT_PAYMENT . '?error=curl&payment_error=mes&error_msg='.urlencode($errmsg).'&curl_code='.$curlerrnum);
  	  }

  	  ## HTTP error
        if ($header['http_code'] != 200) {
          tep_redirect(FILENAME_CHECKOUT_PAYMENT . '?error=http&payment_error=mes&http_code='.$header['http_code']);
        }
  	  
        return $result;
    }

	// format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
      global $currencies, $currency;

      if (empty($currency_code) || !$this->is_set($currency_code)) {
        $currency_code = $currency;
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }


	function getClientReferenceNumber() {
		global $customer_id, $order, $currency;
		
		$crn = MODULE_PAYMENT_MES_CLIENT_REFERENCE_NUMBER;
		
		## Default
		if(empty($crn)) {
			return "Customer #".$customer_id;
		}
		else {
			## Add additional macros as necessary here
			$crn = str_replace("[ip]", tep_get_ip_address(), $crn);
			$crn = str_replace("[name]", $order->customer['firstname'] . " " . $order->customer['lastname'], $crn);
			$crn = str_replace("[email]", substr($order->customer['email_address'], 0, 255), $crn);
			$crn = str_replace("[phone]", $order->customer['telephone'], $crn);
			$crn = str_replace("[company]", $order->customer['company'], $crn);
			$crn = str_replace("[customerid]", $customer_id, $crn);
			$crn = str_replace("[shippingmethod]", $order->info['shipping_method'], $crn);
			$crn = str_replace("[currency]", $order->info['currency'], $crn);
			return $crn;
		}
	}
}
?>
