<?php
/**
* SamsungTV 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 11:10:20 [Oct 18, 2019])
*/
//
//
include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
class samsungtvtizen extends module {
/**
* samsungtvtizen
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="samsungtvtizen";
  $this->title="SamsungTV";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}


/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
	    if ((time() - gg('cycle_samsungtvtizenRun')) < 10 ) {
			$out['CYCLERUN'] = 1;
		} else {
			$out['CYCLERUN'] = 0;
		}
	
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 if (!$out['API_URL']) {
  $out['API_URL']='http://';
 }
 $out['API_KEY']=$this->config['API_KEY'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   $this->saveConfig();
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='samsungtv_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_samsungtv_devices') {
   $this->search_samsungtv_devices($out);
  }
  if ($this->view_mode=='edit_samsungtv_devices') {
   $this->edit_samsungtv_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_samsungtv_devices') {
   $this->delete_samsungtv_devices($this->id);
   $this->redirect("?data_source=samsungtv_devices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='samsungtv_codes') {
  if ($this->view_mode=='' || $this->view_mode=='search_samsungtv_codes') {
   $this->search_samsungtv_codes($out);
  }
  if ($this->view_mode=='edit_samsungtv_codes') {
   $this->edit_samsungtv_codes($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* samsungtv_devices search
*
* @access public
*/
 function search_samsungtv_devices(&$out) {
  require(DIR_MODULES.$this->name.'/samsungtv_devices_search.inc.php');
 }
/**
* samsungtv_devices edit/add
*
* @access public
*/
 function edit_samsungtv_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/samsungtv_devices_edit.inc.php');
 }
/**
* samsungtv_devices delete record
*
* @access public
*/
 function delete_samsungtv_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM samsungtv_devices WHERE ID='".$rec['ID']."'");
  	$properties=SQLSelect("SELECT * FROM samsungtv_codes WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
    $total=count($properties);
    for($i=0;$i<$total;$i++) {
		if ($properties[$i]['LINKED_PROPERTY'] != '')  removeLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
	}
  SQLExec("DELETE FROM samsungtv_codes WHERE DEVICE_ID='".$rec['ID']."'");
  	$properties=SQLSelect("SELECT * FROM samsungtv_apps WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
    $total=count($properties);
    for($i=0;$i<$total;$i++) {
		if ($properties[$i]['LINKED_PROPERTY'] != '')  removeLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
	}
  SQLExec("DELETE FROM samsungtv_apps WHERE DEVICE_ID='".$rec['ID']."'");
 }
/**
* samsungtv_codes search
*
* @access public
*/
 function search_samsungtv_codes(&$out) {
  require(DIR_MODULES.$this->name.'/samsungtv_codes_search.inc.php');
 }
/**
* samsungtv_codes edit/add
*
* @access public
*/
 function edit_samsungtv_codes(&$out, $id) {
  require(DIR_MODULES.$this->name.'/samsungtv_codes_edit.inc.php');
 }

 function processCycle() {
	 $stateget = 1;
	 $this->getConfig();
	 $sams = new samsung();
	 $device = SQLSelect("SELECT * FROM samsungtv_devices");
	 foreach($device as $val){
		 $app = SQLSelect("SELECT * FROM samsungtv_apps WHERE DEVICE_ID = '".(int)$val['ID']."'");
		 foreach($app as $valap){
			$volume = $sams->getvol($val['ID']);
			if(!$volume){
				if($val['ONLINE']){
					$val['ONLINE'] = 0;
					SQLUpdate('samsungtv_devices', $val);
				}
				break;
			}
			if(!$val['ONLINE']){
				$val['ONLINE'] = 1;
				SQLUpdate('samsungtv_devices', $val);
			}
			if($val['VOLUME'] != $volume){
				$val['VOLUME'] = $volume;
				SQLUpdate('samsungtv_devices', $val);
			}
			if($stateget){
				$state = $sams->appmgnt($val['ID'], $valap['APPID']);
				$state = json_decode($state, true);
				if($state['visible']){
					$valap['STATE'] = 1;
					SQLUpdate('samsungtv_apps', $valap);
					$stateget = 0;
					continue;
				}
			}
			if($valap['STATE'] == 1){
				$valap['STATE'] = 0;
				SQLUpdate('samsungtv_apps', $valap);
			}
		}
	 }
 }

 function propertySetHandle($object, $property, $value){
		$sams = new samsung();
	    $samstv = SQLSelect("SELECT ID FROM samsungtv_codes WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
		$total = count($samstv);
		//обработка команды из таблицы команд
        if ($total) { 
            for ($i = 0; $i < $total; $i++) {
				$key = SQLSelectOne("SELECT * FROM samsungtv_codes WHERE ID='".(int)$samstv[$i]['ID']."'");
				if($key['VALUE'] == "KEY_NUM"){ //выбор номера канала
					$value = str_split($value);
					$numcnt = count($value);
					for($u = 0; $u < $numcnt; $u++){
						$key['VALUE'] = "KEY_".$value[$u];
						$sams->sendkey($key['DEVICE_ID'], $key['VALUE']);
						usleep(200000);
					}
				}elseif($key['VALUE'] == "KEY_VOL"){
						if(!$value)	$sams->sendkey($key['DEVICE_ID'], "KEY_MUTE");
						else {
							if($value > 0) $key['VALUE'] = "KEY_VOLUP";
							else{
								$key['VALUE'] = "KEY_VOLDOWN";
								$value = abs($value);
							}
							for($i=1; $i<=$value; $i++){
								$sams->sendkey($key['DEVICE_ID'], $key['VALUE']);
								usleep(200000);
							}
							
						}
				}elseif($key['VALUE'] == "KEY_POWER"){
					$status = SQLSelectOne('SELECT ONLINE FROM samsungtv_devices WHERE ID ="'.(int)$key['DEVICE_ID'].'"');
					debmes($status['ONLINE']);
					debmes($value);
					If(($value and !$status['ONLINE']) or (!$value and $status['ONLINE'])) $sams->sendkey($key['DEVICE_ID'], $key['VALUE']);
				}
				else{
					if($key['VALUE'] == "KEY_CH"){
						if($value == '1') $key['VALUE'] = "KEY_CHUP";
						elseif($value == '0') $key['VALUE'] = "KEY_CH_LIST";
						elseif($value == '-1') $key['VALUE'] = "KEY_CHDOWN";
					}
					elseif($key['VALUE'] == "KEY_NAVI"){
						if($value == '0' or $value == "ENTER" or $value == "OK") $key['VALUE'] = "KEY_ENTER";
						elseif($value == '1' or $value == "LEFT") $key['VALUE'] = "KEY_LEFT";
						elseif($value == '2' or $value == "UP") $key['VALUE'] = "KEY_UP";
						elseif($value == '3' or $value == "RIGHT") $key['VALUE'] = "KEY_RIGHT";
						elseif($value == '4' or $value == "DOWN") $key['VALUE'] = "KEY_DOWN";
					}
					elseif($key['VALUE'] == "KEY_CLR"){
						if($value == '0' or $value == "RED") $key['VALUE'] = "KEY_RED";
						elseif($value == '1' or $value == "GREEN") $key['VALUE'] = "KEY_GREEN";
						elseif($value == '2' or $value == "YELLOW") $key['VALUE'] = "KEY_YELLOW";
						elseif($value == '3' or $value == "CYAN" or $value == "BLUE") $key['VALUE'] = "KEY_CYAN";
					}
					elseif($key['VALUE'] == "KEY_KEY"){
						$key['VALUE'] = $value;
					}
					$sams->sendkey($key['DEVICE_ID'], $key['VALUE']);
				}
            }
        }
		//обработка команды из таблицы приложений
		else{ 
			$samstv = SQLSelect("SELECT ID FROM samsungtv_apps WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
			$total = count($samstv);
			if ($total) {
				for ($i = 0; $i < $total; $i++) {
					$id = SQLSelectOne("SELECT * FROM samsungtv_apps WHERE ID='".(int)$samstv[$i]['ID']."'");
					if($id['APPID'] == 'URL'){
						$sams->ineturl($id['DEVICE_ID'], $value);
						continue;
					}
					$value = ($value)? "start":"close";
					$sams->appmgnt($id['DEVICE_ID'], $id['APPID'], $value);
				}
			}
		}
    }


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
 $id = SQLSelect('SELECT ID FROM samsungtv_devices');
 for($i=0; $i<count($id); $i++){
	$this->delete_samsungtv_devices($id[$i]['ID']);
 }
  SQLExec('DROP TABLE IF EXISTS samsungtv_devices');
  SQLExec('DROP TABLE IF EXISTS samsungtv_codes');
  SQLExec('DROP TABLE IF EXISTS samsungtv_apps');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
samsungtv_devices - 
samsungtv_codes - 
*/
  $data = <<<EOD
 samsungtv_devices: ID int(10) unsigned NOT NULL auto_increment
 samsungtv_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: IP varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: MODEL varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: SERIAL varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: MAC varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: TOKEN varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: VOLUME varchar(100) NOT NULL DEFAULT ''
 samsungtv_devices: ONLINE tinyint(2) unsigned NOT NULL DEFAULT '0'
 samsungtv_codes: ID int(10) unsigned NOT NULL auto_increment
 samsungtv_codes: TITLE varchar(100) NOT NULL DEFAULT ''
 samsungtv_codes: VALUE varchar(255) NOT NULL DEFAULT ''
 samsungtv_codes: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 samsungtv_codes: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 samsungtv_codes: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 samsungtv_codes: UPDATED datetime
 samsungtv_apps: ID int(10) unsigned NOT NULL auto_increment
 samsungtv_apps: TITLE varchar(100) NOT NULL DEFAULT ''
 samsungtv_apps: APPID varchar(255) NOT NULL DEFAULT ''
 samsungtv_apps: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 samsungtv_apps: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 samsungtv_apps: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 samsungtv_apps: STATE tinyint(2) unsigned NOT NULL DEFAULT '0'
 samsungtv_apps: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}