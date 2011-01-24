<?
session_set_cookie_params(60*60*24*365.25);// 1 year
session_start();
error_reporting(0);
ini_set('display_errors',0);
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Uri_Http');
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Calendar');
function getAuthSubUrl() {
	$next = 'http://gcaltodo.com';
	$scope = 'http://www.google.com/calendar/feeds/'; // Google's docs say https, their zend client uses http. The bugger.
	$secure = false;
	$session = true;
	return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure, $session);
}
if (!isset($_SESSION['token'])) {
	if (isset($_GET['token'])) $_SESSION['token'] = Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
	else header('Location: ' . getAuthSubUrl());
}
$note = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['token']);
	$gdataCal = new Zend_Gdata_Calendar($client);
	$event = $gdataCal->newEventEntry();
	$event->content = $gdataCal->newContent($_POST['title']);
	$event->quickAdd = $gdataCal->newQuickAdd('true');
	$newEvent = $gdataCal->insertEvent($event);

	$reminder = $gdataCal->newReminder();
	$reminder->minutes = 1;
	$reminder->method = 'alert';

	foreach ($newEvent->when as $when) {
		$when->reminders = array($reminder);
		$when->endTime = date('c', strtotime($when->startTime) + 60); // 60 seconds long
	}
	$newEvent->save();
	$note = 'Reminder created!';
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>GCal-Todo!</title>
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<style>body{background:white;color:black;line-height:1;margin:0;padding:0;}textarea,button,p{display:block;margin: 10px auto 0;}textarea{border:1px solid #000;height:120px;width:90%;}button{background:#90EE90;border:2px outset #060;color:#040;font-size:1.4em;padding:5px;}</style>
</head>
<body>
	<?=$note?>
	<form method="post" action="?">
		<textarea name="title" required autofocus placeholder="Event info"></textarea>
		<button type="submit">Create Event</button>
	</form>
</body>
</html>

