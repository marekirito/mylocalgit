<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <title>Calendar</title>
    <style>
        /* CSS 코드 */
        .calendar {
            border-collapse: collapse;
            width: 100%;
        }

        .calendar th, .calendar td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .calendar th {
            background-color: #f2f2f2;
        }

        .calendar-day {
            width: 50px;
            height: 50px;
        }

        .comment {
            font-size: 12px;
        }

        .comment-form {
            display: none;
        }

        /* 모달 스타일 */
        .modal-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .modal {
            background-color: #fff;
            border-radius: 5px;
            width: 50%;
            max-width: 600px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f00;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            background-color: #c00;
        }
        /* 여기에 CSS 코드가 들어갑니다 */
    </style>
</head>
<body>

<?php
date_default_timezone_set('Asia/Seoul');

$host = "localhost";
$user = "root";
$pw = "182511";
$dbName = "isoi";

$conn = new mysqli($host, $user, $pw, $dbName);

/* DB 연결 확인 */
if($conn){ echo "Connection established"."<br>"; }
else{ die( 'Could not connect: ' . mysqli_error($conn) ); }

// 달력의 현재 연도와 월 설정
$year = isset($_GET['year']) ? $_GET['year'] : date("Y");
$month = isset($_GET['month']) ? $_GET['month'] : date("n");
$day = isset($_GET['day']) ? $_GET['day'] : null;

// 이전 달과 다음 달 버튼의 링크 생성
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month == 0) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13) {
    $next_month = 1;
    $next_year++;
}

echo "<a href='?year=$prev_year&month=$prev_month'>이전달</a>";
echo "<a href='?year=$next_year&month=$next_month'>다음달</a>";

// 현재 달의 첫 날과 마지막 날 계산
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$last_day_timestamp = mktime(0, 0, 0, $month + 1, 0, $year);

// 현재 달의 일 수 계산
$days_in_month = date("t", $first_day_timestamp);

// 이번 달의 시작 요일과 마지막 요일 계산
$first_day_of_week = date("N", $first_day_timestamp);
$last_day_of_week = date("N", $last_day_timestamp);
?>

<table class='calendar'>
    <caption><?php echo "{$year}년 {$month}월"; ?></caption>
    <tr>
        <th>월</th><th>화</th><th>수</th><th>목</th><th>금</th><th>토</th><th>일</th>
    </tr>

    <tr>
        <?php
        
        for ($i = 1; $i < $first_day_of_week; $i++) {
            echo "<td></td>";
        }

        $day_counter = 1;
        while ($day_counter <= $days_in_month) {
            for ($i = $first_day_of_week; $i <= 7; $i++) {
                if ($day_counter <= $days_in_month) {
                    // 날짜 출력 및 주석 기능 추가
                    echo "<td class='calendar-day'>";
                    // echo "<a href='javascript:void(0);' onclick='showCommentForm($year, $month, $day_counter);'>$day_counter</a>";
                    echo "<a href='javascript:void(0);' onclick='showCommentDetailSave($year, $month, $day_counter);'>$day_counter</a>";
                    
                    // DB에서 해당 날짜의 일정 가져오기
                    $sql = "SELECT no,comment, status, comment_id FROM calendar WHERE date = '{$year}-{$month}-{$day_counter}';";
                    $result = mysqli_query($conn, $sql);
                    $commentCounter = 0; // 주석 카운터 추가
                    while ($row = mysqli_fetch_assoc($result)) {
                        $comment = isset($row['comment']) ? $row['comment'] : '';
                        $status = isset($row['status']) ? $row['status'] : '';
                        $comment_id = isset($row['comment_id']) ? $row['comment_id'] : '';
                        $no = isset($row['no']) ? $row['no'] : '';
                        // 데이터 속성 추가, $commentCounter 변수를 사용하여 주석의 고유 식별자를 생성합니다.
                        echo "<br/><span class='comment' data-comment-id='{$no}' id='data-comment-id-{$no}' data-comment='{$comment}' data-status='{$status}' data-year='{$year}' data-month='{$month}' data-day='{$day_counter}'>$comment</span>";
                        $commentCounter++; // 주석 카운터 증가
                    }
                                   
                    echo "</td>";
                    
                    $day_counter++;
                } else {
                    echo "<td></td>";
                }
            }
            echo "</tr>";
            if ($day_counter <= $days_in_month) {
                echo "<tr>";
            }
            $first_day_of_week = 1;
        }
        ?>
    </tr>
</table>

<?php

// 주석 삭제 기능
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] == '1') {
    $year = $_POST['year'];
    $month = $_POST['month'];
    $day = $_POST['day'];

    $sql = "DELETE FROM calendar WHERE date = '{$year}-{$month}-{$day}';";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "<p>주석이 삭제되었습니다.</p>";
    } else {
        echo "<p>주석 삭제에 실패했습니다.</p>";
    }
}
?>

<!-- 모달을 나타내는 HTML 코드 -->
<div id="modal-container" class="modal-container">
    <div id="modal" class="modal">
        <div id="modal-content" class="modal-content">
            <!-- 모달 내용이 여기에 들어갑니다 -->
        </div>
        <button id="modal-close-btn" class="modal-close-btn" onclick="closeModal()">닫기</button>
     
    </div>
</div>


<script>
     // 모달을 토글하는 함수 작성
     function toggleModal(content) {
        var modalContainer = document.getElementById("modal-container");
        var modalContent = document.getElementById("modal-content");

        modalContent.innerHTML = content;
        modalContainer.style.display = "block";
    }
// 모달 닫기 함수
function closeModal() {
        var modalContainer = document.getElementById("modal-container");
        modalContainer.style.display = "none";
    }
    // 주석 입력 폼 표시
    function showCommentForm(year, month, day) {
        var commentFormId = "comment-form-" + year + "-" + month + "-" + day;
        var commentForm = document.getElementById(commentFormId).innerHTML;
        toggleModal(commentForm);
    }

        // 주석 클릭 시 모달 표시
    function showCommentDetail(commentId, comment, status, year, month, day) {
        var checked = status === 'Y' ? 'checked' : ''; // 상태에 따라 체크박스의 상태 설정

        var commentDetail = `
            <p>${comment}</p>
            <p>Status: <input type='checkbox' id='status-checkbox' ${checked} onchange='updateCommentStatus(${commentId}, this.checked, ${year}, ${month}, ${day});'></p> <!-- 체크박스를 클릭할 때마다 주석의 상태 업데이트 -->
            <form method='post'>
                <input type='hidden' name='year' value='${year}'>
                <input type='hidden' name='month' value='${month}'>
                <input type='hidden' name='day' value='${day}'>
                <input type='hidden' name='comment_id' value='${commentId}'>
                <input type='button' value='주석 삭제' onclick='deleteComment(this.parentNode)'>

                <input type='submit' value='주석 삭제' > <!-- 주석 삭제 버튼 -->
            </form>
        `;
        toggleModal(commentDetail);
    }

    // 날짜 클릭 시 모달 표시
function showCommentDetailSave(year, month, day) {
    var commentDetail = `
        <form method='post'>
            <input type='hidden' id='year' value='${year}'>
            <input type='hidden' id='month' value='${month}'>
            <input type='hidden' id='day' value='${day}'>
            <input type='text' name='comment[]' id='comment-content-${day}' placeholder='주석을 입력하세요...'/><br/>
            <input type='checkbox' id='status-checkbox' name='status[]' value='Y'> 체크
            <button type='button' onclick='saveComment("${day}")'>주석 저장</button>
        </form>
    `;
    toggleModal(commentDetail);
}

// 주석 삭제 함수
function deleteComment(commentId) {
    var year = document.getElementById("year").value;
    var month = document.getElementById("month").value;
    var day = document.getElementById("day").value;

    // AJAX를 사용하여 주석을 삭제하는 PHP 스크립트 호출
    $.ajax({
        type: "POST",
        url: "/delete_comment.php", // 주석을 삭제하는 PHP 스크립트 경로 지정
        data: {
            comment_id: commentId,
            year: year,
            month: month,
            day: day
        },
        success: function(data) {
            // 삭제 성공 시 모달 닫기 및 페이지 리로드
            closeModal();
            location.reload();
        },
        error: function(xhr, status, error) {
            // 삭제 실패 시 처리
            alert("주석 삭제에 실패했습니다. 다시 시도해주세요.");
        }
    });
}

// 주석 상태 업데이트 함수
    function updateCommentStatus(commentId, status, year, month, day) {
        var statusValue = status ? 'Y' : 'N'; // 체크박스가 체크되면 'Y', 체크 해제되면 'N'
       
        // AJAX를 사용하여 주석 상태를 업데이트하는 PHP 스크립트 호출
        $.ajax({
            type: "POST",
            url: "/update_comment.php", // 현재 페이지에 주석 상태를 업데이트하는 PHP 스크립트 작성 (아래에 추가)
            data: {
                comment_id: commentId,
                status: statusValue,
                year: year,
                month: month,
                day: day
            },
            success: function(data) {
                // 업데이트 성공 시 아무런 동작 필요 없음
                document.getElementById('data-comment-id-'+commentId).setAttribute('data-status',statusValue);
            }
        });
    }
   
    // 주석 입력 폼 동적으로 추가
    function addCommentInput(parentNode) {
        var textarea = document.createElement("textarea");
        textarea.setAttribute("name", "comment[]");
        textarea.setAttribute("placeholder", "주석을 입력하세요...");
        parentNode.appendChild(textarea);
        var checkbox = document.createElement("input");
        checkbox.setAttribute("type", "checkbox");
        checkbox.setAttribute("name", "status[]");
        checkbox.setAttribute("value", "Y");
        parentNode.appendChild(checkbox);
    }
// 모달에 저장 버튼을 클릭했을 때 호출되는 함수
function saveAndCloseModal() {
    // 주석을 저장하는 기능을 여기에 추가합니다.

    // 모달을 닫습니다.
    closeModal();
}
// 모달에 저장 버튼을 클릭했을 때 호출되는 함수
// 모달에 저장 버튼을 클릭했을 때 호출되는 함수
function saveComment(save_day) {
    // 모달 내 체크박스 상태 확인
    var checkbox = document.getElementById("status-checkbox");

    var isChecked = checkbox.checked;

    // 모달 내 주석 내용 확인
    // var commentId = document.getElementById("data-comment-id").value;

    var commentContent = document.getElementById("comment-content-"+save_day).value;

    var year = document.getElementById("year").value;
    var month = document.getElementById("month").value;
    var day = document.getElementById("day").value;

    // AJAX를 사용하여 주석 내용과 상태를 서버에 저장
    $.ajax({
        type: "POST",
        url: "/save_comment.php",
        data: {
            // comment_id: commentId,
            comment_content: commentContent,
            status: isChecked ? 'Y' : 'N',
            year: year,
            month: month,
            day: day
        },
        success: function(data) {
            // echo "<br/><span class='comment' data-comment-id='{$no}' data-comment='{$comment}' data-status='{$status}' data-year='{$year}' data-month='{$month}' data-day='{$day_counter}'>$comment</span>";

            // 저장 후에는 모달 닫기
            closeModal(); // 모달 닫기 함수 호출
            // 저장 성공 시 필요한 동작 추가
            alert("주석이 성공적으로 저장되었습니다."); // 임시 메시지, 실제로는 필요한 동작 추가
            
    if (self.name != 'reload') {
        self.name = 'reload';
        self.location.reload(true);
    } else {
        self.name = '';
    }
        },
        error: function(xhr, status, error) {
            // 에러 발생 시 처리
            alert("주석 저장에 실패했습니다. 다시 시도해주세요.");
        }
    });
}


 // 모달 닫기 버튼 이벤트 핸들러 추가
 document.getElementById("modal-close-btn").addEventListener("click", function() {
        document.getElementById("modal-container").style.display = "none";
    });
   

// 모든 주석 요소에 클릭 이벤트 리스너 추가
var commentElements = document.getElementsByClassName('comment');
for (var i = 0; i < commentElements.length; i++) {
    commentElements[i].addEventListener('click', function() {
        // 주석의 내용, 상태, 년, 월, 일 가져오기
        var commentId = this.getAttribute('data-comment-id');
        var comment = this.getAttribute('data-comment');
        var status = this.getAttribute('data-status');
        var year = this.getAttribute('data-year');
        var month = this.getAttribute('data-month');
        var day = this.getAttribute('data-day');

        // 모달에 주석 내용 표시
        showCommentDetail(commentId, comment, status, year, month, day);
    });
}



    if (self.name != 'reload') {
        self.name = 'reload';
        self.location.reload(true);
    } else {
        self.name = '';
    }
</script>

</body>
</html>
