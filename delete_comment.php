<?php
// 데이터베이스 연결 설정
$host = "localhost";
$user = "root";
$pw = "182511";
$dbName = "isoi";

// MySQL 데이터베이스에 연결
$conn = new mysqli($host, $user, $pw, $dbName);

// 연결 확인
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// POST 요청으로부터 데이터 가져오기
$year = $_POST['year'];
$month = $_POST['month'];
$day = $_POST['day'];
$no = $_POST['no']; // 수정: comment_id 대신 no를 사용

// SQL 쿼리 작성
$sql = "DELETE FROM calendar WHERE date = '{$year}-{$month}-{$day}' AND no = $no"; // 수정: comment_id 대신 no를 사용

// 쿼리 실행
if ($conn->query($sql) === TRUE) {
    echo "주석이 성공적으로 삭제되었습니다.";
} else {
    echo "주석 삭제에 실패했습니다: " . $conn->error;
}

// 데이터베이스 연결 닫기
$conn->close();
?>
