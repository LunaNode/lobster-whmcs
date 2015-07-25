<?php

require_once("common.php");

$LOBSTER_DEBUG = false;

function lobster_API($config, $action, $params = array()) {
	$fields = array();
	$fields['action'] = $action;
	$fields['secret'] = $config['secret'];
	foreach($params as $key => $value) {
		$fields[urlencode($key)] = urlencode($value);
	}

	$fields_string = "";
	foreach($fields as $key => $value) {
		$fields_string .= $key . '=' . $value . '&';
	}
	rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $config['endpoint'] . 'whmcs_connector');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

	//execute post
	$response = curl_exec($ch);

	if($response === false) {
		$message = $LOBSTER_DEBUG ? curl_error($ch) : "failed to communicate with Lobster";
		return array('success' => false, 'message' => $message);
	}

	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	//close connection
	curl_close($ch);
	return array('success' => $code == 200, 'message' => $response);
}

function lobster_ConfigOptions() {
	return array(
		"Endpoint" => array("Type" => "text", "Size" => "20", "Description" => "URL of the Lobster installation for API calls, e.g. https://lobster.lunanode.com/"),
		"Secret" => array("Type" => "text", "Size" => "20", "Description" => "This should match the corresponding setting for the WHMCS module on Lobster."),
		"Amount" => array("Type" => "text", "Size" => "20", "Description" => "Decimal amount of credit to add to the Lobster account (e.g. 30.00)."),
		"Public URL" => array("Type" => "text", "Size" => "20", "Description" => "Public URL of Lobster; leave blank if same as endpoint."),
	);
}

function lobster_GetConfig($params) {
	if(!isset($params['configoption1']) || !isset($params['configoption2']) || !isset($params['configoption3'])) {
		return false;
	}

	$endpoint = $params['configoption1'];
	$secret = $params['configoption2'];
	$amount = $params['configoption3'];
	$publicUrl = $params['configoption4'];
	if(!$endpoint || !$secret) {
		return false;
	}

	// add trailing slash if needed
	if(strlen($endpoint) >= 1 && substr($endpoint, -1) != '/') {
		$endpoint .= '/';
	}
	if(strlen($publicUrl) >= 1 && substr($publicUrl, -1) != '/') {
		$publicUrl .= '/';
	} else if(strlen($publicUrl) == 0) {
		$publicUrl = $endpoint;
	}
	return array('endpoint' => $endpoint, 'secret' => $secret, 'amount' => $amount, 'publicUrl' => $publicUrl);
}

function lobster_CreateAccount($params) {
	if(!lobster_customFieldExists($params['pid'], 'userid')) {
		return 'Custom field userid has not been configured.';
	}

	$config = lobster_GetConfig($params);
	if($config === false) {
		return "Error: product misconfiguration.";
	}

	if(empty($params['customfields']['userid'])) {
		$result = lobster_API($config, 'register', array('email' => $params['clientsdetails']['email']));
		if(!$result['success']) {
			return "Error: {$result['message']}.";
		} else if(!is_numeric($result['message'])) {
			return "Error: got invalid response: {$result['message']}.";
		} else {
			lobster_customFieldSet($params['pid'], 'userid', $params['serviceid'], $result['message']);
			$user_id = intval($result['message']);
		}
	} else {
		$user_id = intval($params['customfields']['userid']);
	}

	$result = lobster_API($config, 'credit', array('user_id' => $user_id, 'amount' => $config['amount']));
	if(!$result['success']) {
		return "Error: {$result['message']}.";
	} else {
		return "success";
	}
}

function lobster_TerminateAccount($params) {
	return "success";
}

function lobster_SuspendAccount($params) {
	return "success";
}

function lobster_UnsuspendAccount($params) {
	return "success";
}

function lobster_ChangePackage($params) {
	return "Error: operation not supported.";
}

function lobster_ClientArea($params) {
	if(empty($params['customfields']['userid'])) {
		return 'User does not exist.';
	}

	$config = lobster_GetConfig($params);
	$result = lobster_API($config, 'token', array('user_id' => $params['customfields']['userid']));
	if(!$result['success']) {
		return "Error: {$result['message']}.";
	} else {
		return "<a href=\"{$config['publicUrl']}whmcs_token?token={$result['message']}\">Authenticate into the control panel.</a>";
	}
}

function lobster_AdminLink($params) {
	return "";
}

function lobster_LoginLink($params) {
	echo "";
}

function lobster_ClientAreaCustomButtonArray() {
	return array();
}

function lobster_AdminCustomButtonArray() {
	return array();
}

function lobster_UsageUpdate($params) {
	return;
}

function lobster_AdminServicesTabFields($params) {
	return array();
}

function lobster_AdminServicesTabFieldsSave($params) {
	return;
}

?>
