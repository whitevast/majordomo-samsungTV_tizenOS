<?php
function STVPowerOn($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	$status = SQLSelectOne('SELECT VALUE FROM samsungtv_data WHERE DEVICE_ID ="'.(int)$device['ID'].'" and KEY_ID = "ST"');
	if(!$status['VALUE']){
		if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], 'KEY_POWER');
		else $sams->ssendkey($device['ID'], 'power');
	}
}

function STVPowerOff($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	$status = SQLSelectOne('SELECT VALUE FROM samsungtv_data WHERE DEVICE_ID ="'.(int)$device['ID'].'" and KEY_ID = "ST"');
	if($status['VALUE']){
		if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], 'KEY_POWER');
		else $sams->ssendkey($device['ID'], 'power');
	}
}

function STVVolUp($id, $value = 1){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	for($i=1; $i<=$value; $i++){
		if($device['PORT']=='8001' or $device['PORT']=='8002'){
			$sams->sendkey($device['ID'], "KEY_VOLUP");
			usleep(200000);
		} else $sams->ssendkey($device['ID'], "volumeup");
	}
}

function STVVolDown($id, $value = 1){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	for($i=1; $i<=$value; $i++){
		if($device['PORT']=='8001' or $device['PORT']=='8002'){
			$sams->sendkey($device['ID'], "KEY_VOLDOWN");
			usleep(200000);
		} else $sams->ssendkey($device['ID'], "volumedown");
	}
}

function STVGetVol($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') return $sams->getvol($device['ID']);
	else{
		$vol = $sams->sget($device['ID']);
		return $vol['audioVolume']['volume']['value'];
	}
}

function STVGetMute($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') return $sams->getmute($device['ID']);
	else{
		$vol = $sams->sget($device['ID']);
		return $vol['audioMute']['mute']['value'];
	}
}

function STVSetVol($id, $value){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->setvol($device['ID'], $value);
	else ssendkey($device['ID'], 'volume', $value);
}

function STVChup($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], "KEY_CHUP");
	else $sams->ssendkey($device['ID'], "channelup");
}

function STVChdown($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], "KEY_CHDOWN");
	else $sams->ssendkey($device['ID'], "channeldown");	
}

function STVEnter($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], "KEY_ENTER");
	else $sams->writelog("STVEnter not supported by protocol SmartThings"); 
}

function STVSendURL($id, $url){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->ineturl($device['ID'], $url);
	else $sams->writelog("STVSendURL not supported by protocol SmartThings"); 
}

function STVSendKey($id, $value){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	if($device['PORT']=='8001' or $device['PORT']=='8002') $sams->sendkey($device['ID'], $value);
	else $sams->writelog("STVSendKey not supported by protocol SmartThings"); 
}

function STVStatus($id){
	include_once(DIR_MODULES . 'samsungtvtizen/samsungtvtizen.class.php');
	$samsungtvtizen_module = new samsungtvtizen();
	$sams = new samsung($samsungtvtizen_module->debug);
	$device = $sams->dev($id);
	if(!$device) exit;
	$status = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID = '".(int)$device['ID']."'");
	if($status['ONLINE']) return true;
	else return false;
}

	