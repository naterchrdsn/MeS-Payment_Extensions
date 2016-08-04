<?php
//  Date:	02/11/2010
//  File:	payment_return.php
//  Author:	b.rice
//	Desc:	Template for completing the order.
//  ©Merchant e-Solutions 2010

if($success)
{
	$invoice_number = $_GET['id_order'];
	$auth_code = $_GET['auth_code'];
	$html .= "
	<p>
	    <div style='background-color: #AAFFAA; border-bottom: 1px solid #229922; border-top: 1px solid #448844; padding: 8px;'>
		  Your order is complete.
		</div>
		<br /><br />
		Order Details:<br />
		- Order Number : ".$invoice_number."<br />
		<br />Click here for a 
        <a href='".__PS_BASE_URI__."/pdf-invoice.php?id_order=".$invoice_number."' title='Invoice'>
		  <img src='".__PS_BASE_URI__."/themes/prestashop/img/icon/pdf.gif' alt='Invoice' class='icon' /> PDF Invoice
		</a>
		, or proceed to your <a href='".__PS_BASE_URI__."/history.php'>order history</a> for full details.
		<br />For any questions or for further information, please contact our <a href='".__PS_BASE_URI__."contact-form.php'>customer support</a>.
	</p>
	";
	
}
else
{
	$html = "
	<p class='warning'>
		We noticed a problem with your order. If you think this is an error, you can contact our
		<a href='".__PS_BASE_URI__."contact-form.php'>customer support</a>.
	</p>
	";
}
?>