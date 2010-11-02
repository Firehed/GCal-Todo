<?
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

?>
<!doctype HTML>
<html>
<head>
	<meta name="viewport" content="width=320" />
</head>
<body>
<style>
:required { background: yellow; }
:invalid { background: red; }
</style>
<form method="post" action="?">
	<label>Title: <input type="text" name="title" required autofocus /></label><br />
	<button type="submit">Create Event</button>
</form>
</body>
</html>
<?
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
	echo 'Reminder created!';
}

