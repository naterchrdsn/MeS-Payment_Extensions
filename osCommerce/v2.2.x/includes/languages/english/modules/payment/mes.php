<?php

/**
 * Merchant e-Solutions Payment Module Language definitions
 *
 * @copyright Copyright 2008 Merchant e-Solutions
 */

  define('MODULE_PAYMENT_MES_TEXT_TITLE', 'Merchant e-Solutions');
  define('MODULE_PAYMENT_MES_TEXT_PUBLIC_TITLE', 'Credit Card (mes)');

  if (MODULE_PAYMENT_MES_STATUS == 'True')
  {
    define('MODULE_PAYMENT_MES_TEXT_DESCRIPTION', '<a target="_blank" href="https://www.merchante-solutions.com/">Merchant e-Solutions Merchant Login</a>' . (MODULE_PAYMENT_MES_TESTMODE != 'Production' ? '<br />Configuration' : ''));
  }
  else
  { 
    define('MODULE_PAYMENT_MES_TEXT_DESCRIPTION', ' <br /><br /><strong>Requirements:</strong><br /><hr />*<strong>MeS Merchant Account</strong><br />*<strong>PHP CURL is required </strong><br />*<strong>Profile ID and Profile Key are required</strong><br />*<strong>Javascript must be enabled</strong>');
  }

  define('MODULE_PAYMENT_MES_CREDIT_CARD_OWNER',      'Credit Card Owner:');
  define('MODULE_PAYMENT_MES_CREDIT_CARD_NUMBER',     'Credit Card Number:');
  define('MODULE_PAYMENT_MES_CREDIT_CARD_EXPIRES',    'Credit Card Expiry Date:');
  define('MODULE_PAYMENT_MES_CREDIT_CARD_CVC',        'CVV Number:');
  define('MODULE_PAYMENT_MES_ERROR_TITLE',            'There has been an error processing your credit card');
  define('MODULE_PAYMENT_MES_ERROR_GENERAL',          'Please try again and if problems persist, please try another payment method.');
  define('MODULE_PAYMENT_MES_ERROR_DECLINED',         'This credit card transaction has been declined. If problems persist, please try another credit card or payment method.');
  define('MODULE_PAYMENT_MES_ERROR_INVALID_EXP_DATE', 'The credit card expiration date is invalid. Please check the card information and try again.');
  define('MODULE_PAYMENT_MES_ERROR_EXPIRED',          'The credit card has expired. Please try again with another card or payment method.');
  define('MODULE_PAYMENT_MES_ERROR_CVV',              'The transaction has been declined because the CVV value is incorrect. Please check the card information and try again.');
  define('MODULE_PAYMENT_MES_ERROR_AVS_FILTER',       'Your billing information did not match what is on file with the card issuer. Check the billing information and try again, or try another payment method.');
  define('MODULE_PAYMENT_MES_ERROR_CARD_ERROR',       'The card number entered was invalid. Please check the card number and try again.');
  define('MODULE_PAYMENT_MES_ERROR_NOT_ACCEPTED',     'That card type is not recognized or accepted at this time. If you believe this is in error, please re-key the card number and try again.');
?>