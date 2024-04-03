
<?php
define('PATH', "../");
include_once PATH."_init.php";
// Google Calendar API 라이브러리 로드
require '/home/isoi/sdk/google/vendor/autoload.php';
// 이벤트 정보 가져오기
$db = new db();
$db->setDb("rudy");
$cmd = $_POST['cmd'];
$year = $db->tos($_POST['year']);
$month = $db->tos($_POST['month']);
$channel_code = $db->tos($_POST['channel_code']);
$sql ="";
$title = $_POST['title'];
$startDate = (new DateTime($_POST['startDate']))->format(DateTime::RFC3339);
$endDate = (new DateTime($_POST['endDate']))->format(DateTime::RFC3339);
$description = $_POST['description'];

$auth_json_path = "../key/isoi_google_api_key.json";


// Google 클라이언트 생성 및 인증 설정
$client = new Google_Client();
$client->setAccessType('offline');
$client->setAuthConfig("../key/isoi_google_api_key.json");
$client->setApplicationName('My Calendar');
$client->setScopes(Google\Service\Calendar::CALENDAR);

$service = new Google\Service\Calendar($client);


if($cmd == "saveEvent"){
  $title = $db->tos($_POST['title']);
  $summary = $db->tos($_POST['summary']);
// 이벤트 생성
$event = new Google_Service_Calendar_Event(array(
  'summary' => $title,
  'description' => $description,
  'start' => array(
    'dateTime' => $startDate,
    'timeZone' => 'Asia/Seoul', // 적절한 시간대로 설정하세요.
  ),
  'end' => array(
    'dateTime' => $endDate,
    'timeZone' => 'Asia/Seoul',
  ),
));
// 사용자의 구글 캘린더에 이벤트 추가
$calendarId = 'primary'; // 사용자의 기본 캘린더
$event = $service->events->insert('45f03c6d209fdad24d6f9ad064fd339f2b626e6e28c3f0ae580374f772700840@group.calendar.google.com', $event);
if ($event) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('success' => false, 'error' => 'Failed to add event'));
}}
?>
