<?php
//-- HelperGuru Class
class HelperGuru {
	public static $url;

	public function __construct($url) {
		self::$url = $url;
	}

	public static function getNextUpdate($mmi) {
		$return = date('YmdHis', 
			mktime(
				date('H'),
				date('i') + $mmi,//-- Add the minutes
				date('s'),
				date('m'),
				date('d'),
				date('Y')
			)
		);

		return $return;
	}

	public static function sendAlert($email, $serviceID) {
		$service = self::getService($serviceID);
		$server = self::getServer($service->server);
		$user = self::getUser($service->user);
		$plan = self::getUserPlan($service->user);

		if (!$service || !$server || !$user || !$plan) {
			return false;
		}

		//-- Check if the user is still within the allowed email limit, if not, stop this nonsense
		if ($plan->emails >= $plan->max_emails && $plan->max_emails > 0) {
			return false;
		}

		//-- Send Email
		$tpl = self::eTemplate('not_working_alert');
		$tpl_search = array('%%USER%%', '%%SERVER%%', '%%IP%%', '%%SERVICE%%', '%%NOW%%');
		$tpl_replace = array($user->name, $server->name, $server->ip, $service->name.(!empty($service->port) ? ' :'.$service->port : ''), self::date($service->updated, 'Y.m.d H:i'));
		
		$msg = str_replace($tpl_search, $tpl_replace, $tpl->msg);
		self::mail($tpl->from_name, $tpl->from_email, $email, $tpl->subject, $msg);

		//-- Update number of sent emails on user plan
		++$plan->emails;

		$fields = array('emails' => $plan->emails);
		$sql = DB::build($fields, 'tbl_user_plans', 'update', "WHERE `id` = '".(int)$plan->id."'");
		DB::query($sql);

		//-- If the user now reached is email limit, send an email notifying him/her of such
		if ($plan->emails >= $plan->max_emails && $plan->max_emails > 0) {
			//-- Send Email
			$tpl = self::eTemplate('upgrade');
			$tpl_search = array('%%USER%%','%%URL%%');
			$tpl_replace = array($user->name, str_replace('http://','https://',self::$url));
			
			$msg = str_replace($tpl_search, $tpl_replace, $tpl->msg);
			self::mail($tpl->from_name, $tpl->from_email, $user->email, $tpl->subject, $msg);
		}
	}

	public static function sendRecoveryAlert($email, $serviceID) {
		$service = self::getService($serviceID);
		$server = self::getServer($service->server);
		$user = self::getUser($service->user);

		if (!$service || !$server || !$user) {
			return false;
		}

		//-- Send Email
		$tpl = self::eTemplate('recovery_alert');
		$tpl_search = array('%%USER%%', '%%SERVER%%', '%%IP%%', '%%SERVICE%%', '%%NOW%%');
		$tpl_replace = array($user->name, $server->name, $server->ip, $service->name.(!empty($service->port) ? ' :'.$service->port : ''), self::date($service->updated, 'Y.m.d H:i'));
		
		$msg = str_replace($tpl_search, $tpl_replace, $tpl->msg);
		self::mail($tpl->from_name, $tpl->from_email, $email, $tpl->subject, $msg);
	}

	public static function getUser($id) {
		$sql = "SELECT * FROM `wz_users` WHERE `id` = '".(int)$id."' AND `status` = 1";
		$result = DB::sexecute($sql);

		return $result;
	}

	public static function getUserPlan($user) {
		$sql = "SELECT * FROM `tbl_user_plans` WHERE `user` = '".(int)$user."' AND `status` = 1";
		$result = DB::sexecute($sql);

		return $result;
	}

	public static function getService($id) {
		$sql = "SELECT a.*, b.`name`, b.`port` FROM `tbl_user_services` a INNER JOIN `tbl_services` b ON (a.`service` = b.`id`) WHERE a.`id` = '".(int)$id."'";
		$result = DB::sexecute($sql);

		return $result;
	}

	public static function getServer($id) {
		$sql = "SELECT * FROM `tbl_user_servers` WHERE `id` = '".(int)$id."' AND `status` = 1";
		$result = DB::sexecute($sql);

		return $result;
	}

	public static function getPlan($id) {
		$sql = "SELECT * FROM `tbl_plans` WHERE `id` = '".(int)$id."' AND `status` = 1";
		$result = DB::sexecute($sql);

		return $result;
	}

	//-- From the framework, adapted
	public static function eTemplate($tplname, $lang = '') {
		$sql = "SELECT * FROM `wz_etemplates` WHERE `name` = '".DB::prepare($tplname)."' AND `status` = 1";
		$result = DB::sexecute($sql,89);

		return $result;
	}

	public static function mail($name, $email, $to_mail, $subject, $msg) {
		$sending = false;

		if (!empty($name) && !empty($email) && !empty($to_mail) && !empty($subject) && !empty($msg)) {
			$from_name = $name;
			$from_mail = $email;
			$sending = true;
		}

		if ($sending) {
			$eol = "\n";

			ob_start();
?>
#outlook a{padding:0;}
body{width:100% !important;}
.ReadMsgBody{width:100%;} .ExternalClass{width:100%;}
body{-webkit-text-size-adjust:none;}			

/* Reset Styles */
body{margin:0; padding:0;}
img{border:0; height:auto; line-height:100%; outline:none; text-decoration:none;}
table td{border-collapse:collapse;}
#backgroundTable{height:100% !important; margin:0; padding:0; width:100% !important;}

/* Template Styles */

body, #backgroundTable {
	background-color: #FFF;
}
#templateContainer {
	border: 1px solid #CCC;
}
h1 {
	color: #BF3360;
	display: block;
	font-family: arial;
	font-size: 28px;
	font-weight: bold;
	line-height: 125%;
	margin-top: 0;
	margin-right: 0;
	margin-bottom: 10px;
	margin-left: 0;
	text-align: left;
}
h2 {
	color: #333;
	display: block;
	font-family: arial;
	font-size: 24px;
	font-weight: bold;
	line-height: 125%;
	margin-top: 0;
	margin-right: 0;
	margin-bottom: 10px;
	margin-left: 0;
	text-align: left;
}
h3 {
	color: #090;
	display: block;
	font-family: arial;
	font-size: 20px;
	font-weight: bold;
	line-height: 125%;
	margin-top: 0;
	margin-right: 0;
	margin-bottom: 10px;
	margin-left: 0;
	text-align: left;
}
h4 {
	color: #333;
	display: block;
	font-family: arial;
	font-size: 18px;
	font-weight: bold;
	line-height: 125%;
	margin-top: 0;
	margin-right: 0;
	margin-bottom: 10px;
	margin-left: 0;
	text-align: left;
}
p {
	font-family: arial;
	font-size: 14px;
	line-height: 130%;
}
#browser-link {
	background-color: #FFF;
}
#browser-link div {
	color: #666;
	font-family: arial;
	font-size: 10px;
	line-height: 100%;
	text-align: center;
}
#browser-link div a {
	color: #090;
	font-weight: normal;
	text-decoration: none;
}
#header {
	background-color: #009b00;
	color: #202020;
	font-family: arial;
	font-size: 16px;
	font-weight: 400;
	line-height: 100%;
	padding: 0;
	text-align: center;
	vertical-align: middle;
}
#header a {
	color: #fff;
	font-weight: 400;
	text-decoration: none;
	text-shadow: 0 1px 1px rgba(0,0,0,.4);
}
#templateContainer, .bodyContent {
	 background-color: #FFFFFF;
}
.bodyContent div {
	color: #666;
	font-family: arial;
	font-size: 14px;
	line-height: 150%;
	text-align: left;
}
.bodyContent div a {
	color: #090;
	font-weight: normal;
	text-decoration: none;
}	
.bodyContent img {
	display: inline;
	height: auto;
	margin-bottom: 10px;
}
#footer {
	background-color: #ECECEC;
	border-top: 1px solid #CCC;
	color: #666;
	font-family: arial;
	font-size: 12px;
	line-height: 125%;
	padding-left: 5px;
	padding-right: 5px;
}
#footer img {
	display: inline;
}
#social span {
	margin-right: 6px;
}
#social span img {
	vertical-align: middle;
}
#copyright {
	font-size: 11px;
	color: #666;
	text-align: right;
}
#copyright a {
	font-weight:bold;
	color: #666;
	text-decoration: none;
}
<?php
			$theCSS = ob_get_clean();

			$emogrifier = new Emogrifier();

			Mandrill::setApiKey('MANDRILL-API-KEY');// Mandrill API Key

			$tosend['email'] = $to_mail;
			$tosend['subject'] = $subject;

			$tosend['message'] = '';
			ob_start();
?>

<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
	<center>
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="backgroundTable">
			<tr>
				<td align="center" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer">
						<tr>
							<td align="center" valign="top">
								<!-- Header -->
								<table border="0" cellpadding="2" cellspacing="0" width="600" id="header" background="<?php echo self::$url; ?>images/email/header-bg.png">
									<tr>
					<td><a href="<?php echo self::$url; ?>"><img src="<?php echo self::$url; ?>images/email/sitelog-logo.png" alt="SiteLog - Monitor your websites and servers the easy way" title="SiteLog - Monitor your websites and servers the easy way" width="85" height="26" /></a></td>
					<td style="padding-left:60px;"><a href="<?php echo self::$url; ?>tour/">Tour</a></td>
					<td><a href="<?php echo self::$url; ?>plans-and-pricing/">Plans &amp; Pricing</a></td>
					<td><a href="http://support.emotionloop.com/kb/sitelog">Support</a></td>
					<td style="padding-left: 30px; padding-right:20px;"><a href="<?php echo self::$url; ?>#!/login">Login</a></td>
									</tr>
								</table>
								<!-- / Header -->
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="templateBody">
									<tr>
										<td valign="top" class="bodyContent">
					<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top">
														<div>
															<?php echo $msg; ?>
														</div>
													</td>
												</tr>
											</table>												
										</td>
									</tr>
								</table>
								<!-- / Body -->
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Footer -->
								<table border="0" cellpadding="0" cellspacing="0" width="600" id="footer">
									<tr>
										<td valign="top">
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
												<tr>
													<td colspan="2" valign="middle" id="social">
							<span><a href="http://twitter.com/SiteLogHQ"><img src="<?php echo self::$url; ?>images/social/twitter.png" alt="Follow us on Twitter" title="Follow us on Twitter" width="32" height="32" /></a></span> 
							<span><a href="http://www.facebook.com/pages/SiteLog"><img src="<?php echo self::$url; ?>images/social/facebook.png" alt="Like us on Facebook" title="Like us on Facebook" width="32" height="32" /></a></span> 
													</td>
						<td>
							<div id="copyright">
								<a href="<?php echo self::$url; ?>">SiteLog</a> &copy; by <a href="http://emotionloop.com">emotionLoop</a>
							</div>
						</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<!-- / Footer -->
							</td>
						</tr>
					</table>
					<br />
				</td>
			</tr>
		</table>
	</center>
</body>
<?php
			$theHTML = ob_get_clean();

			$emogrifier->setHTML($theHTML);
			$emogrifier->setCSS($theCSS);

			$tosend['message'] = $emogrifier->emogrify();
			$tosend['headers'] = "From: \"".$from_name."\" <".$from_mail.">".$eol;
			$tosend['headers'] .= "Return-path: <".$from_mail.">".$eol;
			$tosend['headers'] .= "MIME-Version: 1.0".$eol;
			$tosend['headers'] .= "Content-type: text/html; charset=utf-8".$eol;

			/*if (mail($tosend['email'], $tosend['subject'],  $tosend['message'] , $tosend['headers'])) {
				return true;
			} else {
				return false;
			}*/

			$JSONMandrillVariable = new stdClass();
			$JSONMandrillVariable->type = 'messages';
			$JSONMandrillVariable->call = 'send';
			$JSONMandrillVariable->message = new stdClass();
			$JSONMandrillVariable->message->html = $tosend['message'];
			$JSONMandrillVariable->message->subject = $tosend['subject'];
			$JSONMandrillVariable->message->from_email = $from_mail;
			$JSONMandrillVariable->message->from_name = $from_name;
			$JSONMandrillVariable->message->to = array();
			$JSONMandrillVariable->message->to[0] = new stdClass();
			$JSONMandrillVariable->message->to[0]->email = $to_mail;
			$JSONMandrillVariable->message->to[0]->name = '';//-- TODO: Get name from email automagically
			$JSONMandrillVariable->message->track_opens = true;
			$JSONMandrillVariable->message->track_clicks = false;
			$JSONMandrillVariable->message->auto_text = true;
			$JSONMandrillVariable->message->tags = array('cron', 'amazon');

			$mandrillResponse = Mandrill::call((array) $JSONMandrillVariable);

			if (isset($mandrillResponse->status) && $mandrillResponse->status == 'sent') {
				return true;
			} else {
				return false;
			}
		}//-- if ($sending)
		return false;
	}

	public static function date($date, $format = 'd-m-Y', $dateformat = true) {
		if (intval($date) == 0) {
			return '';
		}
		while (strlen($date) < 14) {
			$date .= '0';
		}
		$second = (int) substr($date,12,2);
		$minute = (int) substr($date,10,2);
		$hour = (int) substr($date,8,2);
		$day = (int) substr($date,6,2);
		$month = (int) substr($date,4,2);
		$year = (int) substr($date,0,4);
		$stamp = mktime($hour,$minute,$second,$month,$day,$year);
		if ($dateformat) {
			$format = str_replace('F','%B',$format);
			$format = str_replace('M','%b',$format);
			$format = str_replace('d','%d',$format);
			$format = str_replace('m','%m',$format);
			$format = str_replace('Y','%Y',$format);
			$format = str_replace('H','%H',$format);
			$format = str_replace('i','%M',$format);
			$format = str_replace('s','%S',$format);
			$format = str_replace('l','%w',$format);
			$format = str_replace('y','%y',$format);
			return strftime($format,$stamp);
		} else {
			return date($format,$stamp);
		}
	}
}

//-- ServiceManagement Class
class ServiceManagement {
	protected static $id;

	public function __construct($id) {
		self::$id = (int)$id;
	}

	public static function update($fields) {
		$sql = DB::build($fields, 'tbl_user_services', 'update', "WHERE `id` = '".self::$id."'");
		DB::query($sql);
	}

	public static function logOffline($fields) {
		$sql = DB::build($fields, 'tbl_history_log', 'insert');
		DB::query($sql);
	}

	public static function logRecovery($fields) {
		$sql = DB::build($fields, 'tbl_history_log', 'update', "WHERE `user` = '".(int)$fields['user']."' AND `server` = '".(int)$fields['server']."' AND `service` = '".(int)$fields['service']."' AND `recovery` = ''");
		DB::query($sql);
	}

	public static function shouldAddToAmazonQueue($id) {
		$twoMinutesAgo = date('YmdHis',time() - 120);

		$sql = "SELECT `id` FROM `tbl_amazon_queue` WHERE `service` = '".(int)$id."' AND `date` <= '".DB::prepare($twoMinutesAgo)."'";
		DB::query($sql);

		if (DB::rows() > 0) {//-- If on Amazon Queue for 2 minutes or more, remove it from the queue and don't add it again
			self::removeFromAmazonQueue($id);
			return false;
		} else {
			return true;
		}
	}

	public static function addToAmazonQueue($id) {
		$fields = array(
			'service' => $id,
			'date' => date('YmdHis')
		);
		$sql = DB::build($fields, 'tbl_amazon_queue', 'insert');
		DB::query($sql);
	}

	public static function removeFromAmazonQueue($id) {
		$sql = "DELETE FROM `tbl_amazon_queue` WHERE `service` = '".(int)$id."'";
		DB::query($sql);
	}
}

//-- ServiceCheck Class
class ServiceCheck {
	public function __construct() {
	}

	public static function doCheck($host, $port) {
		if (empty($port)) {
			return self::ping($host);
		} else {
			return self::socket($host, $port);
		}
	}

	public static function ping($host, $pings = 1) {
		$return = new stdClass();
		$return->status = false;
		$return->msg = '';

		exec(sprintf('ping -c %d -w 1 %s', $pings, escapeshellarg($host)), $result);

		if (!empty($result)) {
			$ip = explode('(',$result[0]);
			$ip = explode(')',$ip[1]);
			$ip = $ip[0];

			if (empty($result[1])) {
				$return->msg = 'ERROR ('.$host.') | Not responding.';
			} else {
				//-- Get ping times
				$times = array();
				for ($i = 1 ; $i < count($result) ; $i++) {
					if (empty($result[$i])) { break; }
					$time = explode(' time=',$result[$i]);
					$times[] = substr($time[1],0,-2);
				}

				//-- Verify sent & received packets
				$packets = explode(', ',$result[($i+2)]);
				$packets = array(
					'transmited' => substr($packets[0],0,-20),
					'received' => substr($packets[1],0,-9)
				);

				if ($packets['received'] > 0) {
					//-- Calculate average time
					$time = 0;
					foreach ($times as $t) { $time += $t; }
					$time = round($time / count($times),3);

					$return->status = true;
					$return->msg = 'OK ('.$host.'['.$ip.']) | Avg. Ping time = '.$time.' ms.';
				} else {
					$return->msg = 'ERROR ('.$host.'['.$ip.']) | Timeout.';
				}
			}
		} else {
			$return->msg = 'ERROR ('.$host.') | No host found.';
		}
		return $return;
	}

	public static function socket($host, $port, $timeout = 10) {
		$return = new stdClass();
		$return->status = false;
		$return->msg = '';

		$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

		if (!$fp) {
			$return->msg = 'ERROR ('.$host.':'.$port.') | '.$errno.' :: '.$errstr;
		} else {
			$return->status = true;
			$return->msg = 'OK ('.$host.':'.$port.')';
			fclose($fp);
		}
		return $return;
	}
}

//-- Database Class (from the Framework, but adapted)
class DB {
	protected static $host;
	protected static $user;
	protected static $pass;
	protected static $database;
	protected static $db;
	protected static $query;
	
	public function __construct($dbInfo) {
		self::$host = $dbInfo['host'];
		self::$database = $dbInfo['db'];
		self::$user = $dbInfo['user'];
		self::$pass = $dbInfo['pwd'];
		self::$query = array();
		self::start();
	}
	
	protected static function start() {
		self::$db = @mysql_connect(self::$host, self::$user, self::$pass, true) OR die('The website is temporarily unavailable (E#001).');
		@mysql_select_db(self::$database, self::$db) OR die('The website is temporarily unavailable (E#002).');
		$sql = "SET NAMES 'utf8'";
		self::query($sql);
	}
	
	public static function query($sql,$i=0) {
		if (self::$query[$i] = mysql_query($sql,self::$db)) {
			return true;
		} else {
			echo mysql_error(self::$db)."\n\n".$sql;
		}
		return false;
	}
	
	public static function queryid($sql,$i=0) {
		$result = 0;
		if (self::query($sql,$i)) {
			$result = self::lastid();
			return $result;
		} else {
			echo mysql_error(self::$db)."\n\n".$sql;
		}
		return $result;
	}
	
	public static function fetch($i=0) {
		if (self::$query[$i]) {
			if ($result = mysql_fetch_object(self::$query[$i])) {
				return $result;
			} else {
				return false;
			}
		}
		return false;
	}
	
	public static function rows($i=0) {
		$rows = mysql_num_rows(self::$query[$i]);
		return $rows;
	}
	
	protected static function fetch_array($i=0) {
		$results = false;
		if (self::$query[$i]) {
			while($result = mysql_fetch_object(self::$query[$i])) $results[] = $result;
		}
		return $results;
	}
	
	public static function execute($sql,$i=0) {
		$results = false;
		self::query($sql,$i);
		$results = self::fetch_array($i);
		return $results;
	}
	
	public static function sexecute($sql,$i=0) {
		$result = false;
		self::query($sql,$i);
		if (self::$query[$i]) {
			$result = self::fetch($i);
		}
		return $result;
	}
	
	public static function get($sql,$i=0) {
		$result = false;
		$result = self::sexecute($sql,$i);
		if ($result) {
			$vars = get_object_vars($result);
			foreach ($vars as $var) {
				return $var;
			}
		}
		return false;
	}
	
	public static function build($array, $table, $action = 'insert', $extra = '') {
		$sql = "";
		switch ($action) {
			case 'insert' : {
				$sql = "INSERT INTO `".$table."` (";
				$fields = "";
				foreach($array as $name=>$value) {
					$fields .= ",`".$name."`";
				}
				$fields = substr($fields,1);
				$sql .= $fields.") VALUES (";
				$fields = "";
				foreach($array as $name=>$value) {
					$fields .= ",'".self::prepare($value)."'";
				}
				$fields = substr($fields,1);
				$sql .= $fields.") ".$extra.";";
			}break;
			case 'update' : {
				$sql = "UPDATE `".$table."` SET ";
				$fields = "";
				foreach($array as $name=>$value) {
					$fields .= ",`".$name."` = '".self::prepare($value)."'";
				}
				$fields = substr($fields,1);
				$sql .= $fields." ".$extra.";";
			}break;
			case 'select' : {
				$sql = "SELECT `id`";
				$fields = "";
				foreach($array as $name=>$value) {
					$fields .= ",`".$name."`";
				}
				$sql .= $fields." FROM `".$table."` ".$extra.";";
			}break;
		}
		return $sql;
	}

	public static function nextid($table = '',$i=0) {
		$sql = "SHOW TABLE STATUS LIKE '".self::prepare($table)."'";
		$result = self::sexecute($sql,$i);
		$return = $result->Auto_increment;
		return $return;
	}
	
	public static function lastid() {
		return mysql_insert_id(self::$db);
	}
	
	public static function prepare($string) {
		$string = stripslashes($string);
		return mysql_real_escape_string($string,self::$db);
	}
	
	public static function end() {
		@mysql_close(self::$db);
	}
}

/* Last Update: 2008-08-10 */

define('CACHE_CSS', 0);
define('CACHE_SELECTOR', 1);
define('CACHE_XPATH', 2);

class Emogrifier {
	// for calculating nth-of-type and nth-child selectors
	const INDEX = 0;
	const MULTIPLIER = 1;

	private $html = '';
	private $css = '';
	private $unprocessableHTMLTags = array('wbr');
	private $caches = array();

	// this attribute applies to the case where you want to preserve your original text encoding.
	// by default, emogrifier translates your text into HTML entities for two reasons:
	// 1. because of client incompatibilities, it is better practice to send out HTML entities rather than unicode over email
	// 2. it translates any illegal XML characters that DOMDocument cannot work with
	// if you would like to preserve your original encoding, set this attribute to true.
	public $preserveEncoding = false;

	public function __construct($html = '', $css = '') {
		$this->html = $html;
		$this->css  = $css;
		$this->clearCache();
	}

	public function setHTML($html = '') { $this->html = $html; }
	public function setCSS($css = '') {
		$this->css = $css;
		$this->clearCache(CACHE_CSS);
	}

	public function clearCache($key = null) {
		if (!is_null($key)) {
			if (isset($this->caches[$key])) $this->caches[$key] = array();
		} else {
			$this->caches = array(
				CACHE_CSS	   => array(),
				CACHE_SELECTOR  => array(),
				CACHE_XPATH	 => array(),
			);
		}
	}

	// there are some HTML tags that DOMDocument cannot process, and will throw an error if it encounters them.
	// in particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
	// these functions allow you to add/remove them if necessary.
	// it only strips them from the code (does not remove actual nodes).
	public function addUnprocessableHTMLTag($tag) { $this->unprocessableHTMLTags[] = $tag; }
	public function removeUnprocessableHTMLTag($tag) {
		if (($key = array_search($tag,$this->unprocessableHTMLTags)) !== false)
			unset($this->unprocessableHTMLTags[$key]);
	}

	// applies the CSS you submit to the html you submit. places the css inline
	public function emogrify() {
		$body = $this->html;

		// remove any unprocessable HTML tags (tags that DOMDocument cannot parse; this includes wbr and many new HTML5 tags)
		if (count($this->unprocessableHTMLTags)) {
			$unprocessableHTMLTags = implode('|',$this->unprocessableHTMLTags);
			$body = preg_replace("/<\/?($unprocessableHTMLTags)[^>]*>/i",'',$body);
		}

		$encoding = mb_detect_encoding($body);
		$body = mb_convert_encoding($body, 'HTML-ENTITIES', $encoding);

		$xmldoc = new DOMDocument;
		$xmldoc->encoding = $encoding;
		$xmldoc->strictErrorChecking = false;
		$xmldoc->formatOutput = true;
		$xmldoc->loadHTML($body);
		$xmldoc->normalizeDocument();

		$xpath = new DOMXPath($xmldoc);

		// before be begin processing the CSS file, parse the document and normalize all existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
		// we wouldn't have to do this if DOMXPath supported XPath 2.0.
		// also store a reference of nodes with existing inline styles so we don't overwrite them
		$vistedNodes = $vistedNodeRef = array();
		$nodes = @$xpath->query('//*[@style]');
		foreach ($nodes as $node) {
			$normalizedOrigStyle = preg_replace('/[A-z\-]+(?=\:)/Se',"strtolower('\\0')", $node->getAttribute('style'));

			// in order to not overwrite existing style attributes in the HTML, we have to save the original HTML styles
			$nodeKey = md5($node->getNodePath());
			if (!isset($vistedNodeRef[$nodeKey])) {
				$vistedNodeRef[$nodeKey] = $this->cssStyleDefinitionToArray($normalizedOrigStyle);
				$vistedNodes[$nodeKey]   = $node;
			}

			$node->setAttribute('style', $normalizedOrigStyle);
		}

		// grab any existing style blocks from the html and append them to the existing CSS
		// (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
		$css = $this->css;
		$nodes = @$xpath->query('//style');
		foreach ($nodes as $node) {
			// append the css
			$css .= "\n\n{$node->nodeValue}";
			// remove the <style> node
			$node->parentNode->removeChild($node);
		}

		// filter the CSS
		$search = array(
			'/\/\*.*\*\//sU', // get rid of css comment code
			'/^\s*@import\s[^;]+;/misU', // strip out any import directives
			'/^\s*@media\s[^{]+{\s*}/misU', // strip any empty media enclosures
			'/^\s*@media\s+((aural|braille|embossed|handheld|print|projection|speech|tty|tv)\s*,*\s*)+{.*}\s*}/misU', // strip out all media types that are not 'screen' or 'all' (these don't apply to email)
			'/^\s*@media\s[^{]+{(.*})\s*}/misU', // get rid of remaining media type enclosures
		);

		$replace = array(
			'',
			'',
			'',
			'',
			'\\1',
		);

		$css = preg_replace($search, $replace, $css);

		$csskey = md5($css);
		if (!isset($this->caches[CACHE_CSS][$csskey])) {

			// process the CSS file for selectors and definitions
			preg_match_all('/(^|[^{}])\s*([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);

			$all_selectors = array();
			foreach ($matches as $key => $selectorString) {
				// if there is a blank definition, skip
				if (!strlen(trim($selectorString[3]))) continue;

				// else split by commas and duplicate attributes so we can sort by selector precedence
				$selectors = explode(',',$selectorString[2]);
				foreach ($selectors as $selector) {

					// don't process pseudo-elements and behavioral (dynamic) pseudo-classes; ONLY allow structural pseudo-classes
					if (strpos($selector, ':') !== false && !preg_match('/:\S+\-(child|type)\(/i', $selector)) continue;

					$all_selectors[] = array('selector' => trim($selector),
											 'attributes' => trim($selectorString[3]),
											 'line' => $key, // keep track of where it appears in the file, since order is important
					);
				}
			}

			// now sort the selectors by precedence
			usort($all_selectors, array($this,'sortBySelectorPrecedence'));

			$this->caches[CACHE_CSS][$csskey] = $all_selectors;
		}

		foreach ($this->caches[CACHE_CSS][$csskey] as $value) {

			// query the body for the xpath selector
			$nodes = $xpath->query($this->translateCSStoXpath(trim($value['selector'])));

			foreach($nodes as $node) {
				// if it has a style attribute, get it, process it, and append (overwrite) new stuff
				if ($node->hasAttribute('style')) {
					// break it up into an associative array
					$oldStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));
					$newStyleArr = $this->cssStyleDefinitionToArray($value['attributes']);

					// new styles overwrite the old styles (not technically accurate, but close enough)
					$combinedArr = array_merge($oldStyleArr,$newStyleArr);
					$style = '';
					foreach ($combinedArr as $k => $v) $style .= (strtolower($k) . ':' . $v . ';');
				} else {
					// otherwise create a new style
					$style = trim($value['attributes']);
				}
				$node->setAttribute('style', $style);
			}
		}

		// now iterate through the nodes that contained inline styles in the original HTML
		foreach ($vistedNodeRef as $nodeKey => $origStyleArr) {
			$node = $vistedNodes[$nodeKey];
			$currStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));

			$combinedArr = array_merge($currStyleArr, $origStyleArr);
			$style = '';
			foreach ($combinedArr as $k => $v) $style .= (strtolower($k) . ':' . $v . ';');

			$node->setAttribute('style', $style);
		}

		// This removes styles from your email that contain display:none.
		// We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only supports XPath 1.0,
		// lower-case() isn't available to us. We've thus far only set attributes to lowercase, not attribute values. Consequently, we need
		// to translate() the letters that would be in 'NONE' ("NOE") to lowercase.
		$nodes = $xpath->query('//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]');
		// The checks on parentNode and is_callable below ensure that if we've deleted the parent node,
		// we don't try to call removeChild on a nonexistent child node
		if ($nodes->length > 0)
			foreach ($nodes as $node)
				if ($node->parentNode && is_callable(array($node->parentNode,'removeChild')))
						$node->parentNode->removeChild($node);

		if ($this->preserveEncoding) {
			return mb_convert_encoding($xmldoc->saveHTML(), $encoding, 'HTML-ENTITIES');
		} else {
			return $xmldoc->saveHTML();
		}
	}

	private function sortBySelectorPrecedence($a, $b) {
		$precedenceA = $this->getCSSSelectorPrecedence($a['selector']);
		$precedenceB = $this->getCSSSelectorPrecedence($b['selector']);

		// we want these sorted ascendingly so selectors with lesser precedence get processed first and
		// selectors with greater precedence get sorted last
		return ($precedenceA == $precedenceB) ? ($a['line'] < $b['line'] ? -1 : 1) : ($precedenceA < $precedenceB ? -1 : 1);
	}

	private function getCSSSelectorPrecedence($selector) {
		$selectorkey = md5($selector);
		if (!isset($this->caches[CACHE_SELECTOR][$selectorkey])) {
			$precedence = 0;
			$value = 100;
			$search = array('\#','\.',''); // ids: worth 100, classes: worth 10, elements: worth 1

			foreach ($search as $s) {
				if (trim($selector == '')) break;
				$num = 0;
				$selector = preg_replace('/'.$s.'\w+/','',$selector,-1,$num);
				$precedence += ($value * $num);
				$value /= 10;
			}
			$this->caches[CACHE_SELECTOR][$selectorkey] = $precedence;
		}

		return $this->caches[CACHE_SELECTOR][$selectorkey];
	}

	// right now we support all CSS 1 selectors and most CSS2/3 selectors.
	// http://plasmasturm.org/log/444/
	private function translateCSStoXpath($css_selector) {

		$css_selector = trim($css_selector);
		$xpathkey = md5($css_selector);
		if (!isset($this->caches[CACHE_XPATH][$xpathkey])) {
			// returns an Xpath selector
			$search = array(
							   '/\s+>\s+/', // Matches any element that is a child of parent.
							   '/\s+\+\s+/', // Matches any element that is an adjacent sibling.
							   '/\s+/', // Matches any element that is a descendant of an parent element element.
							   '/([^\/]+):first-child/i', // first-child pseudo-selector
							   '/([^\/]+):last-child/i', // last-child pseudo-selector
							   '/(\w)\[(\w+)\]/', // Matches element with attribute
							   '/(\w)\[(\w+)\=[\'"]?(\w+)[\'"]?\]/', // Matches element with EXACT attribute
							   '/(\w+)?\#([\w\-]+)/e', // Matches id attributes
							   '/(\w+|[\*\]])?((\.[\w\-]+)+)/e', // Matches class attributes

			);
			$replace = array(
							   '/',
							   '/following-sibling::*[1]/self::',
							   '//',
							   '*[1]/self::\\1',
							   '*[last()]/self::\\1',
							   '\\1[@\\2]',
							   '\\1[@\\2="\\3"]',
							   "(strlen('\\1') ? '\\1' : '*').'[@id=\"\\2\"]'",
							   "(strlen('\\1') ? '\\1' : '*').'[contains(concat(\" \",@class,\" \"),concat(\" \",\"'.implode('\",\" \"))][contains(concat(\" \",@class,\" \"),concat(\" \",\"',explode('.',substr('\\2',1))).'\",\" \"))]'",
			);

			$css_selector = '//'.preg_replace($search, $replace, $css_selector);

			// advanced selectors are going to require a bit more advanced emogrification
			// if we required PHP 5.3 we could do this with closures
			$css_selector = preg_replace_callback('/([^\/]+):nth-child\(\s*(odd|even|[+\-]?\d|[+\-]?\d?n(\s*[+\-]\s*\d)?)\s*\)/i', array($this, 'translateNthChild'), $css_selector);
			$css_selector = preg_replace_callback('/([^\/]+):nth-of-type\(\s*(odd|even|[+\-]?\d|[+\-]?\d?n(\s*[+\-]\s*\d)?)\s*\)/i', array($this, 'translateNthOfType'), $css_selector);

			$this->caches[CACHE_SELECTOR][$xpathkey] = $css_selector;
		}
		return $this->caches[CACHE_SELECTOR][$xpathkey];
	}

	private function translateNthChild($match) {

		$result = $this->parseNth($match);

		if (isset($result[self::MULTIPLIER])) {
			if ($result[self::MULTIPLIER] < 0) {
				$result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
				return sprintf("*[(last() - position()) mod %u = %u]/self::%s", $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
			} else {
				return sprintf("*[position() mod %u = %u]/self::%s", $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
			}
		} else {
			return sprintf("*[%u]/self::%s", $result[self::INDEX], $match[1]);
		}
	}

	private function translateNthOfType($match) {

		$result = $this->parseNth($match);

		if (isset($result[self::MULTIPLIER])) {
			if ($result[self::MULTIPLIER] < 0) {
				$result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
				return sprintf("%s[(last() - position()) mod %u = %u]", $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
			} else {
				return sprintf("%s[position() mod %u = %u]", $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
			}
		} else {
			return sprintf("%s[%u]", $match[1], $result[self::INDEX]);
		}
	}

	private function parseNth($match) {

		if (in_array(strtolower($match[2]), array('even','odd'))) {
			$index = strtolower($match[2]) == 'even' ? 0 : 1;
			return array(self::MULTIPLIER => 2, self::INDEX => $index);
		// if there is a multiplier
		} else if (stripos($match[2], 'n') === false) {
			$index = intval(str_replace(' ', '', $match[2]));
			return array(self::INDEX => $index);
		} else {

			if (isset($match[3])) {
				$multiple_term = str_replace($match[3], '', $match[2]);
				$index = intval(str_replace(' ', '', $match[3]));
			} else {
				$multiple_term = $match[2];
				$index = 0;
			}

			$multiplier = str_ireplace('n', '', $multiple_term);

			if (!strlen($multiplier)) $multiplier = 1;
			elseif ($multiplier == 0) return array(self::INDEX => $index);
			else $multiplier = intval($multiplier);

			while ($index < 0) $index += abs($multiplier);

			return array(self::MULTIPLIER => $multiplier, self::INDEX => $index);
		}
	}

	private function cssStyleDefinitionToArray($style) {
		$definitions = explode(';',$style);
		$retArr = array();
		foreach ($definitions as $def) {
			if (empty($def) || strpos($def, ':') === false) continue;
			list($key,$value) = explode(':',$def,2);
			if (empty($key) || strlen(trim($value)) === 0) continue;
			$retArr[trim($key)] = trim($value);
		}
		return $retArr;
	}
}

/**
  * This class allows the user to easily consume MailChimp's Mandrill services
  * @package Mandrill
  *
  * @author  Wes Widner <wes@werxltd.com>
  *
  * @version 1.0
  *
  * @method string getVersion() Retrieve the Mandrill PHP library's current version
  * @method string getApiKey() Retrieve the API key that is currently set
  * @method mixed call() call(mixed $data) Call Mandril service using an associative array containing the parameters Mandrill for the given type of service and specific call
  * @link http://mandrillapp.com/api/docs/index.html Official documentation for Mandrill API call types and calls
  * @link ../../examples/user_info.php Example: Calling User/Info per http://mandrillapp.com/api/docs/users.html#method=info
  *
  */
abstract class Mandrill {
	/**
	 * Stores the operating enviroment state. Null means the state has not been evaluated yet.
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $is_cli		= null;

	/**
	 * The current Mandrill PHP lib version
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $version		= '1.0';

	/**
	 * The Mandrill service URL
	 * @since 1.0
	 * @static
	 * @ignore
	 */	
	private static $api_url		= 'https://mandrillapp.com/api/1.0/%s/%s.json';
	
	/**
	 * The user's API key
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $api_key		= null;
	
	/**
	 * Holds known Mandrill API call array. Used to validate user requests
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $api_calls	= null;
	
	/**
	 * Whether or not to send additional information to error_log
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $verbose		= false;
	
	/**
	 * Shorthand for the key property which is required for all Mandrill requests
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $required_key	= array('key');
	
	/**
	 * Stores last validation error message
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static $last_error	= null;
	
	/**
	 * Returns true if the class is being run from the command line, caches result
	 * @since 1.0
	 * @static
	 * @ignore
	 * @return bool True if the script is being run from a CLI or false if its being run from a webserver
	 */
	private static function _is_cli() {
		if(is_null(self::$is_cli)) self::$is_cli = php_sapi_name() == 'cli';
		return self::$is_cli;
	}
	
	/**
	 * Generates a structured array of valid Mandrill API calls
	 * @since 1.0
	 * @static
	 * @ignore
	 * @returns mixed Associative array of valid calls and their required parameters
	 */
	private static function api_calls() {
		if(is_null(self::$api_calls)) self::$api_calls = array(
			/* Users Calls */
			'users'=>array(
				'info'			 => self::$required_key,
				'ping'			 => self::$required_key,
				'senders'		  => self::$required_key,
				'disable-sender'   => array_merge_recursive(self::$required_key, array('domain')),
				'verify-sender'	=> array_merge_recursive(self::$required_key, array('email'))
			),
			
			/* Messages Calls */
			'messages'=>array(
				'send'			 => array_merge_recursive(self::$required_key, array('message')),
				'send-template'	=> array_merge_recursive(self::$required_key, array('template_name','template_content','message')),
				'search'		   => array_merge_recursive(self::$required_key, array('query','date_from','date_to','tags','senders','limit'))
			),
			
			/* Tags Calls */
			'tags'=>array(
				'list'			 => self::$required_key,
				'info'			 => array_merge_recursive(self::$required_key, array('tag')),
				'time-series'	  => array_merge_recursive(self::$required_key, array('tag')),
				'all-time-series'  => self::$required_key
			),
			
			/* Senders Calls */
			'senders'=>array(
				'list'			 => self::$required_key,
				'info'			 => array_merge_recursive(self::$required_key, array('address')),
				'time-series'	  => array_merge_recursive(self::$required_key, array('address'))
			),
			
			/* Urls Calls */
			'urls'=>array(
				'list'			 => self::$required_key,
				'search'		   => array_merge_recursive(self::$required_key, array('q')),
				'time-series'	  => array_merge_recursive(self::$required_key, array('url'))
			),
			
			/* Templates Calls */
			'templates'=>array(
				'add'			  => array_merge_recursive(self::$required_key, array('name','code')),
				'info'			 => array_merge_recursive(self::$required_key, array('name')),
				'update'		   => array_merge_recursive(self::$required_key, array('name','code')),
				'delete'		   => array_merge_recursive(self::$required_key, array('name')),
				'list'			 => self::$required_key
			),
			
			/* Webhooks Calls */
			'webhooks'=>array(
				'list'			 => self::$required_key,
				'add'			  => array_merge_recursive(self::$required_key, array('url','events')),
				'info'			 => array_merge_recursive(self::$required_key, array('id')),
				'update'		   => array_merge_recursive(self::$required_key, array('id','url','events')),
				'delete'		   => array_merge_recursive(self::$required_key, array('id'))
			)
		);
		
		return self::$api_calls;
	}
	
	/**
	 * Validates the user's parameters against known valid Mandrill API calls
	 * @param string $call_type The type of Mandrill call to make, ex. 'users' or 'tags'
	 * @param string $call The call to make, ex. 'ping' or 'info'
	 * @param mixed $data An associative array of options that correspond with the Mandrill API call being made
	 * @return bool True or false for successful validation
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static function _validate_call(&$call_type,&$call,&$data) {
		$api_calls = self::api_calls();

		if(!array_key_exists($call_type,$api_calls)) throw new Exception('Invalid call type.');
		if(!array_key_exists($call, $api_calls[$call_type])) throw new Exception("Invalid call for call type $call_type");
		
		$diff_keys = array_diff(array_keys($data),$api_calls[$call_type][$call]);
		
		if(self::$verbose) error_log('MANDRILL: Invalid keys in call: '.implode(',',$diff_keys));
		if(count($diff_keys) > 0) throw new Exception('Invalid keys in call: '.implode(',',$diff_keys));
		
		// @todo actually validate the fields
		
		return true;
	}
	
	/**
	 * Set the api_key. The Mandrill API key can be set in a number of ways. 
	 ** It can be set by the parameters passed in for an API call, ie. Mandrill::call(array('key'=>'mykey')); 
	 ** It can be set via Mandrill::setApiKey('mykey'); 
	 ** It can be set directly in this class file
	 ** It can be set by the MANDRILL_API_KEY constant
	 * 
	 * @param mixed|string $data Associative array containing a 'key' element or a the API key as a string
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static function _set_api_key(&$data) {
		if(array_key_exists('key',$data)) self::$api_key = $data['key'];
		if((count($data) == 0 || is_null(self::$api_key))&& defined('MANDRILL_API_KEY')) self::$api_key = MANDRILL_API_KEY;
		
		if(!isset(self::$api_key)) throw new Exception('API Key must be set.');
	}
	
	/**
	 * The main method which makes the curl request to the Mandrill API
	 * @param mixed $data An associative array of options that correspond with the Mandrill API call being made
	 * @return mixed The response from the server.
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	private static function _call_api(&$data) {
		if(!array_key_exists('type',$data)) throw new Exception('API call type must be set.');
		if(!array_key_exists('call',$data)) throw new Exception('API call must be set.');
		
		self::_set_api_key($data);
		
		$call_type = $data['type'];
		$call = $data['call'];
		
		unset($data['type']);
		unset($data['call']);
		
		if(!self::_validate_call($call_type, $call, $data)) throw new Exception(self::$last_error);

		$data['key'] = self::$api_key;
		
		$data_string = json_encode($data);
		
		$parsed_url = sprintf(self::$api_url, $call_type, $call);
		
		if(self::$verbose) error_log("MANDRILL: Sending request to: $parsed_url with data: $data_string");
		if(self::_is_cli()) echo "MANDRILL: Sending request to: $parsed_url with data: $data_string".PHP_EOL;
		
		$ch = curl_init($parsed_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");																	 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);																  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);																	  
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(																		  
			'Content-Type: application/json',																				
			'Content-Length: ' . strlen($data_string))																	   
		);																											   
		 
		$result = curl_exec($ch);
		
		if(self::_is_cli()) echo "Mandrill API result: $result".PHP_EOL;
		
		if($call != 'ping') $result = json_decode($result);
		
		// @todo: Check result and throw exception?
		
		return $result;
	}
	
	/**
	 * Rather than defining each method individually we use this method to route the
	 * method call to the appropriate handler. This method should not be used directly.
	 * 
	 * @param string $method Method user attempted to use
	 * @param mixed $args Array of arguments the user passed to the method
	 * @since 1.0
	 * @static
	 * @ignore
	 */
	public static function __callStatic($method, $args) {
		switch($method) {
			case 'getApiCalls':
				return self::api_calls();;
			break;
			case 'setVerbose':
				if(count($args) < 1) self::$verbose = false;
				else self::$verbose = (bool) $args[0];
			break;
			case 'toggleVerbose':
				self::$verbose = !self::$verbose;
			break;
			case 'version':
			case 'getVersion':
				return self::$version;
			break;
			case 'getKey':
			case 'getApiKey':
				return self::$api_key;
			break;
			case 'setKey':
			case 'setApiKey':
				if(count($args) < 1) self::_set_api_key($args);
				if(is_string($args[0])) self::$api_key = $args[0];
				elseif(is_array($args[0])) self::_set_api_key($args[0]);
				else return false;
				return true;
			break;
			case 'getLastError':
				return self::$last_error;
			break;
			case 'call':
				if(count($args) != 1 || !is_array($args[0])) throw new Exception('Must pass one associative array with proper values set.');
				$args = $args[0];
				return self::_call_api($args);
			break;
		}
	}
}
?>