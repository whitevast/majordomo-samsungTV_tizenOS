<?php
function STVPowerOn($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	if(!$device['ONLINE']) $sams->sendkey($device['ID'], 'KEY_POWER'); 
}

function STVPowerOff($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['ONLINE']) $sams->sendkey($device['ID'], 'KEY_POWER'); 
}

function STVVolUp($id, $value = 1){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	for($i=1; $i<=$value; $i++){
		$sams->sendkey($device['ID'], "KEY_VOLUP");
		usleep(200000);
	}
}

function STVVolDown($id, $value = 1){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	for($i=1; $i<=$value; $i++){
		$sams->sendkey($device['ID'], "KEY_VOLDOWN");
		usleep(200000);
	}
}

function STVGetVol($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	return $sams->getvol($device['ID'], $value);
}

function STVSetVol($id, $value){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->setvol($device['ID'], $value);
}

function STVChup($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->sendkey($device['ID'], "KEY_CHUP");
}

function STVChdown($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->sendkey($device['ID'], "KEY_CHDOWN");
}

function STVEnter($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->sendkey($device['ID'], "KEY_ENTER");
}

function STVSendURL($id, $url){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->ineturl($device['ID'], $url);
}

function STVSendKey($id, $value){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$sams->sendkey($device['ID'], $value);
}

function STVStatus($id){
	include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
	$sams = new samsung();
	$device = $sams->dev($id);
	if(!$device) exit;
	$status = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID = '".(int)$device['ID']."'");
	if($status['ONLINE']) return true;
	else return false;
}

	