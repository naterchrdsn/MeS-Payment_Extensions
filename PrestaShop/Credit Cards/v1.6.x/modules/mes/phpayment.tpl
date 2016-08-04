<form action="{$mes_form_action|escape:false}" method="POST">
	{if isset($err_message) && !empty($err_message)}
		<div class="alert alert-danger" id="mes_error_creditcard_custom">
			<p>{$err_message|escape:false}</p>
		</div>
	{/if}
	<p class="payment_module">
		<input type="hidden" name="profile_id" value="{$mes_profile_id|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="transaction_amount" value="{$mes_transaction_amount|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="invoice_number" value="{$mes_invoice_number|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="use_merch_receipt" value="{$mes_use_merch_receipt|escape:false}" />
		<input type="hidden" name="cardholder_street_address" value="{$mes_cardholder_street_address|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="cardholder_zip" value="{$mes_cardholder_zip|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="echo_redirurl" value="{$mes_echo_redirurl|escape:'htmlall':'UTF-8'}" />
		<input type="hidden" name="return_url" value="{$mes_return_url|escape:false}" />
		<input type="hidden" name="cancel_url" value="{$mes_cancel_url|escape:false}" />
		{if isset($mes_use_simulator)}
		<input type="hidden" name="use_simulator" value="{$mes_use_simulator}" />
		{/if}
		{if isset($mes_transaction_key) && !empty($mes_transaction_key)}
		<input type="hidden" name="transaction_key" value="{$mes_transaction_key|escape:false}" />
		{/if}
		<button id="mes_payhere_button" type="submit" name="submit" class="btn btn-primary btn-lg"> {l s='Pay via PayHere' mod='mes'}</button>
	</p>
</form>
