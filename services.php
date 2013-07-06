<?php
header("Content-type: text/plain; charset=utf-8");
include 'config.inc.php';

$now = date('YmdHis');

$sql = "SELECT `service` FROM `tbl_amazon_queue`";
$queue = DB::execute($sql);

if (!$queue) {
	die("\nNo services on queue\n\n");
}

$ids = array();

foreach ($queue as $item) {
	$ids[] = $item->service;
}

$ids = implode(',', $ids);

$sql = "SELECT a.`id`, a.`user`, a.`service`, a.`server`, a.`mmi`, a.`alert`, a.`recovery`, a.`status`, b.`name` AS `serviceName`, b.`port`, c.`ip`
		FROM `tbl_user_services` a
		INNER JOIN `tbl_services` b ON (a.`service` = b.`id`)
		INNER JOIN `tbl_user_servers` c ON (a.`server` = c.`id`)
		INNER JOIN `wz_users` d ON (a.`user` = d.`id`)
		WHERE c.`status` = 1
		AND d.`status` = 1
		/*AND a.`next_update` <= '".DB::prepare($now)."'*/
		AND a.`id` IN (".$ids.")
		ORDER BY a.`next_update` ASC";

$results = DB::execute($sql);

if (!$results) {
	die("\nNo services to check\n\n");
}

$filePath = pathinfo(__FILE__);

foreach ($results as $result) {
	exec("php ".$filePath['dirname']."/check.php ".urlencode(json_encode($result))." &");
	//echo "\nphp ".$filePath['dirname']."/check.php ".urlencode(json_encode($result))." &\n\n";
}

DB::end();
?>