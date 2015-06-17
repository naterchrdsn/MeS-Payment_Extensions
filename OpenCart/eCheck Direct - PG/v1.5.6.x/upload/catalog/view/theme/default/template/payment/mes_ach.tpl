<form id="mes_checkout">
<b style="margin-bottom: 3px; display: block;"><?php echo $text_ach; ?></b>
<div id="mes" style="background: #F7F7F7; border: 1px solid #DDDDDD; padding: 10px; margin-bottom: 10px;">

  <table width="100%">

    <tr>
      <td><?php echo $entry_ach_acct; ?></td>
      <td><input type="text" name="ach_acct" value="" size="15" maxlength="13" /></td>
    </tr>

    <tr>
      <td><?php echo $entry_ach_tran; ?></td>
      <td><input type="text" name="ach_tran" value="" size="10" maxlength="9" /></td>
    </tr>

    <tr>
        <td><?php echo $entry_ach_type; ?></td>
        <td>
            <select name="ach_type">
                <option value="C" selected>Checking</option>
                <option value="S">Savings</option>
            </select>
        </td>
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
    $merch_id = $this->config->get('mes_ach_merch_id');
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
            url: 'index.php?route=payment/mes_ach/send',
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