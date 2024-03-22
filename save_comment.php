<?php
// 데이터베이스 연결 설정
$host = "localhost";
$user = "root";
$pw = "182511";
$dbName = "isoi";

$conn = new mysqli($host, $user, $pw, $dbName);

// POST 요청으로부터 데이터 수신
$commentId = $_POST['comment_id'];
$commentContent = $_POST['comment_content'];
$status = $_POST['status'];
$year = $_POST['year'];
$month = $_POST['month'];
$day = $_POST['day'];

// 데이터베이스에 주석 저장
if($commentId){
    $sql = "SELECT * FROM calendar WHERE date = '{$year}-{$month}-{$day}' AND no = $commentId;";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "쿼리 실행에 실패했습니다: " . mysqli_error($conn);
        exit;
    }
    if(mysqli_num_rows($result) > 0) {
        $sql = "UPDATE calendar SET comment = '{$commentContent}', status = '{$status}' WHERE date = '{$year}-{$month}-{$day}' AND no = $commentId;";
    } else {
        $sql = "INSERT INTO calendar (comment, date, status) VALUES ('{$commentContent}', '{$year}-{$month}-{$day}', '{$status}');";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo "주석이 성공적으로 저장되었습니다.";
    } else {
        echo "주석 저장에 실패했습니다: " . mysqli_error($conn);
    }
}else {
    $sql = "INSERT INTO calendar (comment, date, status) VALUES ('{$commentContent}', '{$year}-{$month}-{$day}', '{$status}');";
    
    if (mysqli_query($conn, $sql)) {
        echo "주석이 성공적으로 저장되었습니다.";
    } else {
        echo "주석 저장에 실패했습니다: " . mysqli_error($conn);
    }
}





// 데이터베이스 연결 종료
mysqli_close($conn);
?>
