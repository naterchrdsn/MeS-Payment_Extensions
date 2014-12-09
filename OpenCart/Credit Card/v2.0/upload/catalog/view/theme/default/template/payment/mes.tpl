<form name="mes_checkout" id="mes_checkout" class="form-horizontal">
<?php if ($testmode) { ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $text_testmode; ?></div>
<?php } ?>
  <fieldset id="payment">
    <legend><?php echo $text_credit_card; ?></legend>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="form-control" max-length="16" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-expire-date"><?php echo $entry_cc_expire_date; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_expire_date" id="input-cc-expire-date" value="" max-length="4" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvv2; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_cvv2" max-length="5" value="" id="input-cc-cvv2" class="form-control" />
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
      url: 'index.php?route=payment/mes/send',
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
