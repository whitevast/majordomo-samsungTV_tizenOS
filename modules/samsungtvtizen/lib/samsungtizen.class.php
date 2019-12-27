<?php
/**
* Класс для коммуникации с телевизорами Samsung на TizenOS v3 посредством ssl websocket, REST API и SOAP.
* @author <vashal@mail.ru>
* @copyright 2019 Vashestyuk Alexander aka V.A.S.t <vashal@mail.ru> (c)
* @version 0.1a
*/
class samsung{
	
	function __construct() {
	}

	function gettoken($ip){
		$socket = $this->connecttv($ip);
		if (!$socket) return false;
		$wsdata = fread($socket, 2000);
		fclose($socket);
		$wsdata = $this->hybi10Decode($wsdata);
		$data = json_decode($wsdata, true);
		$token = $data['data']['token'];
		if(!$token) return -1;
		return $token;
	}

	function getapps($id){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$socket = $this->connecttv($device["IP"], $device["TOKEN"]);
		if(!$socket) return false;
		$data = '{"method":"ms.channel.emit","params":{"event":"ed.installedApp.get","to":"host"}}';
		fwrite($socket,$this->hybi10Encode($data));
		usleep(500);
		$wsdata = fread($socket, 10000);
		fclose($socket);
		$wsdata = json_decode($this->hybi10Decode($wsdata), true);
		return $wsdata;
	}

	function sendkey($id, $key){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$socket = $this->connecttv($device["IP"], $device["TOKEN"]);
		if(!$socket){
			if($key == "KEY_POWER") {
				$wol = $this->wol($device["MAC"]);
				return $wol;
			}
			return false;
		} else{
			$data = '{"method":"ms.remote.control","params":{"Cmd":"Click","DataOfCmd":"'.$key.'","Option":"false","TypeOfRemote":"SendRemoteKey"}}';
			fwrite($socket,$this->hybi10Encode($data));
			fclose($socket);
			return true;
		}
	}
	
	function ineturl($id, $url){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$socket = $this->connecttv($device["IP"], $device["TOKEN"]);
		if(!$socket) return false;
		$data = '{"method":"ms.channel.emit","params":{"event":"ed.apps.launch","to":"host","data":{"appId":"org.tizen.browser","action_type":"NATIVE_LAUNCH","metaTag":"'.$url.'"}}}';
		fwrite($socket,$this->hybi10Encode($data));
		fclose($socket);
		return true;	
	}

	function appmgnt($id, $appid, $command = ''){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$ch = curl_init("http://" . $device["IP"] . ":8001/api/v2/applications/" . $appid);
		if ($command == 'start') curl_setopt($ch, CURLOPT_POST, 1); //запустить
		else if($command == 'close') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); //закрыть
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		curl_close($ch);
		if (!$html)	return false;		
		return $html;
	}

	function wol($mac){
		$macAddressHexadecimal = str_replace([':', '-'], '', $mac);
		$macAddressBinary = pack('H12', $macAddressHexadecimal);
		$magicPacket = str_repeat(chr(0xff), 6).str_repeat($macAddressBinary, 16);
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if ($sock !== false) {
			$options = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);
			if ($options !== false) {
				socket_sendto($sock, $magicPacket, strlen($magicPacket), 0, '255.255.255.255', 9);
				socket_close($sock);
				return true;
			}
		}
		return false;
	}
	
	function soap($ip, $command, $argument, $protocol){
		$ch = curl_init('http://'.$ip.':9197/upnp/control/'.$protocol.'1');
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array('SOAPAction: "urn:schemas-upnp-org:service:'.$protocol.':1#'.$command.'"', 'content-type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="utf-8"?>
		<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
			<s:Body>
				<u:'.$command.' xmlns:u="urn:schemas-upnp-org:service:'.$protocol.':1">
					<InstanceID>0</InstanceID>'.
						$argument.'
				</u:'.$command.'>
			</s:Body>
		</s:Envelope>');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		if (!$html)	return false;
		curl_close($ch);	
		return $html;
	}
	
	function getvol($id){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$vol = $this->soap($device["IP"], 'GetVolume', '<Channel>Master</Channel>', 'RenderingControl');
		if(!$vol) return false;
		$clean_xml = str_ireplace(['s:', 'u:'], '', $vol);
		$xml = simplexml_load_string($clean_xml);
		return $xml->Body->GetVolumeResponse->CurrentVolume;
	}
	function setvol($id, $value){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		if(!$this->soap($device["IP"], 'SetVolume', '<Channel>Master</Channel><DesiredVolume>'.$value.'</DesiredVolume>', 'RenderingControl')) return false;
		return true;
	}
	function getmute($id){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		$vol = $this->soap($device["IP"], 'GetMute', '<Channel>Master</Channel>', 'RenderingControl');
		if(!$vol) return false;
		$clean_xml = str_ireplace(['s:', 'u:'], '', $vol);
		$xml = simplexml_load_string($clean_xml);
		return $xml->Body->GetVolumeResponse->CurrentMute;
	}
	function setmedia($id, $url){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		if(!$this->soap($device["IP"], 'SetAVTransportURI', '<CurrentURI>'.$url.'</CurrentURI><CurrentURIMetaData></CurrentURIMetaData>', 'AVTransport')) return false;
		return true;
	}
	function play($id){
		$device = SQLSelectOne("SELECT * FROM samsungtv_devices WHERE ID='".$id."'");
		if(!$this->soap($device["IP"], 'Play', "<Speed>1</Speed>", 'AVTransport')) return false;
		return true;
	}
	
	function dev($id){
	$req = SQLSelect('SELECT * FROM samsungtv_devices');
	foreach($req as $device){
		if($id == $device['ID'] or $id == $device['TITLE'] or $id == $device['IP'])	return $device;
	}
	debmes('STVPower: Неверное имя устройства');
}
	
	function connecttv($ip, $token=''){ //подключение к телевизору
		$name = utf8_decode(base64_encode('MajorDoMo'));
		$path = "/api/v2/channels/samsung.remote.control?name=$name&token=$token"; //

		$context = stream_context_create([
		   'ssl' => [
			'verify_peer_name' => false,
			'verify_peer' => false
		   ]
		]);

		@$socket = stream_socket_client("ssl://$ip:8002", $errorNumber, $errorString, 1, STREAM_CLIENT_CONNECT, $context);

		if (!$socket) {
			return(false);
		} else {       
			$head = "GET " . $path . " HTTP/1.1\r\n" .
				"Host: localhost\r\n" .
				"Upgrade: websocket\r\n" .
				"Connection: Upgrade\r\n" .
				"Sec-WebSocket-Key: tQXaRIOk4sOhqwe7SBs43g==\r\n" .
				"Sec-WebSocket-Version: 13\r\n"."\r\n";
			fwrite($socket, $head);
			$headers = fread($socket, 2000);
			//echo $headers;
			usleep(500);
			if($token != '') fread($socket, 2000);
			return($socket);
		}
	}

	//Функции кодирования/декодирования вебсокетов
	////////////////////////////////////-----------------------------------//////////////////////////////////
	 function hybi10Decode($data){
		$bytes = $data;
		$dataLength = '';
		$mask = '';
		$coded_data = '';
		$decodedData = '';
		$secondByte = sprintf('%08b', ord($bytes[1]));
		$masked = ($secondByte[0] == '1') ? true : false;
		$dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);
	 
		if($masked === true)
		{
			if($dataLength === 126)
			{
			   $mask = substr($bytes, 4, 4);
			   $coded_data = substr($bytes, 8);
			}
			elseif($dataLength === 127)
			{
				$mask = substr($bytes, 10, 4);
				$coded_data = substr($bytes, 14);
			}
			else
			{
				$mask = substr($bytes, 2, 4);       
				$coded_data = substr($bytes, 6);        
			}   
			for($i = 0; $i < strlen($coded_data); $i++)
			{       
				$decodedData .= $coded_data[$i] ^ $mask[$i % 4];
			}
		}
		else
		{
			if($dataLength === 126)
			{          
			   $decodedData = substr($bytes, 4);
			}
			elseif($dataLength === 127)
			{           
				$decodedData = substr($bytes, 10);
			}
			else
			{               
				$decodedData = substr($bytes, 2);       
			}       
		}   
	 
		return $decodedData;
	}
	 
	 function hybi10Encode($payload, $type = 'text', $masked = true) {
		$frameHead = array();
		$frame = '';
		$payloadLength = strlen($payload);
	 
		switch ($type) {
			case 'text':
				// first byte indicates FIN, Text-Frame (10000001):
				$frameHead[0] = 129;
				break;
	 
			case 'close':
				// first byte indicates FIN, Close Frame(10001000):
				$frameHead[0] = 136;
				break;
	 
			case 'ping':
				// first byte indicates FIN, Ping frame (10001001):
				$frameHead[0] = 137;
				break;
	 
			case 'pong':
				// first byte indicates FIN, Pong frame (10001010):
				$frameHead[0] = 138;
				break;
		}
	 
		// set mask and payload length (using 1, 3 or 9 bytes)
		if ($payloadLength > 65535) {
			$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 255 : 127;
			for ($i = 0; $i < 8; $i++) {
				$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
			}
	 
			// most significant bit MUST be 0 (close connection if frame too big)
			if ($frameHead[2] > 127) {
				$this->close(1004);
				return false;
			}
		} elseif ($payloadLength > 125) {
			$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 254 : 126;
			$frameHead[2] = bindec($payloadLengthBin[0]);
			$frameHead[3] = bindec($payloadLengthBin[1]);
		} else {
			$frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
		}
	 
		// convert frame-head to string:
		foreach (array_keys($frameHead) as $i) {
			$frameHead[$i] = chr($frameHead[$i]);
		}
	 
		if ($masked === true) {
			// generate a random mask:
			$mask = array();
			for ($i = 0; $i < 4; $i++) {
				$mask[$i] = chr(rand(0, 255));
			}
	 
			$frameHead = array_merge($frameHead, $mask);
		}
		$frame = implode('', $frameHead);
		// append payload to frame:
		for ($i = 0; $i < $payloadLength; $i++) {
			$frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
		}
	 
		return $frame;
	}
}
///////////////////////////////////////////////------------------------------------------------------///////////////////////////