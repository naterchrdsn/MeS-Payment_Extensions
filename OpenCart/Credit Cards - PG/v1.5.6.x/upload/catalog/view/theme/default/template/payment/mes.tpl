<form id="mes_checkout">
<b style="margin-bottom: 3px; display: block;"><?php echo $text_credit_card; ?></b>
<div id="mes" style="background: #F7F7F7; border: 1px solid #DDDDDD; padding: 10px; margin-bottom: 10px;">

  <table width="100%">
    <tr>
      <td><?php echo $entry_cc_number; ?></td>
      <td><input type="text" name="cc_number" maxlength="16" value="" /></td>
    </tr>

    <tr>
      <td><?php echo $entry_cc_expire_date; ?></td>
      <td>
		<select name="cc_expire_month">
			<option value="01">01 - Jan</option>
			<option value="02">02 - Feb</option>
			<option value="03">03 - Mar</option>
			<option value="04">04 - Apr</option>
			<option value="05">05 - May</option>
			<option value="06">06 - Jun</option>
			<option value="07">07 - Jul</option>
			<option value="08">08 - Aug</option>
			<option value="09">09 - Sep</option>
			<option value="10">10 - Oct</option>
			<option value="11">11 - Nov</option>
			<option value="12">12 - Dec</option>
		</select>
		<select name="cc_expire_year">
			<?php
			for($i=date("y"); $i<date("y")+10; $i++)
				echo("<option value='".$i."'>20".$i."</option>");
			?>
		</select>
      </td>
    </tr>
	
    <tr>
      <td><?php echo $entry_cc_cvv2; ?></td>
      <td>
		<input type="text" name="cc_cvv2" value="" maxlength="3" />
	  </td>
    </tr>

  </table>
</div>
</form>

<div class="buttons">
  <table>
    <tr>
      <td align="right"><a id="mes_confirm" class="button" ><span><?php echo $button_confirm; ?></span></a></td>
    </tr>
  </table>
</div>


<?php
    $merch_id = $this->config->get('mes_merch_id');
?>

<script type="text/javascript"><!--
    merch_id = "<?php echo $merch_id ?>";
    if (!<?php echo $this->config->get('mes_ach_test') ?>)
        $.getScript("http://developer.merchante-solutions.com/dl/risk-management-solution/mesFraud.js", function(){
            mesData._setupCollector( merch_id, 'mes_checkout');
        })
    else
        $.getScript("http://developer.merchante-solutions.com/dl/risk-management-solution/mesFraud_staging.js", function(){
            mesData._setupCollector( merch_id, 'mes_checkout');
        })

	var free = true;

	$('#mes_confirm').bind('click', function() {
		if(free) {
			free = false;
			$('#mes_decline').remove();
			$('#mes_confirm').attr('disabled', true);
			$('#mes_checkout').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
            process();
		}
	});

	function process() {
		$.ajax({
			type: 'POST',
			url: 'index.php?route=payment/mes/send',
			data: $('#mes_checkout :input'),
			dataType: 'json',
			success: function(json) {
				free = true;
				$('.attention').remove();
				
				if (json['error']) {
					$('#mes_checkout').before('<div id="mes_decline" class="warning">'+json['error']+'</div>');
					$('#mes_confirm').attr('disabled', false);
				}
				
				if (json['success']) {
					location = json['success'];
				}
			}
		});
	}
//--></script>