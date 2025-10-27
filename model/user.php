<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password_hash;
    public $role;
    public $full_name;
    public $profile_picture;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    // User login
    public function login($username, $password) {
        $query = "SELECT user_id, username, email, password_hash, role, full_name 
                  FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :username 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (using password_verify)
            if(password_verify($password, $row['password_hash'])) {
                // Update last login
                $this->updateLastLogin($row['user_id']);
                
                return $row;
            }
        }
        return false;
    }

    // Update last login time
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = CURRENT_TIMESTAMP 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET username=:username, email=:email, password_hash=:password_hash, 
                     role=:role, full_name=:full_name";
        
        $stmt = $this->conn->prepare($query);

        // Hash password
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":full_name", $this->full_name);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if username/email exists
    public function exists() {
        $query = "SELECT user_id FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :email 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>