<?php
if ($_POST && isset($_POST['tran_id']) && $_POST['resp_text']) {
?>
<form action='<?php print $_POST['eresp_return'].'/?wc-api=WC_Gateway_Mes_CC'; ?>' method='post' name='frm'>
<?php
foreach ($_POST as $a => $b) {
echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."'>";
}
header('HTTP/1.1 307 Temporary Redirect');
?>
<noscript><input type="submit" value="Click here if you are not redirected."/></noscript>
</form>
<script language="JavaScript">
document.frm.submit();
</script>
<?php
} else {
	echo 'Invalid response from PayHere... Please contact the merchant to complete your order';
}
?>
