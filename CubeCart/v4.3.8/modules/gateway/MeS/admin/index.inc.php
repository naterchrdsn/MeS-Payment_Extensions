<?php

/*
 * CubeCart Payment Module for MeS Payment Gateway
 * Copyright (c) 2010 Merchant e-Solutions
 * All rights reserved.
 * Author: Ben Rice <brice@merchante-solutions.com>
 */
 

if(!defined('CC_INI_SET')){ die("Access Denied"); }

permission("settings","read",$halt=TRUE);

require($glob['adminFolder'].CC_DS."includes".CC_DS."header.inc.php");

if(isset($_POST['module']))
{ 
	require CC_ROOT_DIR.CC_DS.'modules'.CC_DS.'status.inc.php';	
	$cache = new cache("config.".$moduleName);
	$cache->clearCache();
	$module = array();
	$msg = writeDbConf($_POST['module'], $moduleName, $module);
}
$module = fetchDbConfig($moduleName);
?>

<p><a href="http://www.merchante-solutions.com"><img src="modules/<?php echo $moduleType; ?>/<?php echo $moduleName; ?>/admin/logo.gif" alt="" border="0" title="" /></a></p>
<?php 
if(isset($msg))
{ 
	echo msg($msg); 
} 
?>
<p class="copyText">&quot;Payment Solutions for Global Growth.&quot;</p>

<form action="<?php echo $glob['adminFile']; ?>?_g=<?php echo $_GET['_g']; ?>&amp;module=<?php echo $_GET['module']; ?>" method="post" enctype="multipart/form-data">
<table border="0" cellspacing="1" cellpadding="3" class="mainTable">
  <tr>
    <td colspan="2" class="tdTitle">Configuration Settings </td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Status:</strong></td>
    <td class="tdText">
	<select name="module[status]">
		<option value="1" <?php if($module['status']==1) echo "selected='selected'"; ?>>Enabled</option>
		<option value="0" <?php if($module['status']==0) echo "selected='selected'"; ?>>Disabled</option>
    </select>	</td>
  </tr>
  
  <tr>
  	<td align="left" class="tdText"><strong>Description:</strong><br />This is displayed to the card holder during checkout.</td>
    <td class="tdText"><input type="text" name="module[desc]" value="<?php echo $module['desc']; ?>" class="textbox" size="30" /></td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Enable Card Validation:</strong><br />Verify the card number before authorization?</td>
    <td class="tdText">
	<select name="module[validation]">
		<option value="1" <?php if($module['validation']==1) echo "selected='selected'"; ?>>Enabled</option>
		<option value="0" <?php if($module['validation']==0) echo "selected='selected'"; ?>>Disabled</option>
    </select>
	</td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Require CVV Code:</strong></td>
    <td class="tdText">
	<select name="module[reqCvv]">
		<option value="1" <?php if($module['reqCvv']==1) echo "selected='selected'"; ?>>Yes</option>
		<option value="0" <?php if($module['reqCvv']==0) echo "selected='selected'"; ?>>No</option>
    </select>
	</td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Profile ID:</strong></td>
    <td class="tdText"><input type="text" name="module[profile_id]" value="<?php echo $module['profile_id']; ?>" class="textbox" size="30" /></td>
  </tr>
 
  <tr>
    <td align="left" class="tdText"><strong>Profile Key:</strong></td>
    <td class="tdText"><input type="text" name="module[profile_key]" value="<?php echo $module['profile_key']; ?>" class="textbox" size="40" /></td>
  </tr>

  <tr>
    <td align="left" class="tdText"><strong>Transaction Type:</strong><br />Sales are captured immediatly. Pre-authorizations must<br />be captured in the MeS Back Office for funding.</td>
    <td class="tdText"><select name="module[tranType]">
      <option value="D" <?php if($module['tranType'] == "D") echo "selected='selected'"; ?>>Sale</option>
      <option value="P" <?php if($module['tranType'] == "P") echo "selected='selected'"; ?>>Pre-Authorization</option>
      </select>
	</td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Test Mode:</strong><br />Transactions sent while in test mode are not live<br />and do not produce valid authorizations!</td>
    <td class="tdText"><select name="module[testMode]">
      <option value="1" <?php if($module['testMode'] == 1) echo "selected='selected'"; ?>>Yes</option>
      <option value="0" <?php if($module['testMode'] == 0) echo "selected='selected'"; ?>>No</option>
      </select>
	</td>
  </tr>
  
  <tr>
    <td align="left" class="tdText"><strong>Default:</strong><br />Make this the default payment option for the card holder.</td>
    <td class="tdText">
	  <select name="module[default]">
	  <option value="1" <?php if($module['default'] == 1) echo "selected='selected'"; ?>>Yes</option>
	  <option value="0" <?php if($module['default'] == 0) echo "selected='selected'"; ?>>No</option>
	  </select>
	</td>
  </tr>
   
  <tr>
     <td align="left" class="tdText"><strong>Debugging: </strong><br />Debugging is for development purposes.</td>
     <td class="tdText"><select name="module[debug]">
       <option value="0" <?php if($module['debug'] == 0) echo "selected='selected'"; ?>>No</option>
	   <option value="1" <?php if($module['debug'] == 1) echo "selected='selected'"; ?>>Yes</option>
     </select></td>
   </tr>
   
   <tr>
    <td align="right" class="tdText">&nbsp;</td>
    <td class="tdText"><input type="submit" class="submit" value="Edit Config" /></td>
  </tr>
  
</table>
</form>
