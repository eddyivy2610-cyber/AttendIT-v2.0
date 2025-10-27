<?php
class Performance {
    private $conn;
    private $table_name = "performance_metrics";

    public $metric_id;
    public $student_id;
    public $evaluated_by;
    public $evaluation_date;
    public $technical_skill;
    public $learning_activity;
    public $active_contribution;
    public $overall_rating;
    public $comments;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add performance evaluation
    public function create() {
        // Calculate overall rating
        $this->overall_rating = ($this->technical_skill + $this->learning_activity + $this->active_contribution) / 3;

        $query = "INSERT INTO " . $this->table_name . " 
                 SET student_id=:student_id, evaluated_by=:evaluated_by, 
                     evaluation_date=:evaluation_date, technical_skill=:technical_skill, 
                     learning_activity=:learning_activity, active_contribution=:active_contribution, 
                     overall_rating=:overall_rating, comments=:comments";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":evaluated_by", $this->evaluated_by);
        $stmt->bindParam(":evaluation_date", $this->evaluation_date);
        $stmt->bindParam(":technical_skill", $this->technical_skill);
        $stmt->bindParam(":learning_activity", $this->learning_activity);
        $stmt->bindParam(":active_contribution", $this->active_contribution);
        $stmt->bindParam(":overall_rating", $this->overall_rating);
        $stmt->bindParam(":comments", $this->comments);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get student performance history
    public function getStudentPerformance($student_id) {
        $query = "SELECT p.*, u.full_name as evaluator_name 
                  FROM " . $this->table_name . " p 
                  JOIN users u ON p.evaluated_by = u.user_id 
                  WHERE p.student_id = :student_id 
                  ORDER BY p.evaluation_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();
        return $stmt;
    }

    // Get latest performance for a student
    public function getLatestPerformance($student_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE student_id = :student_id 
                  ORDER BY evaluation_date DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>