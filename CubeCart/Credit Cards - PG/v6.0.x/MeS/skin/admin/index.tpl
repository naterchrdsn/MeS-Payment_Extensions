<!--
/*
 * CubeCart Payment Module for MeS Payment Gateway Credit Card Transactions
 * Copyright (c) 2015 Merchant e-Solutions
 * All rights reserved.
 * Author: Nate Richardson <nrichardson@merchante-solutions.com>
 */-->
<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
	<div id="MeS" class="tab_content">
  		<h3>{$TITLE}</h3>
  		<p>{$LANG.mes.module_description}</p>
  		<fieldset>
  			<legend>{$LANG.module.cubecart_settings}</legend>
			<div>
				<label for="status">{$LANG.common.status}</label>
				<span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span>
			</div>
			<div>
				<label for="position">{$LANG.module.position}</label>
				<span><input type="text" name="module[position]" id="position" class="textbox number" value="{$MODULE.position}" /></span>
			</div>
			<div>
				<label for="scope">{$LANG.module.scope}</label>
				<span>
					<select name="module[scope]">
  						<option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
  						<option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
  						<option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
    				</select>
				</span>
			</div>
			<div>
				<label for="default">{$LANG.common.default}</label>
				<span><input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" /></span>
			</div>
			<div>
				<label for="description">{$LANG.common.description} *</label>
				<span><input name="module[desc]" id="description" class="textbox" type="text" value="{$MODULE.desc}" /></span>
			</div>
			<div>
				<label for="test_mode">{$LANG.mes.test_mode}</label>
				<span>
					<select name="module[test_mode]">
    					<option value="1" {$SELECT_test_mode_1}>Enabled</option>
    					<option value="0" {$SELECT_test_mode_0}>Disabled</option>
					</select>
				</span>
			</div>
			<div>
				<label for="payment_mode">{$LANG.mes.payment_mode}</label>
				<span>
					<select name="module[payment_mode]">
    					<option value="pg" {$SELECT_payment_mode_pg}>{$LANG.mes.pg}</option>
    					<option value="ph" {$SELECT_payment_mode_ph}>{$LANG.mes.ph}</option>
					</select>
				</span>
			</div>
			<div>
				<label for="profile_id">{$LANG.mes.profile_id}</label>
				<span><input type="text" name="module[profile_id]" id="profile_id" class="textbox" value="{$MODULE.profile_id}" /></span>
			</div>
			<div>
				<label for="profile_key">{$LANG.mes.profile_key}</label>
				<span><input name="module[profile_key]" id="profile_key" class="textbox" type="text" value="{$MODULE.profile_key}" /></span>
			</div>
			<div>
				<label for="transaction_type">{$LANG.mes.transaction_type}</label>
				<span>
					<select name="module[transaction_type]">
    					<option value="auth" {$SELECT_transaction_type_auth}>{$LANG.mes.txn_authorize}</option>
    					<option value="sale" {$SELECT_transaction_type_sale}>{$LANG.mes.txn_sale}</option>
					</select>
				</span>
			</div>
        </fieldset>
        <p>{$LANG.module.description_options}</p>
		<fieldset><legend>{$LANG.mes.payhere_settings}</legend>
			<div>
				<label for="security_key">{$LANG.mes.security_key}</label>
				<span><input type="text" name="module[security_key]" id="security_key" class="textbox number" value="{$MODULE.security_key}" /></span>
			</div>
		</fieldset>
	</div>
	{$MODULE_ZONES}
	<div class="form_control">
		<input type="submit" name="save" value="{$LANG.common.save}" />
	</div>
  	<input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>