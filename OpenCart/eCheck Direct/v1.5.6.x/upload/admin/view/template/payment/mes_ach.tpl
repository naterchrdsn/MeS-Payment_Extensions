<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb): ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php endforeach;; ?>
  </div>
  <?php if ($error_ach_warning) { ?>
  <div class="warning"><?php echo $error_ach_warning; ?></div>
  <?php } ?>
  <div class="box">
    <div class="left"></div>
    <div class="right"></div>
    <div class="heading">
      <h1><img src="view/image/payment.png" alt="" /><?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location='<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
    </div>
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
      <div id="tab_general" class="page">
        <table class="form">
          <tr>
            <td colspan="2"><h2><?php echo $text_settings ?></h2></td>
          </tr>
          <tr>
            <td width="25%"><span class="required">*</span> <?php echo $entry_profile_id; ?></td>
            <td><input type="text" name="mes_ach_profile_id" value="<?php echo $mes_ach_profile_id; ?>" size="24" />
              <br />
              <?php if ($error_ach_profile_id) { ?>
              <span class="error"><?php echo $error_ach_profile_id; ?></span>
              <?php } ?></td>
          </tr>
          <tr>
            <td><span class="required">*</span> <?php echo $entry_profile_key; ?></td>
            <td><input type="text" name="mes_ach_profile_key" value="<?php echo $mes_ach_profile_key; ?>" size="36" />
              <br />
              <?php if ($error_ach_profile_key) { ?>
              <span class="error"><?php echo $error_ach_profile_key; ?></span>
              <?php } ?></td>
          </tr>
          <tr>
            <td><span class="required">*</span> <?php echo $entry_cust_id; ?></td>
            <td>
              <input type="text" name="mes_ach_cust_id" value="<?php echo $mes_ach_cust_id; ?>" size="36" />
              <br />
              <?php if ($error_ach_cust_id) { ?>
              <span class="error"><?php echo $error_ach_cust_id; ?></span>
              <?php } ?></td>
            </td>
          </tr>
          <tr>
            <td><?php echo $entry_test; ?></td>
            <td><?php if ($mes_ach_test) { ?>
              <input type="radio" name="mes_ach_test" value="1" checked="checked" />
              Simulator
              <input type="radio" name="mes_ach_test" value="0" />
              Live
              <?php } else { ?>
              <input type="radio" name="mes_ach_test" value="1" />
              Simulator
              <input type="radio" name="mes_ach_test" value="0" checked="checked" />
              Live
              <?php } ?></td>
          </tr>
          <tr>
            <td><?php echo $entry_order_status; ?></td>
            <td><select name="mes_ach_order_status_id">
                <?php foreach ($order_statuses as $order_status) { ?>
                <?php if ($order_status['order_status_id'] == $mes_ach_order_status_id) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select></td>
          </tr>
          <tr>
            <td><?php echo $entry_geo_zone; ?></td>
            <td><select name="mes_ach_geo_zone_id">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) { ?>
                <?php if ($geo_zone['geo_zone_id'] == $mes_ach_geo_zone_id) { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select></td>
          </tr>
          <tr>
            <td><?php echo $entry_status; ?></td>
            <td><select name="mes_ach_status">
                <?php if ($mes_ach_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select></td>
          </tr>
          <tr>
            <td><?php echo $entry_sort_order; ?></td>
            <td><input type="text" name="ach_sort_order" value="<?php echo $ach_sort_order; ?>" size="1" /></td>
          </tr>
          <tr>
            <td colspan="2"><h2><?php echo $text_fraud_settings ?></h2></td>
          </tr>
          <tr>
            <td><?php echo $entry_fraud_status; ?></td>
            <td><select name="mes_ach_fraud_status">
                <?php if ($mes_ach_fraud_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select></td>
          </tr>
          <tr>
            <td><?php echo $entry_merch_id; ?></td>
            <td>
              <input type="text" name="mes_ach_merch_id" value="<?php echo $mes_ach_merch_id; ?>" size="36" />
              <br />
              <?php if ($error_ach_merch_id) { ?>
              <span class="error"><?php echo $error_ach_merch_id; ?></span>
              <?php } ?>
            </td>
          </tr>
          <tr>
            <td><?php echo $entry_fraud_order_status; ?></td>
            <td><select name="mes_ach_fraud_status_id">
                <?php foreach ($order_statuses as $order_status) { ?>
                <?php if ($order_status['order_status_id'] == $mes_ach_fraud_status_id) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select></td>
          </tr>
        </table>
      </div>
      </form>
    </div>
  </div> 
</div>
<?php echo $footer; ?>