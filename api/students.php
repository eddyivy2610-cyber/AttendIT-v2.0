<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'config.php';
include_once 'model/students.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all students
        $stmt = $student->read();
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $students_arr = array();
            $students_arr["success"] = true;
            $students_arr["data"] = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($students_arr["data"], $row);
            }
            
            http_response_code(200);
            echo json_encode($students_arr);
        } else {
            http_response_code(404);
            echo json_encode(array(
                "success" => false,
                "message" => "No students found."
            ));
        }
        break;
        
    case 'POST':
        // Create new student
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->student_name) && !empty($data->email)) {
            $student->student_name = $data->student_name;
            $student->email = $data->email;
            $student->institution_id = $data->institution_id ?? null;
            $student->course_of_study = $data->course_of_study ?? '';
            $student->gender = $data->gender ?? 'Male';
            $student->status = $data->status ?? 'Active';
            // Set other properties...
            
            if($student->create()) {
                http_response_code(201);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Student created successfully."
                ));
            } else {
                http_response_code(503);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unable to create student."
                ));
            }
        } else {
            http_response_code(400);
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to create student. Data is incomplete."
            ));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>