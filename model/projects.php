<?php
class Project {
    private $conn;
    private $table_name = "projects";

    public $project_id;
    public $project_name;
    public $technology_stack;
    public $students;
    public $supervisor;
    public $progress;
    public $due_date;
    public $description;
    public $repository_url;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET project_name=:project_name, technology_stack=:technology_stack, 
                     students=:students, supervisor=:supervisor, progress=:progress, 
                     due_date=:due_date, description=:description, repository_url=:repository_url";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->project_name = htmlspecialchars(strip_tags($this->project_name));
        $this->technology_stack = htmlspecialchars(strip_tags($this->technology_stack));
        $this->students = htmlspecialchars(strip_tags($this->students));
        $this->supervisor = htmlspecialchars(strip_tags($this->supervisor));
        
        // Bind values
        $stmt->bindParam(":project_name", $this->project_name);
        $stmt->bindParam(":technology_stack", $this->technology_stack);
        $stmt->bindParam(":students", $this->students);
        $stmt->bindParam(":supervisor", $this->supervisor);
        $stmt->bindParam(":progress", $this->progress);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":repository_url", $this->repository_url);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get projects for a specific student
    public function getStudentProjects($student_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE students LIKE :student_name 
                 OR students LIKE :student_name_comma 
                 OR students = :student_name_exact
                 ORDER BY due_date ASC";
        
        $stmt = $this->conn->prepare($query);
        
        // Get student name first (we need to query the students table)
        $student_name = $this->getStudentName($student_id);
        
        if ($student_name) {
            // Search for student name in various formats
            $search_pattern = "%" . $student_name . "%";
            $search_pattern_comma = "%" . $student_name . ",%";
            
            $stmt->bindParam(":student_name", $search_pattern);
            $stmt->bindParam(":student_name_comma", $search_pattern_comma);
            $stmt->bindParam(":student_name_exact", $student_name);
            
            $stmt->execute();
            return $stmt;
        }
        
        return false;
    }

    // Helper method to get student name by ID
    private function getStudentName($student_id) {
        $query = "SELECT student_name FROM students WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['student_name'];
        }
        
        return null;
    }

    // Alternative method if you want to count projects instead of getting full details
    public function getStudentProjectCount($student_id) {
        $query = "SELECT COUNT(*) as project_count FROM " . $this->table_name . " 
                 WHERE students LIKE :student_name 
                 OR students LIKE :student_name_comma 
                 OR students = :student_name_exact";
        
        $stmt = $this->conn->prepare($query);
        
        $student_name = $this->getStudentName($student_id);
        
        if ($student_name) {
            $search_pattern = "%" . $student_name . "%";
            $search_pattern_comma = "%" . $student_name . ",%";
            
            $stmt->bindParam(":student_name", $search_pattern);
            $stmt->bindParam(":student_name_comma", $search_pattern_comma);
            $stmt->bindParam(":student_name_exact", $student_name);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['project_count'];
        }
        
        return 0;
    }
}
?>