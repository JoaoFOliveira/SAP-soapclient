<?php 

function connect() {

	$appserver = "XXX.XXX.XXX.XXX";
	$port = "XXXX";
	$client = "XXX";
	$services = "XXXXXXXX";

	$login = "usernameSAP";
	$password = "passwordSAP";
	
	$webservice = new stdClass();
	$webservice->client = null;
	$webservice->message = "";
	
	$wsdl = "http://".$appserver.":".$port."/sap/bc/soap/wsdl11?services=".$services."&sap-client=".$client;

	$context = stream_context_create( array(
     'http' => array(
       'protocol_version'=> '1.0', 
       'header'=> 'Content-Type: text/xml;' ,
     ),
   	));

	$options = array("login" => $login, "password" => $password, 'stream_context' => $context, 'encoding'=>'ISO-8859-1');
	
	$status = test_connection($wsdl, $options);
	if($status === 200) {
		$webservice->client = new SoapClient($wsdl, $options);
		$webservice->client->__setCookie("MYSAPSSO2", get_ssocookie());
		$webservice->client->__setCookie("sap-usercontext", "sap-client=".$client);
	} else if($status === 401) {
		$webservice->message = "Error: Authentication failed.";
	} else {
		$webservice->message = "Error: Webservice is down.";
	}

	return $webservice;
}


function test_connection($wsdl, $options) {
	$ch = curl_init();
	 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
	curl_setopt($ch, CURLOPT_URL, $wsdl);
	curl_setopt($ch, CURLOPT_NOBODY, true );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_USERPWD, $options['login'] . ":" .$options['password']);
	
	curl_exec($ch);
	return curl_getinfo($ch, CURLINFO_HTTP_CODE);
}

function get_ssocookie() {
	$has_sso_cookie = false;
	$sso_cookie = "";
	
	if(isset($http_response_header) && !is_null($http_response_header)) {
		foreach($http_response_header as $header) {
			$sso_position = stripos($header, "MYSAPSSO2");
			$has_sso_cookie = $sso_position !== false;
	
			if($has_sso_cookie) {
				$parts =  explode(";", Str::sub($header, $sso_position));
				$sso_cookie = Str::sub($parts[0], stripos($parts[0], "=") + 1);
				break;
			}
		}
	}
	
	return $sso_cookie;
}

$webservice = connect();

// Get raw webservice data
$rawwsdata = $webservice->client->SAP_FUNCTION();

?>