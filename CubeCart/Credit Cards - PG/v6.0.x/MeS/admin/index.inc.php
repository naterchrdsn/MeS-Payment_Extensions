<?php
/*
 * CubeCart Payment Module for MeS Payment Gateway Credit Card Transactions
 * Copyright (c) 2015 Merchant e-Solutions
 * All rights reserved.
 * Author: Nate Richardson <nrichardson@merchante-solutions.com>
 */

if(!defined('CC_INI_SET')) die('Access Denied');
$module   = new Module(__FILE__, $_GET['module'], 'admin/index.tpl', true);
$page_content = $module->display();