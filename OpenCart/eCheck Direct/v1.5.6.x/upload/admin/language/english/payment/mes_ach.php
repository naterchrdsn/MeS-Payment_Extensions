<?php
/**
* Custom Payment Gateway for OpenCart v1.5.6.4
* Using Payment Gateway API v4.12
* For ACH Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

// Heading
$_['heading_title']       = 'Merchant e-Solutions - eCheck Direct';

// Text
$_['text_success']        = 'MeS eCheck settings have been updated!';
$_['text_fraud_settings'] = 'Fraud Settings';
$_['text_settings']       = 'General Settings';
$_['text_mes_ach']        = '<a onclick="window.open(\'https://www.merchante-solutions.com\');"><img src="view/image/payment/mes_ach.png" alt="Merchant e-Solutions" title="MeS" style="border: 1px solid #EEEEEE;" /></a>';

// Entry
$_['entry_profile_id']    = 'MeS Profile ID:';
$_['entry_profile_key']   = 'MeS Profile Key:';
$_['entry_test']          = 'Test Mode:<br /><span class="help">Use the production server or simulator sandbox to process transactions?</span>';
$_['entry_order_status']  = 'Order Status:';
$_['entry_fraud_order_status'] = 'Fraud Order Status:<br /><span class="help">Status of orders that are declined due to Fraud Services settings</span>';
$_['entry_geo_zone']      = 'Geo Zone:';
$_['entry_status']        = 'Status:';
$_['entry_sort_order']    = 'Sort Order:';
$_['entry_merch_id']      = 'Merchant Id:';
$_['entry_cust_id']       = 'Customer Id:<br /><span class="help">If your provider gave you a Customer ID #, place it here.</span>';
$_['entry_fraud_status']  = 'Fraud Services Status:<br /><span class="help">IMPORTANT NOTE:<br />You must have Fraud Services activated on your account for this feature to work correctly.</span>';


// Error
$_['error_ach_permission']    = 'Warning: You do not have permission to modify payment!';
$_['error_ach_username']      = 'MeS Profile ID is Required!'; 
$_['error_ach_password']      = 'MeS Profile Key is Required!';
$_['error_ach_cust_id']       = 'Customer ID is Required!';
$_['error_merch_id']      = 'Merchant ID is Required when Fraud Services is activated!';
?>