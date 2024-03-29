<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/locales-all.min.js'></script>
<script src="https://apis.google.com/js/api.js"></script>

<?php
    // 구글 연동데이터 조회 start
    require '/home/isoi/sdk/google/vendor/autoload.php';
    $member->userid;
    $client = new Google_Client();
    $client->setAccessType('offline');  // 오프라인 액세스를 허용합니다.
    $client->setAuthConfig("../key/isoi_google_api_key.json"); // API 계정 키를 설정합니다.

    $client->setApplicationName('My Calendar');  // 애플리케이션 이름을 원하는 대로 설정합니다.
    $client->setScopes(Google\Service\Calendar::CALENDAR);  // Google Calendar API의 스코프를 설정합니다.
    $service = new Google\Service\Calendar($client);
    // RFC3339 형식의 타임스탬프
    $date = new DateTime();
    if (empty($_POST['year'])||empty($_POST['month']) ||$_POST['year'] > date('Y') || ($_POST['year'] == date('Y') && $_POST['month'] >= date('m')))
        $date->modify('first day of this month');
    else{
        $date->setDate($_POST['year'], $_POST['month'], 1);
    }
    $calendar_sdate = $date->format(DateTime::RFC3339);

    $date->modify('last day of this month');
    $calendar_edate = $date->format(DateTime::RFC3339);

    $opt_params = array(
        'singleEvents' => TRUE,
        // 'timeMin' => ($calendar_sdate),
        // 'timeMax' => ($calendar_edate),
    );
    
    
    $user_id = $member->userid;
    $db_rudy = new db();
    $sql = "SELECT admin_group FROM isoi_member WHERE userid = '$user_id'";
    $user_groups = $db_rudy->getRow($sql); // 사용자가 속한 그룹 가져오기
    $user_groups = explode('|', $user_groups['admin_group']); // '|'를 기준으로 그룹들을 분리하여 배열로 저장
    $db_rudy->setDb("rudy");
    // 사용자의 권한을 기반으로 일정 필터링하여 가져오기
    $sql = "select no, title, link_id, admin_group, link_type FROM mall_calendar_type WHERE link_type='g_calendar' AND admin_group IN ('" . implode("','", $user_groups) . "')";
    $calendar_result = $db_rudy->getAllRow($sql); // 해당 그룹의 일정 가져오기
    $event_list = array();
    $event_id = 1;
    
    foreach($calendar_result as $calendar){
        $link_id = $calendar['link_id'];
        $events = $service->events->listEvents($link_id,$opt_params);
        foreach ($events->getItems() as $event) {

            $sdate = new DateTime($event->start->getDate()?$event->start->getDate():$event->start->getDatetime());
            $edate = new DateTime($event->end->getDate()?$event->end->getDate():$event->end->getDatetime());
            $calendar_name = $event->organizer->getDisplayName();
            preg_match('/\[(.*?)\]\s*(.*)/', $event->getSummary(), $matches);
            if (!empty($matches)) {
                $category = $matches[1];
            } else {
                $category = "";
            }
            if($calendar_name == '자사몰 일정'){
                switch ($category) {
                    // 세일즈
                    case '월 이벤트':
                    case '스팟 이벤트':
                    case '단독 기획':
                    case '쿠폰':
                    case '예약판매':
                    case '신상품 출시':
                    case '생산파트':
                        $color = "#507b33"; // 녹색
                        break;
                    // 멤버십
                    case '특가샵':
                    case '충성회원':
                    case '신규회원':
                    case '사은품/혜택':
                        $color = "#2e75b5"; // 파랑색
                        break;
                    // 마케팅
                    case '마케팅 프로모션':
                    case '브랜딩':
                    case '광고매체':
                    case '콘텐츠':
                        $color = "#833b0c"; // 갈색
                        break;
                    // 서비스
                    case '신규 서비스':
                    case 'UI/UX':
                        $color = "#bf8f01"; // 황녹색
                        break;
                    //#7986cb
                    default:
                        $color = "#696969"; //기본값 회색
                        break;
                }
            }else if($calendar_name == '시스템'){
                $color = "#ff0000"; // red
            }

            $event_set = array(
                'id' => $event_id++,
                'title' => $event->getSummary(),
                'start' => $sdate->format('Y-m-d H:i:s'),
                'end' => $edate->format('Y-m-d H:i:s'),
                'color' => $color,
                'description' => $event->getDescription(),
                'extendedProps' => array(
                    'calendar' => $calendar['no']
                )
            );
            array_push($event_list, $event_set);
        }
    }

    $event_list_json = json_encode($event_list);
    // 구글 연동데이터 조회 end
        

?>
<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 100;
    }

    .modal .bg {
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
    }

    .modalBox {
        position: absolute;
        background-color: #fff;
        width: 300px;
        height: 200px;
        padding: 15px;
    }
    .memoBox {
        position: absolute;
        background-color: #fff;
        width: 300px;
        height: auto;
        padding: 15px;
    }    
    .memoBox button {
        display: block;
        width: 80px;
        margin: 0 auto;
    }
    .hidden {
        display: none;
    }
    
    #eventTable {
    width: 100%;
    border-collapse: collapse;
    }

    #eventTable th, #eventTable td {
        border: 1px solid #ddd;
        padding: 8px;
        vertical-align: top;
    }

    .title-input, .date-input, .time-input, .description-input {
        width: 100%;
    }

    #alldayCheckbox {
        margin-left: 10px;
    }
    .circle {
        display: inline-block;
        width: 9px;          
        height: 9px;         
        border-radius: 50%;    
        vertical-align: middle;
        margin-right: 5px;     
    }
    .text {
        display: inline-block; /* 인라인 블록 요소로 설정 */
        vertical-align: middle; /* 텍스트와 원을 수직 중앙 정렬 */
    }
    #checkbox_container{
        border: 2px solid #3498db;
        border-radius: 10px;
        background-color: #f2f2f2;
        box-shadow: 2px 2px 5px #888888;
        justify-content: center;
        display: flex;
        padding: 15px;
    }
</style>
<div>
<h2><a href="https://calendar.google.com/calendar" target='_blank'>구글 캘린더 새창 띄우기</a></h2>
<br>
<button type="button" onclick=" $('#calendar_info_div').prop('hidden') ? $('#calendar_info_div').prop('hidden', false) : $('#calendar_info_div').prop('hidden',true);">캘린더 설명 보기/접기</button>
<div id="calendar_info_div" hidden>
<?if(isdev()) echo "(dev) 추후 아래 캘린더 정보 안내는 팀 권한이 있는 정보만 보이도록 수정이 필요함"?>
<h4>[자사팀]</h4>
<ul>
    <li>일정 대분류 색상 (제목 앞에 중괄호[]를 이용하여 표기)
        <ul>
            <li><div class="circle" style="background-color: #507b33;"></div><span class="text">[세일즈] 스팟 이벤트 | 단독 기획 | 쿠폰 | 예약판매 | 신상품 출시 | 생산파트</span></li>
            <li><div class="circle" style="background-color: #2e75b5;"></div><span class="text">[멤버십] 멤버십 | 특가샵 | 충성회원 | 신규회원 | 사은품/혜택</span></li>
            <li><div class="circle" style="background-color: #833b0c;"></div><span class="text">[마케팅] 마케팅 | 마케팅 프로모션 | 브랜딩 | 광고매체 | 콘텐츠</span></li>
            <li><div class="circle" style="background-color: #bf8f01;"></div><span class="text">[서비스] 서비스 | 신규 서비스 | UI/UX</span></li>
            <li><div class="circle" style="background-color: #696969;"></div><span class="text">기본값</span></li>
        </ul> 
    </li>
    <li>DB 연동을 위한 매핑값 (내용안에 중괄호[]를 이용하여 표기, 여러 값을 넣을 경우 , 기호를 구분자로 사용 ex) [xxxx: yyyy,yyyy,yyyy])
        <ul>
            <li>상품번호 (상품코드만 있을 경우 상품번호라 기입하고 값은 코드로 작성)</li>
            <li>이벤트번호</li>
            <li>쿠폰번호</li>
        </ul> 
    </li>
</ul>
<h4>[시스템 개발팀]</h4>
<ul>
    <li>일정 대분류 색상 (제목 앞에 중괄호[]를 이용하여 표기)
        <ul>
            <li><div class="circle" style="background-color: #ff0000;"></div><span class="text">기본값</span></li>
        </ul> 
    </li>
</ul>
<b>* 색상, 대분류, DB매핑값 추가/수정이 필요하신 경우 시스템팀 담당자(서인혁)에게 문의 부탁드립니다.<b>
</div>
<br><br>


<div id='calendar-container' style="display:flex">
    <div id='checkbox_container'>
        <ul style="list-style-type: none; padding:0px; margin:0px; line-height: 2.5;">
            <li>
                <input type="checkbox" id="mall_checkbox_all" name="mall_checkbox_all" checked>
                <label>전체 선택/해제</label>
                <li>
                </li>
                <input type="checkbox" id="mall_checkbox_jasa" name="mall_checkbox_jasa" checked>
                <label><span onclick="toggleSubLabels('jasa')">자사 목록</span></label>
                <ul id="sub_jasa_labels" style="display: none;">
                    <?php foreach ($calendar_result as $key => $row) { ?>
                        <?php if ($row['admin_group'] == 'jasa') { ?>
                            <li>
                                <input type="checkbox" name="mall_checkbox[]" value="<?= $row['no'] ?>" data-admin_group="<?= $row['admin_group'] ?>" checked>
                                <label>&nbsp<?= $row['title'] ?></label>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
                <li>
                </li>
                <input type="checkbox" id="mall_checkbox_tasa" name="mall_checkbox_tasa" checked>
                <label><span onclick="toggleSubLabels('tasa')">타사 목록</span></label>
                <ul id="sub_tasa_labels" style="display: none;">
                    <?php foreach ($calendar_result as $key => $row) { ?>
                        <?php if ($row['admin_group'] == 'tasa') { ?>
                            <li>
                                <input type="checkbox" name="mall_checkbox[]" value="<?= $row['no'] ?>" data-admin_group="<?= $row['admin_group'] ?>" checked>
                                <label>&nbsp<?= $row['title'] ?></label>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
                <li>
                </li>
                <input type="checkbox" id="mall_checkbox_dev" name="mall_checkbox_dev" checked>
                <label><span onclick="toggleSubLabels('dev')">시스템 목록</span></label>
                <ul id="sub_dev_labels" style="display: none;">
                    <?php foreach ($calendar_result as $key => $row) { ?>
                        <?php if ($row['admin_group'] == 'dev') { ?>
                            <li>
                                <input type="checkbox" name="mall_checkbox[]" value="<?= $row['no'] ?>" data-admin_group="<?= $row['admin_group'] ?>" checked>
                                <label>&nbsp<?= $row['title'] ?></label>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            </li>
        </ul>
    </div>
    <div id='calendar' style="width: 80%; margin: 0 auto;"></div> 
</div>



<!-- 이벤트 등록 모달창 -->
<div class="event_modal modal hidden">
    <div class="bg" id="evnet_modal_bg"></div>
    <div class="memoBox">
        <form id="event_form" method="post" action="index.php" style="padding:20px">
            <table id="eventTable">
                <tr>
                    <th>제목</th>
                    <td><input type="text" placeholder="제목 추가" id="event_title" class="title-input"></td>
                </tr>
                <tr>
                    <th>시작일</th>
                    <td><input type="date" class="date-input" id="start_date"></td>
                </tr>
                <tr>
                    <th>종료일</th>
                    <td><input type="date" class="date-input" id="end_date"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" id="alldayCheckbox" checked> 종일
                        <input type="time" id="endTime" style="display:none;">
                    </td>
                </tr>
                <tr>
                    <th>설명</th>
                    <td><textarea class="description-input"></textarea></td>
                </tr>
            </table>
            <button type="button" onclick="saveEvent()">저장</button>
        </form>
    </div>
</div>
</div>
<script>

    let calendar="";
	$(document).ready(function() {
        let calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialDate: "<?echo ((empty($_POST['year'])||empty($_POST['month']) || $_POST['year'] > date('Y') || ($_POST['year'] == date('Y') && $_POST['month'] > date('m'))) ? date('Y-m-01') : sprintf('%04d-%02d-01', $_POST['year'], $_POST['month']))?>",
            googleCalendarApiKey : "AIzaSyDXN2jMOsktRp5ApYQvGbmA4Vw4FbCW3g0",
            eventSources :[
                {  //대한민국 공휴일
                googleCalendarId: 'ko.south_korea#holiday@group.v.calendar.google.com',
                color: '#ffe1cd',
                textColor: 'black'
                }],
            initialView: 'dayGridMonth', // 초기 로드 될때 보이는 캘린더 화면 (기본 설정: 달)
            locale: 'ko', // 한국어 설정
            expandRows: true, // 화면에 맞게 높이 재설정
            selectable: false,
            height: '1000px', // calendar 높이 설정
            weight: '500px',
            ihandleWindowResize:false, // 브라우저 창 크기조절시 자동조절
            events: <?php echo $event_list_json;?>, // 이벤트
            eventClick: function(info) {
                let description = info.event.extendedProps.description;
                description = (description == null?"내용없음" : description);
                alert(description);

                // url 커스텀 이동
                info.jsEvent.preventDefault(); // 클릭이벤트 후처리 방지

                // if (info.event.url) {
                //     window.open("https://fullcalendar.io/docs/eventClick");
                // }
            }, // 일정 이벤트 클릭 이벤트 callback
            // select: function(info) {
            //     // etcMemoUpdateOpen();
            //     alert('selected ' + info.startStr + ' to ' + info.endStr);
            // }

            dateClick: function(info) {
            // 클릭한 날짜 정보 가져오기
            var clickedDate = info.date;

            // 모달 열기
            openEventModal(clickedDate);
        }
        });
        
        calendar.render();

        $('#alldayCheckbox').on("change", function() {
            if ($(this).is(':checked')) {
                $('#startTime, #endTime').hide();
            } else {
                $('#startTime, #endTime').show();
            }
        });

        $("input[name='mall_checkbox[]']").on("change", function() {
            if (!$(this).is(':checked')) {
                $("#mall_checkbox_all").prop("checked", false);
            }
            toggleEventsVisibility(this.value,$(this).is(':checked'));
        });

	}); // $(document).ready(function() { end

    // 메모 수정 모달 오픈
    function etcMemoUpdateOpen(itemCd,etc_memo,secret_memo){
        $("#event_form")[0].reset();
        $("#event_title").val(itemCd);
        $("#etc_memo").val(etc_memo);
        $("#secret_memo").val(secret_memo);
        $('.event_modal').removeClass('hidden');
    }

    $('#evnet_modal_bg').on("click", function(){
        $('.event_modal').addClass('hidden');
        $("#event_form")[0].reset();
    })
// 이벤트 모달 열기 함수 정의
function openEventModal(clickedDate) {
    // 모달 열기
    $('.event_modal').removeClass('hidden');

    // 클릭한 날짜를 모달에 표시 (예시: input 요소에 적용)
    $('#start_date').val(clickedDate.format('YYYY-MM-DD'));
    $('#end_date').val(clickedDate.format('YYYY-MM-DD'));
}

    function addEvent(){
        console.log("test");
        $.ajax({
        url:"/pluto/isoi_calendar_prc.php",
        data:{'cmd':'addEvent',
            'channel_code': 01 
        },
        dataType:"json",
        type:"POST",
        success:function(result) {
            if (result.code == '1') { // 성공
                alert(result.msg);
            }else if (result.code == '0'){
                alert(result.msg);
            }
        },
        error:function(request,status,error){
            alert('조회중 오류가 발생하였습니다.',error);
        }
    });
    }
    
 // 이벤트 저장 함수
function saveEvent() {
    var title = $('#event_title').val();
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    var description = $('.description-input').val();

    $.ajax({
        type: "POST",
        url: "save_event.php", // PHP 스크립트 경로
        data: {
            'cmd':'addEvent',
            'channel_code': 01 
            title: title,
            startDate: startDate,
            endDate: endDate,
            description: description
        },dataType:"json",
        type:"POST",
        success: function(response) {
            alert('이벤트가 성공적으로 저장되었습니다.');
            $('.event_modal').addClass('hidden'); // 모달 닫기
        },
        error: function(xhr, status, error) {
            alert('이벤트 저장 중 오류가 발생했습니다.');
            console.error(xhr, status, error);
        }
    });
}


    
    // 특정 캘린더 이벤트 숨기고 나타내기
    function toggleEventsVisibility(calendar_no,display) {
        display = display ? 'auto' : 'none';
        var events = calendar.getEvents();
        events.forEach(function(event) {
            if (event.extendedProps.calendar === calendar_no) {
                event.setProp('display',display);
            }
        });
    }

    // 체크박스 전체 선택
    $("#mall_checkbox_all").on("click", function () {
        if(this.checked){
            $("input[name='mall_checkbox[]']").prop("checked", true);
            $("input[name='mall_checkbox_jasa']").prop("checked", true);
            $("input[name='mall_checkbox_tasa']").prop("checked", true);
            $("input[name='mall_checkbox_dev']").prop("checked", true);
        }
        else {
            $("input[name='mall_checkbox[]']").prop("checked", false);
            $("input[name='mall_checkbox_jasa']").prop("checked", false);
            $("input[name='mall_checkbox_tasa']").prop("checked", false);
            $("input[name='mall_checkbox_dev']").prop("checked", false);
        }
        let checkbox_values = $("input[name='mall_checkbox[]']").map(function() {
            return $(this).val();
        }).get();

        checkbox_values.forEach(value => {
            toggleEventsVisibility(value,this.checked);
        });
    });
    




// 체크박스 팀 선택
$(document).ready(function() {
    // '자사' 버튼 클릭 이벤트 리스너 추가
    $('#mall_checkbox_jasa').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupCheckboxes('jasa', isChecked);
    });
});

$(document).ready(function() {
    // 'tasa' 버튼 클릭 이벤트 리스너 추가
    $('#mall_checkbox_tasa').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupCheckboxes('tasa', isChecked);
    });
});// 체크박스 팀 선택
$(document).ready(function() {
    // '시스템' 버튼 클릭 이벤트 리스너 추가
    $('#mall_checkbox_dev').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupCheckboxes('dev', isChecked);
    });
});
// 특정 팀명의 체크박스들을 선택하거나 해제하는 함수
function toggleadmin_groupCheckboxes(admin_group, check) {
    // 각 행(row)에 대해 반복하여 팀명인 체크박스를 찾아서 선택하거나 해제합니다.
    $("input[name='mall_checkbox[]']").each(function() {
        var rowadmin_group = $(this).data('admin_group'); // 현재 체크박스의 팀명 데이터 값 가져오기
        if (rowadmin_group === admin_group) {
            $(this).prop('checked', check); // 팀명인 체크박스의 상태를 변경합니다.
            toggleEventsVisibility($(this).val(), check); // 해당 팀의 이벤트들의 표시 여부도 변경합니다.
        }
    });
}

    


//체크박스해제시 해당 admin_group 감추기
$(document).ready(function() {
    // '자사 목록' 텍스트 클릭 이벤트 리스너 추가
    $('#mall_checkbox_jasa').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupRows('jasa', isChecked);
    });

    // 'tasa 목록' 텍스트 클릭 이벤트 리스너 추가
    $('#mall_checkbox_tasa').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupRows('tasa', isChecked);
    });

    // '시스템 목록' 텍스트 클릭 이벤트 리스너 추가
    $('#mall_checkbox_dev').click(function() {
        var isChecked = $(this).prop('checked');
        toggleadmin_groupRows('dev', isChecked);
    });
});

// 특정 팀명에 해당하는 행들을 보이거나 숨기는 함수
function toggleadmin_groupRows(admin_group, check) {
    // 각 행(row)에 대해 반복하여 특정 팀명에 해당하는 행의 가시성을 조절합니다.
    $("input[name='mall_checkbox[]']").each(function() {
        var rowadmin_group = $(this).data('admin_group'); // 현재 체크박스의 팀명 데이터 값 가져오기
        if (rowadmin_group === admin_group) {
            if (check) {
                $(this).closest('li').show(); // 팀명에 해당하는 행을 보이게 합니다.
            } 
        }
    });

}

    // 라벨 클릭시 하위 라벨 토글
function toggleSubLabels(admin_group) {
    var subLabels = document.getElementById("sub_" + admin_group + "_labels");
    if (subLabels.style.display === "none") {
        subLabels.style.display = "block";
    } else {
        subLabels.style.display = "none";
    }
}

</script>

