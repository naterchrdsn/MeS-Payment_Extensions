<?php
/**
 * Merchant e-Solutions Payment Module Language definitions
 *
 * @copyright Copyright 2008-2011 Merchant e-Solutions
 * @copyright Portions Copyright 2003-2007 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 */


// Admin Configuration Items
  define('MODULE_PAYMENT_MES_TEXT_ADMIN_TITLE', 'Merchant e-Solutions'); // Payment option title as displayed in the admin

  if (MODULE_PAYMENT_MES_STATUS == 'True') {
    define('MODULE_PAYMENT_MES_TEXT_DESCRIPTION', '<a target="_blank" href="https://www.merchante-solutions.com/">Merchant e-Solutions Merchant Login</a>' . (MODULE_PAYMENT_MES_TESTMODE != 'Production' ? '<br />Configuration' : ''));
  } else { 
 define('MODULE_PAYMENT_MES_TEXT_DESCRIPTION', ' <br /><br /><strong>Requirements:</strong><br /><hr />*<strong>MeS Merchant Account</strong><br />*<strong>PHP CURL is required </strong><br />*<strong>Profile ID and Profile Key are required</strong>');
  }
  define('MODULE_PAYMENT_MES_TEXT_ERROR_CURL_NOT_FOUND', 'CURL functions not found - required for MeS payment module');

// Catalog Items
  define('MODULE_PAYMENT_MES_TEXT_CATALOG_TITLE', 'Credit Card');  // Payment option title as displayed to the customer
  define('MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_TYPE', 'Credit Card Type:');
  define('MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_OWNER', 'Cardholder Name:');
  define('MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_NUMBER', 'Credit Card Number:');
  define('MODULE_PAYMENT_MES_TEXT_CREDIT_CARD_EXPIRES', 'Expiry Date:');
  define('MODULE_PAYMENT_MES_TEXT_CVV', 'CVV Number:');
  define('MODULE_PAYMENT_MES_TEXT_POPUP_CVV_LINK', 'What\'s this?');
  define('MODULE_PAYMENT_MES_TEXT_JS_CC_OWNER', '* The owner\'s name of the credit card must be at least ' . CC_OWNER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_MES_TEXT_JS_CC_NUMBER', '* The credit card number must be at least ' . CC_NUMBER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_MES_TEXT_JS_CC_CVV', '* The 3 or 4 digit CVV number must be entered from the back of the credit card.\n');
  define('MODULE_PAYMENT_MES_TEXT_DECLINED_MESSAGE', 'Your credit card could not be authorized for this reason. Please correct the information and try again or contact us for further assistance.');
  define('MODULE_PAYMENT_MES_TEXT_ERROR', 'Credit Card Error!');
  define('MODULE_PAYMENT_MES_TEXT_AUTHENTICITY_WARNING', 'WARNING: Security hash problem. Please contact store-owner immediately. Your order has *not* been fully authorized.');

// admin tools:
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_BUTTON_TEXT', 'Refund');
  define('MODULE_PAYMENT_MES_TEXT_REFUND_CONFIRM_ERROR', 'Error: You requested to do a refund but did not check the Confirmation box.');
  define('MODULE_PAYMENT_MES_TEXT_INVALID_REFUND_AMOUNT', 'Error: You requested a refund but entered an invalid amount.');
  define('MODULE_PAYMENT_MES_TEXT_CC_NUM_REQUIRED_ERROR', 'Error: You requested a refund but didn\'t enter the last 4 digits of the Credit Card number.');
  define('MODULE_PAYMENT_MES_TEXT_REFUND_INITIATED', 'Refunded. Transaction ID: %s');
  define('MODULE_PAYMENT_MES_TEXT_CAPTURE_CONFIRM_ERROR', 'Error: You requested to a capture but did not check the Confirmation box.');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_BUTTON_TEXT', 'Capture');
  define('MODULE_PAYMENT_MES_TEXT_INVALID_CAPTURE_AMOUNT', 'Error: You requested a capture but need to enter an amount.');
  define('MODULE_PAYMENT_MES_TEXT_TRANS_ID_REQUIRED_ERROR', 'Error: You need to specify a Transaction ID.');
  define('MODULE_PAYMENT_MES_TEXT_CAPT_INITIATED', 'Funds Capture initiated. Transaction ID: %s.  Response: %s. Error Code: %s');
  define('MODULE_PAYMENT_MES_ENTRY_VOID_BUTTON_TEXT', 'Void');
  define('MODULE_PAYMENT_MES_TEXT_VOID_CONFIRM_ERROR', 'Error: You requested a Void but did not check the Confirmation box.');
  define('MODULE_PAYMENT_MES_TEXT_VOID_INITIATED', 'Void Initiated. Response: %s. Transaction ID: %s');


  define('MODULE_PAYMENT_MES_ENTRY_REFUND_TITLE', '<strong>Refund Transaction</strong>');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND', 'You may refund money to the customer\'s credit card here. The transaction must be settled to your account.');
  define('MODULE_PAYMENT_MES_TEXT_REFUND_CONFIRM_CHECK', 'Check this box to confirm your intent.');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_AMOUNT_TEXT', 'Enter the amount you wish to refund.');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_CC_NUM_TEXT', 'Enter the last 4 digits of the Credit Card you are refunding.');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_TRANS_ID', 'Enter the original Transaction ID.');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_TEXT_COMMENTS', 'Notes (will show in the Order History).');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_DEFAULT_MESSAGE', 'Refund Issued');
  define('MODULE_PAYMENT_MES_ENTRY_REFUND_SUFFIX', 'You may refund an order up to the amount already captured. You must supply the last 4 digits of the credit card number used on the initial order for verification.');

  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_TITLE', '<strong>Capture Transaction</strong>');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE', 'You may capture previously-authorized funds here.');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_AMOUNT_TEXT', 'Enter the amount to Capture. ');
  define('MODULE_PAYMENT_MES_TEXT_CAPTURE_CONFIRM_CHECK', 'Check this box to confirm your intent.');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_TRANS_ID', 'Enter the original Transaction ID.');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_TEXT_COMMENTS', 'Notes (will show on Order History).');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_DEFAULT_MESSAGE', 'Settled previously-authorized funds.');
  define('MODULE_PAYMENT_MES_ENTRY_CAPTURE_SUFFIX', 'Captures must be performed within 30 days of the original authorization. You may only capture an order once. <br />Please be sure the amount specified is correct.');

  define('MODULE_PAYMENT_MES_ENTRY_VOID_TITLE', '<strong>Void Transaction</strong>');
  define('MODULE_PAYMENT_MES_ENTRY_VOID', 'You may void a transaction which has not yet been settled.');
  define('MODULE_PAYMENT_MES_TEXT_VOID_CONFIRM_CHECK', 'Check this box to confirm your intent.');
  define('MODULE_PAYMENT_MES_ENTRY_VOID_TEXT_COMMENTS', 'Notes (will show on Order History).');
  define('MODULE_PAYMENT_MES_ENTRY_VOID_DEFAULT_MESSAGE', 'Transaction Cancelled');
  define('MODULE_PAYMENT_MES_ENTRY_VOID_SUFFIX', 'Voids must be completed before the original transaction is settled in the daily batch.');

?>