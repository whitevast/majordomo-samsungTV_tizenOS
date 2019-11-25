<?php
/*
* @version 0.1 (wizard)
*/

include_once(DIR_MODULES . 'samsungtvtizen/lib/samsungtizen.class.php');
$sams = new samsung();
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='samsungtv_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
   $rec['TITLE']=gr('title');
   $rec['IP']=gr('ip');
   if ($rec['TITLE']=='' or $rec['IP']=='') {
	$out['ERR_ALERT']=($rec['TITLE']=='')?"Введите название телевизора!":"Введите адрес телевизора!";
    $ok=0;
   }
   if($ok){
	 $rec['TOKEN'] = $sams->gettoken($rec['IP']);
	 if(!$rec['TOKEN']){
		 $ok=0;
		 $out['ERR_ALERT']="Неправильный адрес телевизора или телевизор выключен!";
	 }
	 else if($rec['TOKEN'] == -1){
		$ok=0;
		$out['ERR_ALERT']="Что-то пошло не так... Возможно, доступ к телевизору заблокирован";
	}
	 else {
		$url = "http://".$rec['IP'].":9197/dmr";
		$modeldata = new SimpleXMLElement(file_get_contents($url));
		$rec['SERIAL'] =  $modeldata->device->serialNumber;
		$rec['MODEL'] =  $modeldata->device->modelName;
		$arp = (IsWindowsOS())? 'for /f "tokens=2" %i in ('."'arp -a ".$rec['IP']." ^| findstr ..-..-..-..-..-..') do @echo %i":"arp ".$rec['IP']." | grep -v addr | awk '{print $3}'";
		$rec['MAC'] = shell_exec($arp); //определяем mac
	}
   }
  }
   
  
  // step: data
  if ($this->tab=='data') {
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
	 $wsdata = $sams->getapps($rec['ID']);
	 $app['DEVICE_ID'] = $rec['ID'];
	 $app['TITLE'] = 'Internet';
	 $app['APPID'] = 'URL';
	 SQLInsert('samsungtv_apps', $app);
	 for ($i=0; $i<count($wsdata['data']['data']); $i++){
		 if($wsdata['data']['data'][$i]['name'] == 'Internet') continue;
		 $app['TITLE'] = $wsdata['data']['data'][$i]['name']; //название приложения
		 $app['APPID'] = $wsdata['data']['data'][$i]['appId']; //ID приложения
		 SQLInsert('samsungtv_apps', $app);
	 }
	 $codes = [['KEY_KEY','Команда'],
		  ['KEY_POWER','ВКЛ/ВЫКЛ'],
		  ['KEY_VOL','Управление громкостью'],
		  ['KEY_CH','Управление каналами'],
		  ['KEY_NAVI','Навигация'],
		  ['KEY_MENU','Меню'],
		  ['KEY_HOME','Домой'],
		  ['KEY_STOP','Стоп'],
		  ['KEY_PLAY','Воспроизведение'],
		  ['KEY_PAUSE','Пауза'],
		  ['KEY_HDMI','HDMI'],
		  ['KEY_HDMI1','HDMI1'],
	      ['KEY_NUM','Цифровые кнопки'],
		  ['KEY_CLR','Цветные кнопки']];
		  
	  $code['DEVICE_ID'] = $rec['ID'];
	  for ($i=0; $i<count($codes); $i++){
		  $code['TITLE'] = $codes[$i][1];
		  $code['VALUE'] = $codes[$i][0];
		  SQLInsert('samsungtv_codes', $code);
	  }
	  
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }
  // step: data
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
    if ($this->mode=='update') {
    global $title_new;
	if ($title_new) {
	 $prop=array('TITLE'=>$title_new,'DEVICE_ID'=>$rec['ID']);
	 $new_id=SQLInsert('samsungtv_codes',$prop);
	}
   }
   global $delete_id;
   if ($delete_id) {
	$properties=SQLSelectOne("SELECT * FROM samsungtv_codes WHERE ID='".(int)$delete_id."'");
    if ($properties['LINKED_PROPERTY']) removeLinkedProperty($properties['LINKED_OBJECT'], $properties['LINKED_PROPERTY'], $this->name);
	SQLExec("DELETE FROM samsungtv_codes WHERE ID='".(int)$delete_id."'");
   }
   
   global $test_id;
   if ($test_id) {
    $key = SQLSelectOne("SELECT * FROM samsungtv_codes WHERE ID='".(int)$test_id."'")['VALUE'];
	$sams->sendkey($rec["ID"], $key);
	$this->redirect("?data_source=&view_mode=edit_samsungtv_devices&id=".$rec['ID']."&tab=data");
	}
   
   $properties=SQLSelect("SELECT * FROM samsungtv_codes WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
	if ($test_id) continue; 
    if ($this->mode=='update') {
	  $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
      global ${'value'.$properties[$i]['ID']};
      $properties[$i]['VALUE']=trim(${'value'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      SQLUpdate('samsungtv_codes', $properties[$i]);
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
   }
   $out['PROPERTIES']=$properties;   
  }
  
    // step: apps
  if ($this->tab=='apps') {
   //dataset2
   $new_id=0;
   global $delete_id;
   if ($delete_id) {
	$properties=SQLSelectOne("SELECT * FROM samsungtv_apps WHERE ID='".(int)$delete_id."'");
    if ($properties['LINKED_PROPERTY']) removeLinkedProperty($properties['LINKED_OBJECT'], $properties['LINKED_PROPERTY'], $this->name); //если есть привязанное свойство - удаляем привязку
    SQLExec("DELETE FROM samsungtv_apps WHERE ID='".(int)$delete_id."'");
   }
   global $start_id;
   if ($start_id) {
    $id = SQLSelectOne("SELECT * FROM samsungtv_apps WHERE ID='".(int)$start_id."'")['APPID'];
	$sams->appmgnt($rec["ID"], $id, "start");
	$this->redirect("?data_source=&view_mode=edit_samsungtv_devices&id=".$rec['ID']."&tab=apps");
   }
   global $close_id;
   if ($close_id) {
    $id = SQLSelectOne("SELECT * FROM samsungtv_apps WHERE ID='".(int)$close_id."'")['APPID'];
	$sams->appmgnt($rec["ID"], $id, "close");
	$this->redirect("?data_source=&view_mode=edit_samsungtv_devices&id=".$rec['ID']."&tab=apps");
   }
   
    global $update_id;
	if ($update_id) {
		$id = SQLSelect("SELECT * FROM samsungtv_apps WHERE ID='".$rec['ID']."'");
		$wsdata = $sams->getapps($rec['ID']);
		$app['DEVICE_ID'] = $rec['ID'];
		for ($i=0; $i<count($wsdata['data']['data']); $i++){
			$app['TITLE'] = $wsdata['data']['data'][$i]['name']; //название приложения
			$app['APPID'] = $wsdata['data']['data'][$i]['appId']; //ID приложения
			SQLInsertUpdate('samsungtv_apps', $app);
		}
		$this->redirect("?data_source=&view_mode=edit_samsungtv_devices&id=".$rec['ID']."&tab=apps");
   }

   $appsdata=SQLSelect("SELECT * FROM samsungtv_apps WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   $total=count($appsdata);
   for($i=0;$i<$total;$i++) {
    if ($appsdata[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
	  $old_linked_object=$appsdata[$i]['LINKED_OBJECT'];
      $old_linked_property=$appsdata[$i]['LINKED_PROPERTY'];
      global ${'linked_object'.$appsdata[$i]['ID']};
      $appsdata[$i]['LINKED_OBJECT']=trim(${'linked_object'.$appsdata[$i]['ID']});
      global ${'linked_property'.$appsdata[$i]['ID']};
      $appsdata[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$appsdata[$i]['ID']});
      global ${'linked_method'.$appsdata[$i]['ID']};
      $appsdata[$i]['LINKED_METHOD']=trim(${'linked_method'.$appsdata[$i]['ID']});
      SQLUpdate('samsungtv_apps', $appsdata[$i]);
      if ($old_linked_object && $old_linked_object!=$appsdata[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$appsdata[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($appsdata[$i]['LINKED_OBJECT'] && $appsdata[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($appsdata[$i]['LINKED_OBJECT'], $appsdata[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
   }
   $out['APPSDATA']=$appsdata;   
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);