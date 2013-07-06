<?php
header("Content-type: text/plain; charset=utf-8");
include 'config.inc.php';

if (!isset($_SERVER['argv']) || !isset($_SERVER['argv'][1])) {
	die("\nNo arguments\n\n");
}

$result = urldecode($_SERVER['argv'][1]);
$result = json_decode($result);

if (!$result) {
	die("\nInvalid arguments\n\n");
}

echo "\nCRON START\n";

echo "\nChecking ".$result->serviceName.(!empty($result->port) ? ' :'.$result->port : '')." response from ".$result->ip."...\n\n";
$check = ServiceCheck::doCheck($result->ip, $result->port);//-- Make check

$now = date('YmdHis');

if ($check->status) {//-- Service is ok
	$fields = array(
		'updated' => $now,
		'next_update' => HelperGuru::getNextUpdate($result->mmi),
		'extra' => json_encode($check->msg),
		'status' => 1
	);
	//-- Update the date and msg
	new ServiceManagement($result->id);
	ServiceManagement::update($fields);

	if (empty($result->status)) {//-- If the service was previously down, also send the recovery alert, if set like that, and update the service's history log
		if (!empty($result->recovery) && !empty($result->alert)) {
			HelperGuru::sendRecoveryAlert($result->alert, $result->id);
		}

		$fieldsLog = array(
			'user' => $result->user,
			'server' => $result->server,
			'service' => $result->id,
			'recovery' => $now
		);
		ServiceManagement::logRecovery($fieldsLog);
	}

	ServiceManagement::removeFromAmazonQueue($result->id);
} else {//-- Boo. Service is down, just mark it offline and send the alert, if set like that
	$fields = array(
		'updated' => $now,
		'next_update' => HelperGuru::getNextUpdate($result->mmi),
		'extra' => json_encode($check->msg),
		'status' => 0
	);
	//-- Update the date and error
	new ServiceManagement($result->id);
	ServiceManagement::update($fields);

	if (!empty($result->status) && !empty($result->alert)) {//-- If the service wasn't previously down, also send the alert, if set like that, and update the service's history log
		HelperGuru::sendAlert($result->alert, $result->id);

		$fieldsLog = array(
			'user' => $result->user,
			'server' => $result->server,
			'service' => $result->id,
			'error' => $check->msg,
			'date' => $now
		);
		ServiceManagement::logOffline($fieldsLog);
	}

	ServiceManagement::removeFromAmazonQueue($result->id);
}

echo "\nRESULT:\n\n";
var_dump($check);
echo "\n";

DB::end();

echo "\nCRON END\n\n";
?>