{if $mes_order.valid == 1}
<div class="conf confirmation">
	{l s='Congratulations! Your payment is pending verification, and your order has been saved under' mod='mes'}{if isset($mes_order.reference)} {l s='the reference' mod='mes'} <b>{$mes_order.reference|escape:html:'UTF-8'}</b>{else} {l s='the ID' mod='mes'} <b>{$mes_order.id|escape:html:'UTF-8'}</b>{/if}.
</div>
<div>
	<br /><br />
	Order Details:<br />
	- Order Number : "{$mes_order.reference|escape:false}"<br />
	<br />Click here for a 
	<a href='{$mes_order.urlbase}index.php?controller=pdf-invoice&id_order={$mes_order.id}' title='Invoice'>
	  <i class="icon-file-text large"></i> PDF Invoice
	</a>
	, or proceed to your <a href='{$mes_order.urlbase}order-history'>order history</a> for full details.
	<br />For any questions or for further information, please contact our <a href='{$link->getPageLink("contact")|escape:false}'>customer support</a>.
</div>
{else}
<div class="error">
	{l s='Unfortunately, an error occurred during the transaction.' mod='mes'}<br /><br />
	{l s='Please double-check your credit card details and try again. If you need further assistance, feel free to contact us anytime.' mod='mes'}<br /><br />
{if isset($mes_order.reference)}
	({l s='Your Order\'s Reference:' mod='mes'} <b>{$mes_order.reference|escape:html:'UTF-8'}</b>)
{else}
	({l s='Your Order\'s ID:' mod='mes'} <b>{$mes_order.id|escape:html:'UTF-8'}</b>)
{/if}
</div>
{/if}