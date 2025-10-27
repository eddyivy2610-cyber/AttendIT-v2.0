<?php
class Student {
    private $conn;
    private $table_name = "students";

    public $student_id;
    public $email;
    public $student_name;
    public $period_of_attachment;
    public $institution_id;
    public $birthday;
    public $course_of_study;
    public $skill_of_interest;
    public $gender;
    public $join_date;
    public $end_date;
    public $supervisor;
    public $status;
    public $phone;
    public $photo_url;
    public $institution_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all students
    public function read() {
        $query = "SELECT s.*, i.institution_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN institutions i ON s.institution_id = i.institution_id 
                  ORDER BY s.student_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
    $query = "SELECT s.*, i.institution_name 
              FROM " . $this->table_name . " s 
              LEFT JOIN institutions i ON s.institution_id = i.institution_id 
              WHERE s.student_id = ? 
              LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $this->student_id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row) {
        // Set ALL properties from the database row
        $this->student_id = $row['student_id'];
        $this->student_name = $row['student_name'];
        $this->email = $row['email'];
        $this->phone = $row['phone'];
        $this->period_of_attachment = $row['period_of_attachment'];
        $this->institution_id = $row['institution_id'];
        $this->birthday = $row['birthday'];
        $this->course_of_study = $row['course_of_study'];
        $this->skill_of_interest = $row['skill_of_interest'];
        $this->gender = $row['gender'];
        $this->join_date = $row['join_date'];
        $this->end_date = $row['end_date'];
        $this->supervisor = $row['supervisor'];
        $this->status = $row['status'];
        $this->photo_url = $row['photo_url'];
        $this->institution_name = $row['institution_name'];
        
        return true;
    }
    return false;
}

public function getActiveStudents() {
    $query = "SELECT * FROM students 
             WHERE status = 'Active' 
             AND (end_date IS NULL OR end_date >= CURDATE()) 
             AND (period_of_attachment IS NULL OR 
                 DATE_ADD(join_date, INTERVAL period_of_attachment MONTH) >= CURDATE())";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
    
    // Create new student
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET email=:email, student_name=:student_name, period_of_attachment=:period_of_attachment, 
                     institution_id=:institution_id, birthday=:birthday, course_of_study=:course_of_study, 
                     skill_of_interest=:skill_of_interest, gender=:gender, join_date=:join_date, 
                     end_date=:end_date, supervisor=:supervisor, photo_url=:photo_url, phone=:phone, status=:status";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->student_name = htmlspecialchars(strip_tags($this->student_name));
        $this->period_of_attachment = htmlspecialchars(strip_tags($this->period_of_attachment));
        $this->institution_id = htmlspecialchars(strip_tags($this->institution_id));
        $this->birthday = htmlspecialchars(strip_tags($this->birthday));
        $this->course_of_study = htmlspecialchars(strip_tags($this->course_of_study));
        $this->skill_of_interest = htmlspecialchars(strip_tags($this->skill_of_interest));
        $this->gender = htmlspecialchars(strip_tags($this->gender));
        $this->join_date = htmlspecialchars(strip_tags($this->join_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->supervisor = htmlspecialchars(strip_tags($this->supervisor));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind parameters
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":student_name", $this->student_name);
        $stmt->bindParam(":period_of_attachment", $this->period_of_attachment);
        $stmt->bindParam(":institution_id", $this->institution_id);
        $stmt->bindParam(":birthday", $this->birthday);
        $stmt->bindParam(":course_of_study", $this->course_of_study);
        $stmt->bindParam(":skill_of_interest", $this->skill_of_interest);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":join_date", $this->join_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":supervisor", $this->supervisor);
        $stmt->bindParam(":photo_url", $this->photo_url);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;

        
    }
}
?>