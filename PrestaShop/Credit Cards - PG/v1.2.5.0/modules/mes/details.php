<?php
//  Date:	02/11/2010
//  File:	details.php
//  Author:	b.rice
//	Desc:	Template for collecting card information.
//  ©Merchant e-Solutions 2010
?>

<h2>Credit Card Payment</h2>

<?php
if($error)
  echo('<div class="error">'.$error.'</div>');
?>

<form action="process.php" method="post">
	<fieldset>
	<legend><img src="lock.png" /> Payment Details</legend>
		<table border="0" width="550" cellpadding="0" cellspacing="0" id="form" style="padding: 4px; font-size: 12px;">
			<tr>
				<td colspan="2">
					Order Summary<br />
					You have chosen to pay with a Credit Card.<br />
					- The total amount of your order is
					<span id="amount" class="price">
					<?php echo( number_format(floatval($cart->getOrderTotal(true, 3)), 2, '.', '') );  ?>
					</span>
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<br />The following Card Types are accepted:<br />
					<?php
					if(Configuration::get('MES_VISA') == "on") { echo('<img src="'._MODULE_DIR_.'mes/img/visa.gif" alt="Visa" style="float:left;" />'); }
					if(Configuration::get('MES_MASTERCARD') == "on") { echo('<img src="'._MODULE_DIR_.'mes/img/mastercard.gif" alt="MasterCard" style="float:left;" />'); }
					if(Configuration::get('MES_DISCOVER') == "on") { echo('<img src="'._MODULE_DIR_.'mes/img/discover.gif" alt="Discover" style="float:left;" />'); }
					if(Configuration::get('MES_AMEX') == "on") { echo('<img src="'._MODULE_DIR_.'mes/img/amex.gif" alt="American Express" style="float:left;" />'); }
					?>
				</td>
			</tr>
			
			<tr>
				<td width="160" style="height: 35px;">Credit Card Number<span class="price">*</span></td>
				<td><input type="text" name="card_number" style="width: 160px;" maxlength="16" /></td>
			</tr>
			
            <tr>
				<td width="160" style="height: 35px;">Expiration Date<span class="price">*</span></td>
				<td>
					<select name="MM">
						<option value="01">Jan</option>
						<option value="02">Feb</option>
						<option value="03">Mar</option>
						<option value="04">Apr</option>
						<option value="05">May</option>
						<option value="06">Jun</option>
						<option value="07">Jul</option>
						<option value="08">Aug</option>
						<option value="09">Sep</option>
						<option value="10">Oct</option>
						<option value="11">Nov</option>
						<option value="12">Dec</option>
					</select>
					/
					<select name="YY">
						<option value="10">2010</option>
						<option value="11">2011</option>
						<option value="12">2012</option>
						<option value="13">2013</option>
						<option value="14">2014</option>
						<option value="15">2015</option>
						<option value="16">2016</option>
						<option value="17">2017</option>
						<option value="18">2018</option>
						<option value="19">2019</option>
						<option value="20">2020</option>
						<option value="21">2021</option>
						<option value="22">2022</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td width="160" style="height: 35px;">
					CVV Code
					<?php if(Configuration::get('MES_CVVFLAG') == "yes") { echo('<span class="price">*</span>'); } ?>
				</td>
				<td><input type="text" name="cvv2" style="width: 30px;" maxlength="3" /></td>
			</tr>
			
			<tr>
				<td colspan="2">
					The CVV code is a 3 or 4 digit number located on the back of Visa, MasterCards, Discovers, and on the front of American Express cards.<br />
					<img src="<?php echo(_MODULE_DIR_); ?>mes/img/visa_cvv2.gif" alt="Visa CVV2" style="float:left;" />
					<img src="<?php echo(_MODULE_DIR_); ?>mes/img/amex_cid.gif" alt="AMEX CVV2" style="float:left;" />
				</td>
			</tr>
			
			<tr>
				<td colspan="2">
					<p><span class="price">*</span> Required Fields</p>
					<p class="cart_navigation">
						<a href="<?php echo(__PS_BASE_URI__); ?>order.php?step=3" class="button_large">Back to Payment Methods</a>
						<input type="submit" name="submitPayment" class="exclusive_large" />
					</p>
				</td>
			</tr>
		</table>
	</fieldset>
</form>
</p>