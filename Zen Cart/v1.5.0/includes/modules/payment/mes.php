<?php
/**
 * Merchant e-Solutions payment method class
 *
 * @package paymentMethod
 * @copyright Copyright 2008-2011 Merchant e-Solutions
 * @copyright Portions Copyright 2003-2007 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 */

class mes extends base {
  /**
   * $code determines the internal 'code' name used to designate "this" payment module
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
   */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
   */
  var $description;
  /**
   * $enabled determines whether this module shows or not... in catalog.
   *
   * @var boolean
   */
  var $enabled;
  /**
   * $delimiter determines what separates each field of returned data
   *
   * @var string (single char)
   */
  var $delimiter = ',';
  /**
   * $encapChar denotes what character is used to encapsulate the response fields
   *
   * @var string (single char)
   */
  var $encapChar = '';
  /**
   * log file folder
   *
   * @var string
   */
  var $_logDir = '';
  /**
   * communication vars
   */
  var $authorize = '';
  var $commErrNo = 0;
  var $commError = '';
  /**
   * debug content var
   */
  var $reportable_submit_data = array();
  
  /**
   * Constructor
   *
   * @return mes
   */
  function mes() {
    global $order;
    $this->code = 'mes';
    $this->enabled = ((MODULE_PAYMENT_MES_STATUS == 'True') ? true : false); // Whether the module is installed or not
    if (IS_ADMIN_FLAG === true) {
      // Payment module title in Admin
      $this->title = MODULE_PAYMENT_MES_TEXT_ADMIN_TITLE;
      if (MODULE_PAYMENT_MES_STATUS == 'True' && (MODULE_PAYMENT_MES_LOGIN == 'testing' || MODULE_PAYMENT_MES_TXNKEY == 'Test')) {
        $this->title .=  '<span class="alert"> (Not Configured)</span>';
      } elseif (MODULE_PAYMENT_MES_TESTMODE == 'Test') {
        $this->title .= '<span class="alert"> (in Testing mode)</span>';
      }
      if ($this->enabled && !function_exists('curl_init')) $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_ERROR_CURL_NOT_FOUND, 'error');
    } else {
      $this->title = MODULE_PAYMENT_MES_TEXT_CATALOG_TITLE; // Payment module title in Catalog
    }
	
	if (!defined('ENABLE_SSL') || ENABLE_SSL != 'true') {
    	$this->description .= '<span class="alert">'.MODULE_PAYMENT_MES_TEXT_SSL_REQUIRED.'</span><br /><br />';
    }
    
    $this->description .= MODULE_PAYMENT_MES_TEXT_DESCRIPTION; // Descriptive Info about module in Admin
    $this->sort_order = MODULE_PAYMENT_MES_SORT_ORDER; // Sort Order of this payment option on the customer payment page
    $this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false); // Page to go to upon submitting page info
    $this->order_status = (int)DEFAULT_ORDERS_STATUS_ID;
    if ((int)MODULE_PAYMENT_MES_ORDER_STATUS_ID > 0) {
      $this->order_status = (int)MODULE_PAYMENT_MES_ORDER_STATUS_ID;
    }

    $this->_logDir = DIR_FS_SQL_CACHE;

    if (is_object($order)) $this->update_status();
  }
  
  /**
   * display this method to customers?   
   */
  function update_status() {
    global $order, $db;
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_MES_ZONE > 0) ) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_MES_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }
  
  /**
   * JS validation which does error-checking of data-entry if this module is selected for use
   * (Number, Owner, and CVV Lengths)
   *
   * @return string
   */
  function javascript_validation() {
    $js = '  if (payment_value == "' . $this->code . '") {' . "\n" .
    '    var cc_owner = document.checkout_payment.mes_cc_owner.value;' . "\n" .
    '    var cc_number = document.checkout_payment.mes_cc_number.value;' . "\n";
    if (MODULE_PAYMENT_MES_USE_CVV == 'True')  {
      $js .= '    var cc_cvv = document.checkout_payment.mes_cc_cvv.value;' . "\n";
    }
    $js .= '    if (cc_owner == "" || cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
    '      error_message = error_message + "' . MODULE_PAYMENT_MES_TEXT_JS_CC_OWNER . '";' . "\n" .
    '      error = 1;' . "\n" .
    '    }' . "\n" .
    '    if (cc_number == "" || cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
    '      error_message = error_message + "' . MODULE_PAYMENT_MES_TEXT_JS_CC_NUMBER . '";' . "\n" .
    '      error = 1;' . "\n" .
    '    }' . "\n";
    if (MODULE_PAYMENT_MES_USE_CVV == 'True')  {
      $js .= '    if (cc_cvv == "" || cc_cvv.length < "3" || cc_cvv.length > "4") {' . "\n".
      '      error_message = error_message + "' . MODULE_PAYMENT_MES_TEXT_JS_CC_CVV . '";' . "\n" .
      '      error = 1;' . "\n" .
      '    }' . "\n" ;
    }
    $js .= '  }' . "\n";

    return $js;
  }
  
  /**
   * Display Credit Card Information Submission Fields on the Checkout Payment Page
   *
   * @return array
   */
  function selection() {
    global $order;

    for ($i=1; $i<13; $i++) {
      $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B - (%m)',mktime(0,0,0,$i,1,2000)));
    }

    $today = getdate();
    for ($i=$today['year']; $i < $today['year']+10; $i++) {
      $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
    }
    $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';

    $selection = array('id' => $this->code,
                       'module' => MODULE_PAYMENT_MES_TEXT_CATALOG_TITLE,
                       'fields' => array(array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_OWNER,
                                               'field' => zen_draw_input_field('mes_cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'], 'id="'.$this->code.'-cc-owner"'. $onFocus),
                                               'tag' => $this->code.'-cc-owner'),
                                         array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_NUMBER,
                                               'field' => zen_draw_input_field('mes_cc_number', '', 'id="'.$this->code.'-cc-number"' . $onFocus),
                                               'tag' => $this->code.'-cc-number'),
                                         array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_EXPIRES,
                                               'field' => zen_draw_pull_down_menu('mes_cc_expires_month', $expires_month, '', 'id="'.$this->code.'-cc-expires-month"' . $onFocus) . '&nbsp;' . zen_draw_pull_down_menu('mes_cc_expires_year', $expires_year, '', 'id="'.$this->code.'-cc-expires-year"' . $onFocus),
                                               'tag' => $this->code.'-cc-expires-month')));
    if (MODULE_PAYMENT_MES_USE_CVV == 'True') {
      $selection['fields'][] = array('title' => MODULE_PAYMENT_MES_TEXT_CVV,
                                   'field' => zen_draw_input_field('mes_cc_cvv', '', 'size="4", maxlength="4"' . ' id="'.$this->code.'-cc-cvv"' . $onFocus) . ' ' . '<a href="javascript:popupWindow(\'' . zen_href_link(FILENAME_POPUP_CVV_HELP) . '\')">' . MODULE_PAYMENT_MES_TEXT_POPUP_CVV_LINK . '</a>',
                                   'tag' => $this->code.'-cc-cvv');
    }
    return $selection;
  }
  
  /**
   * Validation of card data and other requirements (such as SSL)
   */
  function pre_confirmation_check() {
    global $messageStack;
    
    include(DIR_WS_CLASSES . 'cc_validation.php');

    $cc_validation = new cc_validation();
    $result = $cc_validation->validate($_POST['mes_cc_number'], $_POST['mes_cc_expires_month'], $_POST['mes_cc_expires_year'], $_POST['mes_cc_cvv']);
    $error = '';
    switch ($result) {
      case -1:
      $error = sprintf(TEXT_CCVAL_ERROR_UNKNOWN_CARD, substr($cc_validation->cc_number, 0, 4));
      break;
      case -2:
      case -3:
      case -4:
      $error = TEXT_CCVAL_ERROR_INVALID_DATE;
      break;
      case false:
      $error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
      break;
    }
    
    // Require SSL if we're not in test.
  	if ( (!defined('ENABLE_SSL') || ENABLE_SSL != 'true') && (MODULE_PAYMENT_MES_TESTMODE != 'Test') ) {
    	$error = MODULE_PAYMENT_MES_TEXT_SSL_REQUIRED;
    	$result = false;
    }

    if ( ($result == false) || ($result < 1) ) {
      $payment_error_return = 'payment_error=' . $this->code . '&mes_cc_owner=' . urlencode($_POST['mes_cc_owner']) . '&mes_cc_expires_month=' . $_POST['mes_cc_expires_month'] . '&mes_cc_expires_year=' . $_POST['mes_cc_expires_year'];
      $messageStack->add_session('checkout_payment', $error . '<!-- ['.$this->code.'] -->', 'error');
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
    }

    $this->cc_card_type = $cc_validation->cc_type;
    $this->cc_card_number = $cc_validation->cc_number;
    $this->cc_expiry_month = $cc_validation->cc_expiry_month;
    $this->cc_expiry_year = $cc_validation->cc_expiry_year;
  }
  
  /**
   * Display Credit Card Information on the Checkout Confirmation Page
   *
   * @return array
   */
  function confirmation() {
    $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_TYPE,
                                                  'field' => $this->cc_card_type),
                                            array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_OWNER,
                                                  'field' => $_POST['mes_cc_owner']),
                                            array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_NUMBER,
                                                  'field' => substr($this->cc_card_number, 0, 4) . str_repeat('X', (strlen($this->cc_card_number) - 8)) . substr($this->cc_card_number, -4)),
                                            array('title' => MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_EXPIRES,
                                                  'field' => strftime('%B, %Y', mktime(0,0,0,$_POST['mes_cc_expires_month'], 1, '20' . $_POST['mes_cc_expires_year']))) ));
    return $confirmation;
  }
  
  /**
   * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
   * This sends the data to the payment gateway for processing.
   * (These are hidden fields on the checkout confirmation page)
   *
   * @return string
   */
  function process_button() {
    $process_button_string = zen_draw_hidden_field('cc_owner', $_POST['mes_cc_owner']) .
                             zen_draw_hidden_field('cc_expires', $this->cc_expiry_month . substr($this->cc_expiry_year, -2)) .
                             zen_draw_hidden_field('cc_type', $this->cc_card_type) .
                             zen_draw_hidden_field('cc_number', $this->cc_card_number);
    if (MODULE_PAYMENT_MES_USE_CVV == 'True') {
      $process_button_string .= zen_draw_hidden_field('cc_cvv', $_POST['mes_cc_cvv']);
    }
    $process_button_string .= zen_draw_hidden_field(zen_session_name(), zen_session_id());

    return $process_button_string;
  }
  
  /**
   * Store the details to the order and process any results that come back from the payment gateway
   */
  function before_process() {
    global $response, $db, $order, $messageStack;

    $order->info['cc_number']  = str_pad(substr($_POST['cc_number'], -4), strlen($_POST['cc_number']), "X", STR_PAD_LEFT);
    $order->info['cc_expires'] = $_POST['cc_expires'];
    $order->info['cc_type']    = $_POST['cc_type'];
    $order->info['cc_owner']   = $_POST['cc_owner'];
    $order->info['cc_cvv']     = '';
    $sessID = zen_session_id();

    // DATA PREPARATION SECTION
    unset($submit_data);  // Cleans out any previous data stored in the variable

    // Create a variable that holds the order time
    $order_time = date("F j, Y, g:i a");

    // Calculate the next expected order id (adapted from code written by Eric Stamper - 01/30/2004 Released under GPL)
    $last_order_id = $db->Execute("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1");
    $new_order_id = $last_order_id->fields['orders_id'];
    $new_order_id = ($new_order_id + 1);

    // add randomized suffix to order id
    $new_order_id = (string)$new_order_id . '-' . zen_create_random_value(6);

	//REQUEST
    // Populate an array that contains all of the data to be sent to MeS

    $submit_data = array('profile_id' => trim(MODULE_PAYMENT_MES_LOGIN),
                         'profile_key' => trim(MODULE_PAYMENT_MES_TXNKEY),
                         'transaction_type' => MODULE_PAYMENT_MES_AUTHORIZATION_TYPE == 'Authorize Only' ? 'P': 'D',
                         'transaction_amount' => number_format($order->info['total'], 2),
                         'card_number' => $_POST['cc_number'],
                         'card_exp_date' => $_POST['cc_expires'],
                         'cvv2' => $_POST['cc_cvv'],
                         'invoice_number' => (MODULE_PAYMENT_MES_TESTMODE == 'Test' ? 'TEST-' : '') . $new_order_id,
                         'cardholder_street_address' => $order->billing['street_address'],
                         'cardholder_zip' => $order->billing['postcode'],
                         'ship_to_zip' => $order->delivery['postcode'],
                         'tax_amount' => number_format((float)$order->info['tax'],2),
                         'echo_Date' => $order_time,
                         'echo_Session' => $sessID );
	
	//RESPONSE
    unset($response);
    $response = $this->_sendRequest($submit_data);
    $this->transaction_id = $response['transaction_id'];
    $response_code = $response['error_code'];
    $response_text = $response['auth_response_text'];
    $this->auth_code = $response['auth_code'];
    $response_msg_to_customer = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');
	
	$this->_debugActions($response, $order_time, $sessID);
	
    // If the response code is not 000 (approved) then redirect back to the payment page with the appropriate error message
    if ($response_code != '000') {
      $messageStack->add_session('checkout_payment', $response_msg_to_customer . ' - ' . MODULE_PAYMENT_MES_TEXT_DECLINED_MESSAGE, 'error');
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }
  }
  
  /**
   * Post-process activities. Updates the order-status history data with the auth code from the transaction.
   *
   * @return boolean
   */
  function after_process() {
    global $insert_id, $db;
	
	$comment = 'Credit Card payment.  AUTH: ' . $this->auth_code . '. TransID: ' . $this->transaction_id . '.';
    $sql = 'insert into ' . TABLE_ORDERS_STATUS_HISTORY . '
			(comments, orders_id, orders_status_id, customer_notified, date_added)
			values ("'.$comment.'", "'.$insert_id.'", "'.$this->order_status.'", 0, now() )';
	
    $db->Execute($sql);
    return false;
  }
  /**
    * Build admin-page components
    *
    * @param int $zf_order_id
    * @return string
    */
  function admin_notification($zf_order_id) {
    global $db;
	
	$order = $db->Execute("SELECT * FROM " . TABLE_ORDERS . " WHERE orders_id = ".$zf_order_id." limit 1");
	$status = $db->Execute("SELECT * FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = " . $zf_order_id . " order by orders_status_history_id asc");
	
	$comments = array();
	$tranId = '';
	$output = '';
	$amount = $order->fields['order_total'];
	
    while (!$status->EOF) {
		array_push($comments, $status->fields);
		
		$search = strstr($status->fields['comments'], "TransID");
		if( $search )
			$tranId = substr($search, 9, 32);
		$status->MoveNext();
    }
	
	//Include Admin functionality for the order page
    require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/mes/mes_admin_notification.php');
    return $output;
  }

  /**
   * Used to display error message details
   *
   * @return array
   */
  function get_error() {
    $error = array('title' => MODULE_PAYMENT_MES_TEXT_ERROR,
                   'error' => stripslashes(urldecode($_GET['error'])));
    return $error;
  }
  
  /**
   * Check to see whether module is installed
   *
   * @return boolean
   */
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MES_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }
  
  /**
   * Install the payment module and its configuration settings
   *
   */
  function install() {

    global $db, $messageStack;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Merchant e-Solutions Module', 'MODULE_PAYMENT_MES_STATUS', 'True', 'Do you want to accept MeS payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Profile ID', 'MODULE_PAYMENT_MES_LOGIN', 'Profile ID', 'The Profile ID linked to your merchant account', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Profile Key', 'MODULE_PAYMENT_MES_TXNKEY', 'Profile Key', 'The Profile Key linked to your Profile ID', '6', '0', now(), 'zen_cfg_password_display')");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Mode', 'MODULE_PAYMENT_MES_TESTMODE', 'Test', 'Transaction mode used for processing orders', '6', '0', 'zen_cfg_select_option(array(\'Test\', \'Production (Requires SSL)\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Authorization Type', 'MODULE_PAYMENT_MES_AUTHORIZATION_TYPE', 'Immediate Sale', 'Do you want transactions to be authorized, or authorized and captured immediatly?<br /><i>Transactions run as authorize only will need to be settled on the orders screen.</i>', '6', '0', 'zen_cfg_select_option(array(\'Authorize Only\', \'Immediate Sale (Auth+Capture)\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Request CVV Number', 'MODULE_PAYMENT_MES_USE_CVV', 'True', 'Do you want to ask the customer for the card\'s CVV number (Recommended)?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order', 'MODULE_PAYMENT_MES_SORT_ORDER', '0', 'Sort order of this module . Lowest is displayed first.', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Completed Order Status', 'MODULE_PAYMENT_MES_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refunded Order Status', 'MODULE_PAYMENT_MES_REFUNDED_ORDER_STATUS_ID', '1', 'Set the status of refunded orders to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Debug Mode', 'MODULE_PAYMENT_MES_DEBUGGING', 'Off', 'Would you like to enable debug mode?  A complete detailed log of failed transactions may be emailed to the store owner.', '6', '0', 'zen_cfg_select_option(array(\'Off\', \'Log File\', \'Log and Email\', \'Echo\'), ', now())");
  }
  
  /**
   * Remove the module and all its settings
   *
   */
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_PAYMENT\_MES\_%'");
  }
  
  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
   */
  function keys() {
    return array('MODULE_PAYMENT_MES_STATUS', 'MODULE_PAYMENT_MES_LOGIN', 'MODULE_PAYMENT_MES_TXNKEY', 'MODULE_PAYMENT_MES_TESTMODE', 'MODULE_PAYMENT_MES_AUTHORIZATION_TYPE', 'MODULE_PAYMENT_MES_USE_CVV', 'MODULE_PAYMENT_MES_SORT_ORDER', 'MODULE_PAYMENT_MES_ORDER_STATUS_ID', 'MODULE_PAYMENT_MES_REFUNDED_ORDER_STATUS_ID', 'MODULE_PAYMENT_MES_DEBUGGING');
  }
  
  /**
   * Send communication request
   */
  function _sendRequest($submit_data) {

    // Populate an array that contains all of the data to be sent to MeS
    $submit_data = array_merge(array(
                         'profile_id' => trim(MODULE_PAYMENT_MES_LOGIN),
                         'profile_key' => trim(MODULE_PAYMENT_MES_TXNKEY),
                         ), $submit_data);

    $url = 'https://api.merchante-solutions.com/mes-api/tridentApi';

    if(MODULE_PAYMENT_MES_TESTMODE == 'Test') {
      $url = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
    }
	
    // concatenate the submission data into $data variable after sanitizing to protect delimiters
    $data = '';
    while(list($key, $value) = each($submit_data)) {
      if ($key != 'x_delim_char' && $key != 'x_encap_char') {
        $value = str_replace(array($this->delimiter, $this->encapChar,'"',"'",'&amp;','&', '='), '', $value);
      }
      $data .= $key . '=' . urlencode($value) . '&';
    }
    // Remove the last "&" from the string
    $data = substr($data, 0, -1);


    // prepare a copy of submitted data for error-reporting purposes
    $this->reportable_submit_data = $submit_data;
    $this->reportable_submit_data['profile_id'] = '*******';
    $this->reportable_submit_data['profile_key'] = '*******';
    if (isset($this->reportable_submit_data['card_number'])) $this->reportable_submit_data['card_number'] = str_repeat('X', strlen($this->reportable_submit_data['card_number'] - 4)) . substr($this->reportable_submit_data['card_number'], -4);
    if (isset($this->reportable_submit_data['cvv2'])) $this->reportable_submit_data['cvv2'] = '****';
    $this->reportable_submit_data['url'] = $url;


    // Post order info data to MeS via CURL - Requires that PHP has cURL support installed

    // Send CURL communication
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); /* compatibility for SSL communications on some Windows servers (IIS 5.0+) */
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    
    if (CURL_PROXY_REQUIRED == 'True') {
      $this->proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
      curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $this->proxy_tunnel_flag);
      curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
      curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
    }

    $this->authorize = curl_exec($ch);
    $this->commError = curl_error($ch);
    $this->commErrNo = curl_errno($ch);

    $this->commInfo = @curl_getinfo($ch);
    curl_close($ch);

    // if in 'echo' mode, dump the returned data to the browser and stop execution
    if (MODULE_PAYMENT_MES_DEBUGGING == 'Echo') {
      echo $this->authorize . ($this->commErrNo != 0 ? '<br />' . $this->commErrNo . ' ' . $this->commError : '') . '<br />';
      die('Press the BACK button in your browser to return to the previous page.');
    }

    $stringToParse = $this->authorize;

// !....
//    if (substr($stringToParse,0,1) == $this->encapChar) $stringToParse = substr($stringToParse,1);
//    $stringToParse = preg_replace('/.{*}' . $this->encapChar . '$/', '', $stringToParse);
//    $response = explode($this->encapChar . $this->delimiter . $this->encapChar, $stringToParse);

// Brice 6/16/08
// Changed response keys from numbers to the response field name. 
// Much clearer when parsing the response, as MeS's response may not always be in a certain order.

    $rFields = explode("&",$stringToParse); 

    foreach($rFields as $field) { 
      $nameValue = explode("=",$field); 
      $responseFields[$nameValue[0]] = $nameValue[1]; 
    } 
    return $responseFields;
  }
  
  /**
   * Used to do any debug logging / tracking / storage as required.
   */
  function _debugActions($response, $order_time= '', $sessID = '') {
    global $db, $messageStack;
    if ($order_time == '') $order_time = date("F j, Y, g:i a");
	
    $resp_output[] = 'Response from gateway';
    $resp_output = array_reverse($resp_output);
	
    // DEBUG LOGGING
    $errorMessage = date('M-d-Y h:i:s') .
                      "\n=================================\n\n" .
                      ($this->commError !='' ? 'Comm results: ' . $this->commErrNo . ' ' . $this->commError . "\n\n" : '') .
                      'Response Code: ' . $response['error_code'] . ".\nResponse Text: " . $response['auth_response_text'] . "\n\n" .
                      'Sending to MeS: ' . print_r($this->reportable_submit_data, true) . "\n\n" .
                      'Results Received back from MeS: ' . print_r($resp_output, true) . "\n\n" .
                      'CURL communication info: ' . print_r($this->commInfo, true) . "\n";
    if (CURL_PROXY_REQUIRED == 'True')
		$errorMessage .= 'Using CURL Proxy: [' . CURL_PROXY_SERVER_DETAILS . ']  with Proxy Tunnel: ' .($this->proxy_tunnel_flag ? 'On' : 'Off') . "\n";
    $errorMessage .= "\nRAW data received: \n" . $this->authorize . "\n\n";
		
	//Log the response data into a file, if configured in settings
    if(strstr(MODULE_PAYMENT_MES_DEBUGGING, 'Log')) {
		$key = time() . '_' . zen_create_random_value(4);
        $file = $this->_logDir . '/' . 'MES_Debug_' . $key . '.log';
        if ($fp = @fopen($file, 'a')) {
			fwrite($fp, $errorMessage);
			fclose($fp);
        }
    }
	
    if (strstr(MODULE_PAYMENT_MES_DEBUGGING, 'Email')) {
		zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'MeS Alert ' . $response[7] . ' ' . date('M-d-Y h:i:s') . ' ' . $response[6], $errorMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, array('EMAIL_MESSAGE_HTML'=>nl2br($errorMessage)), 'debug');
    }
  }
  
  /**
   * Used to submit a refund for a given transaction.
   */
  function _doRefund($oID, $amount = 0) {
    global $db, $messageStack;
    $new_order_status = (int)MODULE_PAYMENT_MES_REFUNDED_ORDER_STATUS_ID;
    if ($new_order_status == 0) $new_order_status = 1;
    $proceedToRefund = true;
    $refundNote = strip_tags(zen_db_input($_POST['refnote']));
	
    if (isset($_POST['buttonrefund'])) {
      if ($_POST['refconfirm'] != 'on') {
        $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_REFUND_CONFIRM_ERROR, 'error');
        $proceedToRefund = false;
      }
    }
	
    if (isset($_POST['trans_id']) && trim($_POST['trans_id']) == '') {
      $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
      $proceedToRefund = false;
    }
	
    /**
     * Submit refund request to gateway
     */
    if ($proceedToRefund) {
		$submit_data = array('transaction_type' => 'U',
                           'transaction_amount' => number_format($_POST['refamt'], 2, '.', ''),
                           'transaction_id' => trim($_POST['trans_id'])
                           );
		unset($response);
		
		$response = $this->_sendRequest($submit_data);
		$response_code = $response[error_code];
		$response_text = $response[auth_response_text];
		$response_alert = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');
		$this->reportable_submit_data['Note'] = $refundNote;
		$this->_debugActions($response);
		
		if ($response_code != '000') {
			$messageStack->add_session($response_alert, 'error');
		} else {
			// Success, so save the results
			$sql_data_array = array('orders_id' => $oID,
									'orders_status_id' => (int)$new_order_status,
									'date_added' => 'now()',
									'comments' => 'REFUND INITIATED. Trans ID: ' . $response[transaction_id] . ' ' . "\n" . ' Response: ' . $response[auth_response_text] . "\n" . $refundNote,
									'customer_notified' => 0
								);
			zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
			$db->Execute("update " . TABLE_ORDERS  . "
						set orders_status = '" . (int)$new_order_status . "'
						where orders_id = '" . (int)$oID . "'");
			$messageStack->add_session(sprintf(MODULE_PAYMENT_MES_TEXT_REFUND_INITIATED, $response[auth_response_text]), 'success');
			return true;
		}
    }
    return false;
  }

  /**
   * Used to capture part or all of a given previously-authorized transaction.
   */
  function _doCapt($oID, $amt = 0, $currency = 'USD') {
    global $db, $messageStack;

    //@TODO: Read current order status and determine best status to set this to
    $new_order_status = (int)MODULE_PAYMENT_MES_ORDER_STATUS_ID;
    if ($new_order_status == 0) $new_order_status = 1;

    $proceedToCapture = true;
    $captureNote = strip_tags(zen_db_input($_POST['captnote']));
	
    if (isset($_POST['btndocapture'])) {
      if ($_POST['captconfirm'] != 'on') {
        $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_CAPTURE_CONFIRM_ERROR, 'error');
        $proceedToCapture = false;
      }
    }
	
    if (isset($_POST['btndocapture']) && $_POST['btndocapture'] == MODULE_PAYMENT_MES_ENTRY_CAPTURE_BUTTON_TEXT) {
      $captureAmt = (float)$_POST['captamt'];
	
    }
    if (isset($_POST['captauthid']) && trim($_POST['captauthid']) != '') {
      // okay to proceed
    } else {
      $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
      $proceedToCapture = false;
    }
	
    /**
     * Submit capture request to MeS
     */
    if ($proceedToCapture) {
      // Populate an array that contains all of the data to be sent to MeS
      unset($submit_data);
      $submit_data = array('transaction_type' => 'S',
                           'transaction_amount' => number_format($captureAmt, 2),
                           'transaction_id' => strip_tags(trim($_POST['captauthid'])),
                           'invoice_num' => $new_order_id,
                           'tax_amount' => $order->info['tax'],
                           );
	  
      $response = $this->_sendRequest($submit_data);
      $response_code = $response[error_code];
      $response_text = $response[auth_response_text];
      $response_alert = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');
      $this->reportable_submit_data['Note'] = $captureNote;
      $this->_debugActions($response);

      if ($response_code != '000') {
        $messageStack->add_session($response_alert, 'error');
      } else {
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'FUNDS COLLECTED. Amount: ' . $captureAmt  . "\n" . ' Trans ID: ' . $response[transaction_id] . "\n" . ' Response: ' . $response[auth_response_text] . "\n" . 'Time: ' . date('Y-m-D h:i:s') . "\n" . $captureNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_MES_TEXT_CAPT_INITIATED, $response[transaction_id], $response[auth_response_text], $response[error_code]), 'success');
        return true;
      }
    }
    return false;
  }
  
  /**
   * Used to void a given previously-authorized transaction.
   */
  function _doVoid($oID, $note = '') {
    global $db, $messageStack;

    $new_order_status = (int)MODULE_PAYMENT_MES_REFUNDED_ORDER_STATUS_ID;
    if ($new_order_status == 0) $new_order_status = 1;
    $voidNote = strip_tags(zen_db_input($_POST['voidnote'] . $note));
    $voidAuthID = trim(strip_tags(zen_db_input($_POST['voidauthid'])));
    $proceedToVoid = true;
	
    if (isset($_POST['ordervoid'])) {
      if ($_POST['voidconfirm'] != 'on') {
        $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_VOID_CONFIRM_ERROR, 'error');
        $proceedToVoid = false;
      }
    }
    if ($voidAuthID == '') {
      $messageStack->add_session(MODULE_PAYMENT_MES_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
      $proceedToVoid = false;
    }
    // Populate an array that contains all of the data to be sent to gateway
    $submit_data = array('transaction_type' => 'V',
                         'transaction_id' => trim($voidAuthID) );
    /**
     * Submit void request to Gateway
     */
    if ($proceedToVoid) {
      $response = $this->_sendRequest($submit_data);
      $response_code = $response[error_code];
      $response_text = $response[auth_response_text];
      $response_alert = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');
      $this->reportable_submit_data['Note'] = $voidNote;
      $this->_debugActions($response);

      if ($response_code != '000') {
        $messageStack->add_session($response_alert, 'error');
      } else {
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'VOIDED. Trans ID: ' . $response[transaction_id] . ' ' . $response[auth_response_text] . "\n" . $voidNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_MES_TEXT_VOID_INITIATED, $response[auth_response_text], $response[transaction_id]), 'success');
        return true;
      }
    }
    return false;
  }

}
?>