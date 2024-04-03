<?php
define('PATH', "../");
include_once PATH."_init.php";

// Google Calendar API 라이브러리 로드
require '/home/isoi/sdk/google/vendor/autoload.php';

// 이벤트 정보 가져오기
$db = new db();
$db->setDb("rudy");

$eventId = $_POST['eventId'];
$title = $db->tos($_POST['title']);
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

// 이벤트 수정
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

// 이벤트 업데이트
$calendarId = 'primary'; // 사용자의 기본 캘린더
$updatedEvent = $service->events->update('45f03c6d209fdad24d6f9ad064fd339f2b626e6e28c3f0ae580374f772700840@group.calendar.google.com', $eventId, $event);

if ($updatedEvent) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('success' => false, 'error' => 'Failed to update event'));
}
?>
