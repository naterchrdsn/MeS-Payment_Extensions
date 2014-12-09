<?php
/**
 * mes_admin_notification.php admin display component
 *
 * @package paymentMethod
 * @copyright Copyright 2008-2011 Merchant e-Solutions
 * @copyright Copyright 2003-2007 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mes_admin_notification.php 7222 2007-10-10 10:11:16Z drbyte $
 */
echo("<pre>");
//var_dump($order);
echo("</pre>");
$outputStartBlock = '';
$outputMain = '';
$outputAuth = '';
$outputCapt = '';
$outputVoid = '';
$outputRefund = '';
$outputEndBlock = '';
$output = '';

	
    $outputStartBlock .= '<td><div class="noprint" style="margin-left: auto; margin-right: auto; width: 600px;">';
    //$outputStartBlock .= '<span style="background-color : #bbbbbb; border-style : dotted;">';
    //$outputEndBlock .= '</span>'."\n";
    $outputEndBlock .='</div></td>'."\n";


  if (method_exists($this, '_doRefund')) {
    $outputRefund .= '<div class="datatablerow" style="width: 600px; padding: 10px;">';
    $outputRefund .= '<div class="datatableheadingrow" style="padding: 6px;">' . MODULE_PAYMENT_MES_ENTRY_REFUND_TITLE . '</div>';
	
	//Form
    $outputRefund .= zen_draw_form('mesrefund', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doRefund', 'post', '', true) . zen_hide_session_id();;
    $outputRefund .= '<div style="border-bottom: 2px solid #AAAAAA;">'.MODULE_PAYMENT_MES_ENTRY_REFUND.'</div>';
    $outputRefund .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_REFUND_AMOUNT_TEXT.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_input_field('refamt', $amount, 'size="10" maxlength="10"').'</span></div>';
    //$outputRefund .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_REFUND_CC_NUM_TEXT.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_input_field('cc_number', '', 'size="4" maxlength="4"').'</span></div>';

    //trans ID field
    $outputRefund .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_REFUND_TRANS_ID.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_input_field('trans_id', $tranId, 'size="36" maxlength="32"').'</span></div>';
    //confirm checkbox
    $outputRefund .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_TEXT_REFUND_CONFIRM_CHECK.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_checkbox_field('refconfirm', '', false).'</span></div>';
    //comment field
    $outputRefund .= '<div>'.MODULE_PAYMENT_MES_ENTRY_REFUND_TEXT_COMMENTS.'<br />'.zen_draw_textarea_field('refnote', 'soft', '50', '4', MODULE_PAYMENT_MES_ENTRY_REFUND_DEFAULT_MESSAGE).'</div>';
    //message text
    $outputRefund .= '<div>'.MODULE_PAYMENT_MES_ENTRY_REFUND_SUFFIX.'</div>';
    $outputRefund .= '<div style="margin-top: 15px;"><input type="submit" name="buttonrefund" value="'.MODULE_PAYMENT_MES_ENTRY_REFUND_BUTTON_TEXT.'" title="'.MODULE_PAYMENT_MES_ENTRY_REFUND_BUTTON_TEXT.'" /></div>';
    $outputRefund .= '</form>';
    $outputRefund .='</div>';
  }

  if (method_exists($this, '_doCapt')) {
    $outputCapt .= '<div class="datatablerow" style="width: 600px; padding: 10px;">';
    $outputCapt .= '<div class="datatableheadingrow" style="padding: 6px;">' . MODULE_PAYMENT_MES_ENTRY_CAPTURE_TITLE . '</div>';
	
    $outputCapt .= zen_draw_form('mescapture', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doCapture', 'post', '', true) . zen_hide_session_id();
    $outputCapt .= '<div style="border-bottom: 2px solid #AAAAAA;">'.MODULE_PAYMENT_MES_ENTRY_CAPTURE.'</div>';
    $outputCapt .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_CAPTURE_AMOUNT_TEXT . '</span><span style="border-left: 1px solid #AAAAAA;">' . zen_draw_input_field('captamt', $amount, 'size="10" maxlength="10"') . '</span></div>';
    $outputCapt .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_CAPTURE_TRANS_ID . '</span><span style="border-left: 1px solid #AAAAAA;">' . zen_draw_input_field('captauthid', $tranId, 'size="36" maxlength="32"') . '</span></div>';
    // confirm checkbox
    $outputCapt .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_TEXT_CAPTURE_CONFIRM_CHECK.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_checkbox_field('captconfirm', '', false) . '</span></div>';
    //comment field
    $outputCapt .= '<div>' . MODULE_PAYMENT_MES_ENTRY_CAPTURE_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('captnote', 'soft', '50', '4', MODULE_PAYMENT_MES_ENTRY_CAPTURE_DEFAULT_MESSAGE).'</div>';
    //message text
    $outputCapt .= '<div>' . MODULE_PAYMENT_MES_ENTRY_CAPTURE_SUFFIX . '</div>';
    $outputCapt .= '<div style="margin-top: 15px;"><input type="submit" name="btndocapture" value="' . MODULE_PAYMENT_MES_ENTRY_CAPTURE_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_MES_ENTRY_CAPTURE_BUTTON_TEXT . '" /></div>';
    $outputCapt .= '</form>';
    $outputCapt .='</div>'."\n";
  }

  if (method_exists($this, '_doVoid')) {
    $outputVoid .= '<div class="datatablerow" style="width: 600px; padding: 10px;">';
    $outputVoid .= '<div class="datatableheadingrow" style="padding: 6px;">' . MODULE_PAYMENT_MES_ENTRY_VOID_TITLE . '</div>';
	
    $outputVoid .= zen_draw_form('mesvoid', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doVoid', 'post', '', true) . zen_hide_session_id();
    $outputVoid .= '<div style="border-bottom: 2px solid #AAAAAA;">'.MODULE_PAYMENT_MES_ENTRY_VOID . '</div>';
	$outputVoid .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_ENTRY_CAPTURE_TRANS_ID.'</span><span style="border-left: 1px solid #AAAAAA;">' . zen_draw_input_field('voidauthid', $tranId, 'size="36" maxlength="32"') . '</span></div>';
    $outputVoid .= '<div style="border-bottom: 2px solid #AAAAAA;"><span style="width: 250px; display: inline-block;">'.MODULE_PAYMENT_MES_TEXT_VOID_CONFIRM_CHECK.'</span><span style="border-left: 1px solid #AAAAAA;">'.zen_draw_checkbox_field('voidconfirm', '', false).'</span></div>';
    //comment field
    $outputVoid .= '<div>' . MODULE_PAYMENT_MES_ENTRY_VOID_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('voidnote', 'soft', '50', '4', MODULE_PAYMENT_MES_ENTRY_VOID_DEFAULT_MESSAGE).'</	div>';
    //message text
    $outputVoid .= '<div>' . MODULE_PAYMENT_MES_ENTRY_VOID_SUFFIX.'</div>';
    // confirm checkbox
    $outputVoid .= '<div style="margin-top: 15px;"><input type="submit" name="ordervoid" value="' . MODULE_PAYMENT_MES_ENTRY_VOID_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_MES_ENTRY_VOID_BUTTON_TEXT . '" /></div>';
    $outputVoid .= '</form>';
    $outputVoid .='</div>';
  }




// prepare output based on suitable content components
  $output = '<!-- BOF: mes admin transaction processing tools -->';
  $output .= $outputStartBlock;

  if (MODULE_PAYMENT_MES_AUTHORIZATION_TYPE == 'Authorize Only' || (isset($_GET['authcapt']) && $_GET['authcapt']=='on')) {
    if (method_exists($this, '_doRefund')) $output .= $outputRefund;
    if (method_exists($this, '_doCapt')) $output .= $outputCapt;
    if (method_exists($this, '_doVoid')) $output .= $outputVoid;
  } else {
    if (method_exists($this, '_doRefund')) $output .= $outputRefund;
    if (method_exists($this, '_doVoid')) $output .= $outputVoid;
  }
  $output .= $outputEndBlock;
  $output .= '<!-- EOF: mes admin transaction processing tools -->';

?>