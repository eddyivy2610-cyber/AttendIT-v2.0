<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'config.php';
include_once 'model/attendance.php';
include_once 'model/students.php';

$database = new Database();
$db = $database->getConnection();
$attendance = new Attendance($db);
$student = new Student($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['student_id'])) {
            // Get student attendance history
            $attendance->student_id = $_GET['student_id'];
            $stmt = $attendance->getStudentAttendance($attendance->student_id);
        } else if(isset($_GET['today'])) {
            // Get today's attendance
            $stmt = $attendance->getTodaysAttendance();
        } else {
            // Get today's summary
            $summary = $attendance->getTodaysSummary();
            echo json_encode(array("success" => true, "data" => $summary));
            exit;
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array("success" => true, "data" => $result));
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        $attendance->student_id = $data->student_id;
        $attendance->attendance_date = $data->attendance_date ?? date('Y-m-d');
        $attendance->arrival_time = $data->arrival_time;
        $attendance->departure_time = $data->departure_time;
        $attendance->status = $data->status;
        $attendance->notes = $data->notes ?? '';
        $attendance->marked_by = $data->marked_by ?? 1; // Default to admin
        
        if($attendance->mark()) {
            echo json_encode(array("success" => true, "message" => "Attendance marked successfully"));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to mark attendance"));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("success" => false, "message" => "Method not allowed"));
        break;
}
?>