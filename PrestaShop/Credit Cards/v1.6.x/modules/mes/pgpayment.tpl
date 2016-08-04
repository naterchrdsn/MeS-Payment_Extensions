<div class="row panel">
	<div class="col-xs-12 col-md-6 panel text-center">
		<div id="mes_payment_module" class="payment_module">
			<h3 class="mes_title">
				{l s='Pay by credit card' mod='mes'}
			</h3>
			<div class="alert alert-danger" style="display:none" id="mes_error_creditcard">
				<p>{l s='Payment Authorization Failed: Please verify your Credit Card details are entered correctly and try again, or try another payment method.' mod='mes'}</p>
			</div>
			{if isset($err_message) && !empty($err_message)}
				<div class="alert alert-danger" id="mes_error_creditcard_custom">
					<p>{$err_message|escape:false}</p>
				</div>
			{/if}
			<form action="{$link->getModuleLink('mes', 'validation')|escape:false}" method="POST" id="mesCCForm" novalidate>
				<div class="block-left">
					<label>{l s='Card Number' mod='mes'}</label><br />
					<input class="numeric form-control" type="text" maxlength="20" name="ccNo" autocomplete="off" id="ccNo" required/>
				</div>
				<br />
				<div class="block-left">
					<label>{l s='Expiration (MM/YYYY)' mod='mes'}</label><br />
					<select id="expMonth" name="expMonth" required class="form-control">
						<option value="01">{l s='January' mod='mes'}</option>
						<option value="02">{l s='February' mod='mes'}</option>
						<option value="03">{l s='March' mod='mes'}</option>
						<option value="04">{l s='April' mod='mes'}</option>
						<option value="05">{l s='May' mod='mes'}</option>
						<option value="06">{l s='June' mod='mes'}</option>
						<option value="07">{l s='July' mod='mes'}</option>
						<option value="08">{l s='August' mod='mes'}</option>
						<option value="09">{l s='September' mod='mes'}</option>
						<option value="10">{l s='October' mod='mes'}</option>
						<option value="11">{l s='November' mod='mes'}</option>
						<option value="12">{l s='December' mod='mes'}</option>
					</select>
					<span> / </span>
					<select id="expYear" name="expYear" required class="form-control">
						{for $i=1 to 8}
							{$tmp_year = {$smarty.now|date_format:"%Y"} - 1 + $i}
							<option value="{$tmp_year|escape:false}">{$tmp_year|escape:false}</option>
						{/for}
					</select>
				</div>
				<br />
				<div class="block-left">
					<label>{l s='CVC' mod='mes'}</label><br />
					<input class="numeric form-control" name="cvv" id="cvv" type="text" maxlength="4" autocomplete="off" required />
				</div>
				<br />
				<input type="submit" class="btn btn-primary btn-lg" id="submit_payment" value="{l s='Submit Payment' mod='mes'}" />
			</form>
		</div>
	</div>
</div>
<script>
	$('.numeric').on('blur', function () {
		this.value = this.value.replace(/[^0-9]/g, '');
	});
	$('#mesCCForm').submit(function (e) {
		e.preventDefault();
		if (!$('#ccNo').val() || !$('#expMonth').val() || !$('#expYear').val() || !$('#cvv').val()) {
			$('#mes_error_creditcard').show();
		} else {
			$('#mes_error_creditcard').hide();
			$('#mes_error_creditcard_custom').hide();
			var myForm = document.getElementById('mesCCForm');
			myForm.submit();
		};
	});
</script>