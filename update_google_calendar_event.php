<?php
define('PATH', "../");
include_once PATH."_init.php";

// Google Calendar API 라이브러리 로드
require '/home/isoi/sdk/google/vendor/autoload.php';

// 이벤트 정보 가져오기
$db = new db();
$db->setDb("rudy");

$eventId = $_POST['eventId'];
$calendarId = $_POST['calendarId'];
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

// 이벤트 가져오기
$event = $service->events->get($calendarId, $eventId);

// 이벤트 수정
$event->setSummary($title);
$event->setDescription($description);
$event->getStart()->setDateTime($startDate);
$event->getEnd()->setDateTime($endDate);

// 이벤트 업데이트
try {
    $updatedEvent = $service->events->update($calendarId, $eventId, $event);
    echo json_encode(array('success' => true, 'result' => $event));
} catch (Google\Service\Exception $e) {
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
}
?>
