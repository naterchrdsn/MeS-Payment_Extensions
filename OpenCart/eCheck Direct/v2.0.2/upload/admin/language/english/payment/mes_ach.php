<?php
/**
* Custom Payment Gateway for OpenCart v2
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
$_['text_payment']        = 'Payment';
$_['text_success']        = 'MeS eCheck Plugin settings have been updated!';
$_['text_edit']           = 'Edit Merchant e-Solutions - eCheck Direct Settings';
$_['text_mes_ach']     	 	  = '<a target="_BLANK" href="https://merchante-solutions.com"><img src="view/image/payment/mes_ach.png" alt="Merchant e-Solutions" title="MeS" style="border: 1px solid #EEEEEE;" /></a>';

// Entry
$_['entry_profile_id']    = 'MeS Profile ID';
$_['entry_profile_key']   = 'MeS Profile Key';
$_['entry_test']          = 'Sandbox Mode';
$_['entry_completed_status']        = 'Completed Status';
$_['entry_geo_zone']      = 'Geo Zone';
$_['entry_status']        = 'Status';
$_['entry_sort_order']    = 'Sort Order';
$_['entry_cust_id']       = 'Customer Id';

// Tab
$_['tab_general']         = 'General';
$_['tab_extras']          = 'Extras';

// Help
$_['help_ach_test']           = 'Use the live or testing (sandbox) gateway server to process transactions?';
// Error
$_['error_ach_permission']    = 'Warning: You do not have permission to modify payment MeS_ACH!';
$_['error_ach_profile_id']    = 'MeS Profile ID is Required!'; 
$_['error_ach_profile_key']   = 'MeS Profile Key is Required!';
$_['error_ach_cust_id']   = 'Customer ID is Required!';
?>