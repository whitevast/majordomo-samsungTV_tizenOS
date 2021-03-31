<?php
/*
* @version 0.1 (wizard)
*/
 $sams = new samsung($this->debug);
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['samsungtv_devices_qry'];
  } else {
   $session->data['samsungtv_devices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_samsungtv_devices="ID DESC";
  $out['SORTBY']=$sortby_samsungtv_devices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM samsungtv_devices WHERE $qry ORDER BY ".$sortby_samsungtv_devices);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
	   $data = SQLSelect("SELECT * FROM samsungtv_data WHERE DEVICE_ID = '".$res[$i]['ID']."'");
	   foreach($data as $value){
		   if($value['KEY_ID']=='ST') $res[$i]['ONLINE'] = $value['VALUE'];
		   elseif($value['KEY_ID']=='VOL') $res[$i]['VOLUME'] = $value['VALUE'];
	   }
	   if($res[$i]['PORT'] == '8001' or $res[$i]['PORT'] == '8002'){
		   $app = SQLSelectOne("SELECT TITLE FROM samsungtv_apps WHERE DEVICE_ID='".$res[$i]['ID']."' AND STATE='1'");
		   if($app['TITLE'] == '') $app['TITLE'] = "ТВ, HDMI или DLNA";
	   }else{
		   $app = SQLSelectOne("SELECT VALUE FROM samsungtv_data WHERE DEVICE_ID='".$res[$i]['ID']."' AND KEY_ID = 'APP'");
		   $app['TITLE'] = $app['VALUE'];
		   if($app['TITLE'] == '') $app['TITLE'] = "ТВ, HDMI или DLNA";
	   }
	   $res[$i]['APP'] = $app['TITLE'];
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
