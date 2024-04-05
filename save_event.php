
<?php
define('PATH', "../");
include_once PATH."_init.php";
// Google Calendar API 라이브러리 로드
require '/home/isoi/sdk/google/vendor/autoload.php';
// 이벤트 정보 가져오기
$db = new db();
$db->setDb("rudy");
$cmd = $_POST['cmd'];
$title = $db->tos($_POST['title']);
$year = $db->tos($_POST['year']);
$month = $db->tos($_POST['month']);
$channel_code = $db->tos($_POST['channel_code']);
$sql ="";

$startDate = (new DateTime($_POST['startDate']))->format(DateTime::RFC3339);
$endDate = (new DateTime($_POST['endDate']))->format(DateTime::RFC3339);
$description = $_POST['description'];
$calendarId = $_POST['calendarId'];
$auth_json_path = "../key/isoi_google_api_key.json";


// Google 클라이언트 생성 및 인증 설정
$client = new Google_Client();
$client->setAccessType('offline');
$client->setAuthConfig("../key/isoi_google_api_key.json");
$client->setApplicationName('My Calendar');
$client->setScopes(Google\Service\Calendar::CALENDAR);

$service = new Google\Service\Calendar($client);


if($cmd == "saveEvent"){
  $event = new Google_Service_Calendar_Event(array(
    'summary' => $title,
    'description' => $description,
    'start' => array(
      'dateTime' => $startDate,
      'timeZone' => 'Asia/Seoul',
    ),
    'end' => array(
      'dateTime' => $endDate,
      'timeZone' => 'Asia/Seoul',
    ),
));
// 사용자의 구글 캘린더에 이벤트 추가

$event = $service->events->insert($calendarId, $event);
if ($event) {
    echo json_encode(array('success' => true, 'result' => $event));
} else {
    echo json_encode(array('success' => false, 'error' => 'Failed to add event'));
}}
?>
