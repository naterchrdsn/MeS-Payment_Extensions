<?php
/**
* Custom Payment Gateway for OpenCart v2
* Using Payment Gateway API v4.12
* For Credit Card Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

// Heading
$_['heading_title']       = 'Merchant e-Solutions';

// Text 
$_['text_payment']        = 'Payment';
$_['text_success']        = 'MeS Plugin settings have been updated!';
$_['text_edit']           = 'Edit Merchant e-Solutions Settings';
$_['text_authorization']  = 'Pre-Authorization';
$_['text_sale']           = 'Sale';
$_['text_mes']     	 	  = '<a target="_BLANK" href="https://merchante-solutions.com"><img src="view/image/payment/mes.png" alt="Merchant e-Solutions" title="MeS" style="border: 1px solid #EEEEEE;" /></a>';

// Entry
$_['entry_profile_id']    = 'MeS Profile ID';
$_['entry_profile_key']   = 'MeS Profile Key';
$_['entry_test']          = 'Sandbox Mode';
$_['entry_auth']          = 'Transaction Type';
$_['entry_review_status'] = 'Fraud Services Status';
$_['entry_completed_status']        = 'Completed Status';
$_['entry_geo_zone']      = 'Geo Zone';
$_['entry_status']        = 'Status';
$_['entry_sort_order']    = 'Sort Order';
$_['entry_merch_id']      = 'Merchant Id';

// Tab
$_['tab_general']         = 'General';
$_['tab_extras']          = 'Extras';

// Help
$_['help_test']           = 'Use the live or testing (sandbox) gateway server to process transactions?';
$_['help_review']         = 'Status for transactions declined due to Fraud Services settings';
$_['help_auth']           = 'Use the live gateway server to process Pre-Authorizations or Sales Transactions?';
$_['help_merch_id']       = 'IMPORTANT NOTE:<br />If MeS gave you a Merchant ID, you must enter that number here.<br />(If no number is entered, Fraud Services will not be activated for your transactions.)';

// Error
$_['error_permission']    = 'Warning: You do not have permission to modify payment MeS!';
$_['error_profile_id']    = 'MeS Profile ID is Required!'; 
$_['error_profile_key']   = 'MeS Profile Key is Required!'; 
?>