<form name="mes_checkout" id="mes_checkout" class="form-horizontal">
<?php if ($testmode) { ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $text_testmode; ?></div>
<?php } ?>
  <fieldset id="payment">
    <legend><?php echo $text_ach; ?></legend>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-ach-acct"><?php echo $entry_ach_acct; ?></label>
      <div class="col-sm-10">
        <input type="text" name="account_num" value="" placeholder="<?php echo $entry_ach_acct; ?>" id="input-ach-acct" class="form-control" max-length="16" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-ach-tran"><?php echo $entry_ach_tran; ?></label>
      <div class="col-sm-10">
        <input type="text" name="transit_num" id="input-ach-tran" value="" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="ach_type"><?php echo $entry_ach_type; ?></label>
      <div class="col-sm-10">
        <select name="account_type">
            <option value="C" selected>Checking</option>
            <option value="S">Savings</option>
        </select>
      </div>
    </div>
  </fieldset>
</form>
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="Confirm Order" id="mes_confirm" class="btn btn-primary" data-loading-text="Loading..." />
  </div>
</div>
<script type="text/javascript"><!--
  var merch_id = "<?php echo $merch_id ?>";
  if (!<?php echo $testmode ?>)
      $.getScript("http://developer.merchante-solutions.com/dl/risk-management-solution/mesFraud.js", function(){
          mesData._setupCollector( merch_id, 'mes_checkout');
      })
  else
      $.getScript("http://developer.merchante-solutions.com/dl/risk-management-solution/mesFraud_staging.js", function(){
          mesData._setupCollector( merch_id, 'mes_checkout');
      })
  $('#mes_confirm').bind('click', function() {
    $('#mes_decline').remove();
    $('#mes_confirm').attr('disabled', true);
    $('#mes_checkout').before('<div class="alert"><?php echo $text_wait; ?></div>');
    process();
  });
  function process() {
    $.ajax({
      type: 'POST',
      url: 'index.php?route=payment/mes_ach/send',
      data: $('#mes_checkout :input'),
      dataType: 'json',
      statusCode: {
        404: function() {
        $('.alert').remove();
        $('#mes_checkout').before('<div id="mes_decline" class="alert alert-danger">Payment Gateway Connection Error, please try again later.</div>');

        },
        500: function() {
        $('.alert').remove();
        $('#mes_checkout').before('<div id="mes_decline" class="alert alert-danger">Internal Server Error, please try again later.</div>');

        }
      },
      beforeSend: function() {
        $('#mes_confirm').button('loading');
      },
      complete: function() {
        $('#mes_confirm').button('reset');
      },
      success: function(json) {
        $('.alert').remove();
        
        if (json['error']) {
          $('#mes_checkout').before('<div id="mes_decline" class="alert alert-danger">'+json['error']+'</div>');
          $('#mes_confirm').attr('disabled', false);
        }
        
        if (json['success']) {
          location = json['success'];
        }
      }
    });
  }
//--></script>
