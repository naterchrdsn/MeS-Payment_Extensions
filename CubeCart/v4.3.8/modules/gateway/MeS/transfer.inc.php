<?php
/*
 * CubeCart Payment Module for MeS Payment Gateway
 * Copyright (c) 2010 Merchant e-Solutions
 * All rights reserved.
 * Author: Ben Rice <brice@merchante-solutions.com>
 */
 
 
if (!defined('CC_INI_SET')) die("Access Denied");

function repeatVars() { return FALSE; }

function fixedVars() { return FALSE; }

$formAction = "index.php?_g=co&amp;_a=step3&amp;process=1&amp;cart_order_id=".$_GET['cart_order_id'];
$formMethod = "post";
$formTarget = "_self";
$transfer = "manual";
?>