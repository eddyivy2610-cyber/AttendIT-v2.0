<?php
class Institution {
    private $conn;
    private $table_name = "institutions";
    public $institution_id;
    public $institution_name;
    public $institution_logo;
    public $contact_email;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all institutions
    public function read() {
        $query = "SELECT i.*, COUNT(s.student_id) as student_count 
                  FROM " . $this->table_name . " i 
                  LEFT JOIN students s ON i.institution_id = s.institution_id 
                  GROUP BY i.institution_id 
                  ORDER BY i.institution_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Add this method to your Institution class
public function getInstitutionOverview() {
    try {
        $query = "
            SELECT i.institution_name, COUNT(s.student_id) as student_count
            FROM institutions i
            LEFT JOIN students s ON i.institution_id = s.institution_id AND s.status = 'Active'
            GROUP BY i.institution_id, i.institution_name
            ORDER BY student_count DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("Error getting institution overview: " . $e->getMessage());
        return false;
    }
}

    // Create new institution
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET institution_name=:institution_name, 
                     contact_email=:contact_email, 
                     institution_logo=:institution_logo";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":institution_name", $this->institution_name);
        $stmt->bindParam(":contact_email", $this->contact_email);
        $stmt->bindParam(":institution_logo", $this->institution_logo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>