<?php
define('PATH', "../");
include_once PATH."_init.php";
// Google Calendar API 라이브러리 로드
require '/home/isoi/sdk/google/vendor/autoload.php';
// 이벤트 정보 가져오기
$db = new db();
$db->setDb("rudy");
$event_id = $_POST['eventId'];

$auth_json_path = "../key/isoi_google_api_key.json";

// Google 클라이언트 생성 및 인증 설정
$client = new Google_Client();
$client->setAccessType('offline');
$client->setAuthConfig("../key/isoi_google_api_key.json");
$client->setApplicationName('My Calendar');
$client->setScopes(Google\Service\Calendar::CALENDAR);

$service = new Google\Service\Calendar($client);

if ($event_id) {
    // 사용자의 구글 캘린더에서 이벤트 삭제
    $calendarId = 'primary'; // 사용자의 기본 캘린더
    try {
        $service->events->delete($calendarId, $event_id);
        echo json_encode(array('success' => true));
    } catch (Google\Service\Exception $e) {
        echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'error' => 'Event ID not provided'));
}
?>
