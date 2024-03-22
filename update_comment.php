<?php
// 데이터베이스 연결 설정
$host = "localhost";
$user = "root";
$pw = "182511";
$dbName = "isoi";

$conn = new mysqli($host, $user, $pw, $dbName);

// POST 요청으로부터 데이터 수신
$commentId = $_POST['comment_id'];
$status = $_POST['status'];
$year = $_POST['year'];
$month = $_POST['month'];
$day = $_POST['day'];

// 데이터베이스에 주석 상태 업데이트
$sql = "UPDATE calendar SET status = '{$status}' WHERE date = '{$year}-{$month}-{$day}' AND no = $commentId;";

if (mysqli_query($conn, $sql)) {
    echo "주석 상태가 업데이트되었습니다.";
} else {
    echo "주석 상태 업데이트에 실패했습니다: " . mysqli_error($conn);
}

// 데이터베이스 연결 종료
mysqli_close($conn);
?>
