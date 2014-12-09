<?php
//  Date:	02/11/2010
//  File:	mesAdmin.php
//  Author:	b.rice
//	Desc:	Template for administrative options.
//  ©Merchant e-Solutions 2010

$html .= '<img src="../modules/mes/mes.jpg" style="float:left; margin-right:15px;"><b>';
$html .= '<br />This module allows you to accept payment through the Merchant e-Solutions Payment Gateway.</b><br /><br />';
$html .= 'For full documentation visit <a href="http://resources.merchante-solutions.com" target="_t">resources.merchante-solutions.com</a>.<br />';
$html .= 'For Back Office access, visit <a href="http://www.merchante-solutions.com" target="_t">www.merchante-solutions.com</a>, and log in with your merchant credentials.<br /><br /><br />';

Configuration::get('MES_APIURL') == "cert.merchante-solutions.com" ? $sandbox_yes = " selected" : $sandbox_no = " selected";
Configuration::get('MES_CVVFLAG') == "yes" ? $cvv_yes = " selected" : $cvv_no = " selected";
Configuration::get('MES_TRANSACTIONTYPE') == "D" ? $tt_sale = " selected" : $tt_pre = " selected";

Configuration::get('MES_VISA') == "on" ? $visa = " checked" : $visa = "" ;
Configuration::get('MES_MASTERCARD') == "on" ? $mc = " checked" : $mc = "" ;
Configuration::get('MES_DISCOVER') == "on" ? $dc = " checked" : $dc = "" ;
Configuration::get('MES_AMEX') == "on" ? $amex = " checked" : $amex = "" ;

$html .=  
'<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
	<fieldset>
	<legend><img src="../img/admin/contact.gif" />Gateway details</legend>
		<table border="0" width="700" cellpadding="4" cellspacing="0" id="form">
			<tr><td colspan="2">Please specify the gateway details supplied by a MeS VAR form, sales representative, or certification manager.<br /><br /></td></tr>
			
			<tr><td width="240" style="height: 35px;">Profile ID</td><td><input type="text" name="profileID" value="'.htmlentities(Tools::getValue('profileID', $this->_profileID), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" /></td></tr>
            <tr><td width="240" style="height: 35px;">Profile Key</td><td><input type="text" name="profileKey" value="'.htmlentities(Tools::getValue('profileKey', $this->_profileKey), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" /></td></tr>
			<tr>
				<td width="240" style="height: 35px;">
					Transaction Type
				</td>
				<td>
					<select name="transaction_type">
						<option value="D"'.$tt_sale.'>Sale (Auth and Capture)</option>
						<option value="P"'.$tt_pre.'>Pre-Auth (Authorization Only)</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td width="240" style="height: 35px;">
					Use the Sandbox Environment?
				</td>
				<td>
					<select name="apiURL">
						<option value="cert.merchante-solutions.com"'.$sandbox_yes.'>Yes</option>
						<option value="api.merchante-solutions.com"'.$sandbox_no.'>No</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td width="240" style="height: 35px;">
					Will you require the cardholder to enter the CVV2?
				</td>
				<td>
					<select name="cvvflag">
						<option value="yes"'.$cvv_yes.'>Yes</option>
						<option value="no"'.$cvv_no.'>No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="240" style="height: 35px;">
					Select which card types your Merchant e-Solutions account is set up to take.
				</td>
				<td>
					<input type="checkbox" name="visa"'.$visa.'> Visa <br />
					<input type="checkbox" name="mastercard"'.$mc.'> Mastercard <br />
					<input type="checkbox" name="discover"'.$dc.'> Discover <br />
					<input type="checkbox" name="amex"'.$amex.'> American Express <br />
				</td>
			</tr>
			<tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="Update settings" type="submit" /></td></tr>
		</table>
	</fieldset>
</form>';

?>