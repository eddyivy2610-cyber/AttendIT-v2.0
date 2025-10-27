<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'config.php';
include_once '../model/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->username) && !empty($data->password)) {
        $user_data = $user->login($data->username, $data->password);
        
        if($user_data) {
            session_start();
            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role'];
            
            echo json_encode(array(
                "success" => true,
                "message" => "Login successful",
                "user" => $user_data
            ));
        } else {
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid username or password"
            ));
        }
    } else {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Username and password are required"
        ));
    }
} else {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Method not allowed"));
}
?>