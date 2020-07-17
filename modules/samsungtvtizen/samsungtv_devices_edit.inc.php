<?php
/*
* @version 0.1 (wizard)
*/
 $sams = new samsung($this->debug);
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
   $rec['PORT']='8002';
   if(gr('alt_port')) $rec['PORT']='8001';
   if(gr('smrtthgs')){
	   $rec['TOKEN']=gr('token');
	   $rec['PORT']=gr('id_tv');
   }
   if ($rec['TITLE']=='' or $rec['IP']=='') {
	$out['ERR_ALERT']=($rec['TITLE']=='')?"Введите название телевизора!":"Введите адрес телевизора!";
    $ok=0;
   }
   if($ok){
	    if(!gr('smrtthgs')) $rec['TOKEN'] = $sams->connecttv($rec['IP'], $rec['PORT'], '');
		else {
			$device = $sams->smartthingsapi($rec['TOKEN'], $rec['PORT'], 'devices');
			$ok=0;
			$out['ERR_ALERT']="Данный ID не найден в ваших устройствах SmartThings!";
			foreach ($device['items'] as $dev){
				if ($dev['deviceId'] == $rec['PORT']){
					$sams->smartthingsapi($rec['TOKEN'], $rec['PORT'], 'refresh');
					$device = $sams->smartthingsapi($rec['TOKEN'], $rec['PORT'], 'status');
					print_r($device);
					if (!isset($device['switch']['switch']['value'])){
						$out['ERR_ALERT']="Телевизор не имеет функции управления через SmartThings!";
					}
					else{
						$rec['MODEL'] = $device['ocf']['mnmo']['value'];
						$arp = (IsWindowsOS())? 'for /f "tokens=2" %i in ('."'arp -a ".$rec['IP']." ^| findstr ..-..-..-..-..-..') do @echo %i":"sudo arp ".$rec['IP']."| grep -v addr | awk '{print $3}'";
						$rec['MAC'] = exec($arp); //определяем mac
						$ok=1;
						break;
					}
				}
			}
		}
		if(!$rec['TOKEN']){
			$ok=0;
			$out['ERR_ALERT']="Неправильный адрес телевизора или телевизор выключен!";
		}
		else if($rec['TOKEN'] == -1){
			$ok=0;
			$out['ERR_ALERT']="Соединение установлено, но токен не был получен. См. лог-файл ".date("Y-m-d")."_samsungtvtizen.log";
		}
		if($ok and !gr('smrtthgs')) {
			$url = "http://".$rec['IP'].":9197/dmr";
			$modeldata = new SimpleXMLElement(file_get_contents($url));
			$rec['SERIAL'] = $modeldata->device->serialNumber;
			$rec['MODEL'] = $modeldata->device->modelName;
			$arp = (IsWindowsOS())? 'for /f "tokens=2" %i in ('."'arp -a ".$rec['IP']." ^| findstr ..-..-..-..-..-..') do @echo %i":"sudo arp ".$rec['IP']."| grep -v addr | awk '{print $3}'";
			$rec['MAC'] = exec($arp); //определяем mac
		}
	}
   }

   
  
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
	 if(!gr('smrtthgs')){
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
	}
	 $codes = (!gr('smrtthgs'))? [['KEY_KEY','Команда'],
		  ['KEY_POWER','ВКЛ/ВЫКЛ'],
		  ['KEY_VOL','Управление громкостью'],
		  ['KEY_SETVOL','Установка громкости'],
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
		  ['KEY_CLR','Цветные кнопки']]
		  :
		  [['power','ВКЛ/ВЫКЛ'],
		  ['volume','Управление громкостью'],
		  ['setvol','Установка громкости'],
		  ['channel','Управление каналами'],
		  ['stop','Стоп'],
		  ['play','Воспроизведение'],
		  ['pause','Пауза'],
//		  ['KEY_HDMI','HDMI'],
//		  ['KEY_HDMI1','HDMI1'],
		  ['next','Следующий'],
		  ['previous','Предыдущий'],
		  ['fast_forward','Перемотка вперед'],
		  ['rewind','Перемотка назад']];
		  
	  $code['DEVICE_ID'] = $rec['ID'];
	  for ($i=0; $i<count($codes); $i++){
		  $code['TITLE'] = $codes[$i][1];
		  $code['VALUE'] = $codes[$i][0];
		  SQLInsert('samsungtv_codes', $code);
	  }
	  $data = [['Статус','ST'],
		  ['Громкость','VOL']];
	  if(!gr('smrtthgs')) array_push($data, ['Активное приложение','APP']);
	  $dataadd['DEVICE_ID'] = $rec['ID'];
	  for ($i=0; $i<count($data); $i++){
		  $dataadd['TITLE'] = $data[$i][0];
		  $dataadd['KEY_ID'] = $data[$i][1];
		  SQLInsert('samsungtv_data', $dataadd);
	  }
	  setGlobal('cycle_samsungtvtizenControl','start');
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }
  // Вкладка Данные
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
    if ($this->mode=='update') {
    global $title_new;
	if ($title_new) {
	 $prop=array('TITLE'=>$title_new,'DEVICE_ID'=>$rec['ID']);
	 $new_id=SQLInsert('samsungtv_data',$prop);
	}
   }
   $dtable=SQLSelect("SELECT * FROM samsungtv_data WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   
   $total=count($dtable);
   for($i=0;$i<$total;$i++) {
    if ($dtable[$i]['ID']==$new_id) continue;
	if ($test_id) continue; 
    if ($this->mode=='update') {
	  $old_linked_object=$dtable[$i]['LINKED_OBJECT'];
      $old_linked_property=$dtable[$i]['LINKED_PROPERTY'];
      global ${'linked_object'.$dtable[$i]['ID']};
      $dtable[$i]['LINKED_OBJECT']=trim(${'linked_object'.$dtable[$i]['ID']});
      global ${'linked_property'.$dtable[$i]['ID']};
      $dtable[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$dtable[$i]['ID']});
	  	// Если юзер удалил привязанные свойство и метод, но забыл про объект, то очищаем его.
	  if ($dtable[$i]['LINKED_OBJECT'] != '' && ($dtable[$i]['LINKED_PROPERTY'] == '' && $dtable[$i]['LINKED_METHOD'] == '')) {
	  	$dtable[$i]['LINKED_OBJECT'] = '';
	  }
      SQLUpdate('samsungtv_data', $dtable[$i]);
      if ($old_linked_object && $old_linked_object!=$dtable[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$dtable[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($dtable[$i]['LINKED_OBJECT'] && $dtable[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($dtable[$i]['LINKED_OBJECT'], $dtable[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
	 $dtable[$i]['SDEVICE_TYPE'] = 'tv';
   }
   $out['DTABLE']=$dtable;   
  }
  
  // Вкладка Команды
  if ($this->tab=='codes') {
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
		if($rec["PORT"]=='8001' or $rec["PORT"]=='8002') $sams->sendkey($rec["ID"], $key);
		else $sams->ssendkey($rec["ID"], $key);
		$this->redirect("?data_source=&view_mode=edit_samsungtv_devices&id=".$rec['ID']."&tab=codes");
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
	  if($properties[$i]['VALUE'] != 'KEY_KEY') $properties[$i]['VALUE']=trim(${'value'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      // Если юзер удалил привязанные свойство и метод, но забыл про объект, то очищаем его.
      if ($properties[$i]['LINKED_OBJECT'] != '' && ($properties[$i]['LINKED_PROPERTY'] == '' && $properties[$i]['LINKED_METHOD'] == '')) {
          $properties[$i]['LINKED_OBJECT'] = '';
      }
      SQLUpdate('samsungtv_codes', $properties[$i]);
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
	 $properties[$i]['SDEVICE_TYPE'] = 'tv';
   }
   $out['PROPERTIES']=$properties;   
  }
  
    // Вкладка Приложения
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
	  	  	// Если юзер удалил привязанные свойство и метод, но забыл про объект, то очищаем его.
	  if ($appsdata[$i]['LINKED_OBJECT'] != '' && ($appsdata[$i]['LINKED_PROPERTY'] == '' && $appsdata[$i]['LINKED_METHOD'] == '')) {
	  	$appsdata[$i]['LINKED_OBJECT'] = '';
	  }
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
