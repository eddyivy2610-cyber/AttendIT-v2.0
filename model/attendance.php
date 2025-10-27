<?php
class Attendance {
    private $conn;
    private $table_name = "attendance";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Sign in a student
    public function signIn($student_id) {
        try {
            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            
            // Check if attendance already exists for today
            $check_query = "SELECT attendance_id FROM " . $this->table_name . " 
                           WHERE student_id = :student_id AND attendance_date = :attendance_date";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":student_id", $student_id);
            $check_stmt->bindParam(":attendance_date", $current_date);
            $check_stmt->execute();
            
            // Determine if late (after 8:30 AM)
            $status = 'Present';
            $late_threshold = strtotime('08:30:00');
            if (strtotime($current_time) > $late_threshold) {
                $status = 'Late';
            }
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing record
                $query = "UPDATE " . $this->table_name . " 
                         SET arrival_time = CONCAT(:attendance_date, ' ', :arrival_time), 
                             status = :status 
                         WHERE student_id = :student_id AND attendance_date = :attendance_date";
            } else {
                // Insert new record
                $query = "INSERT INTO " . $this->table_name . " 
                         SET student_id = :student_id, 
                             attendance_date = :attendance_date, 
                             arrival_time = CONCAT(:attendance_date, ' ', :arrival_time), 
                             status = :status";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->bindParam(":attendance_date", $current_date);
            $stmt->bindParam(":arrival_time", $current_time);
            $stmt->bindParam(":status", $status);
            
            if ($stmt->execute()) {
                // Update daily summary after successful sign in
                $this->updateDailySummary($current_date);
                
                return ['success' => true, 'status' => $status];
            }
            return ['success' => false, 'message' => 'Unable to sign in student'];
            
        } catch (PDOException $e) {
            error_log("Error signing in student: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    // Sign out a student
    public function signOut($student_id) {
        try {
            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            
            $query = "UPDATE " . $this->table_name . " 
                     SET departure_time = CONCAT(:attendance_date, ' ', :departure_time) 
                     WHERE student_id = :student_id AND attendance_date = :attendance_date 
                     AND departure_time IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->bindParam(":attendance_date", $current_date);
            $stmt->bindParam(":departure_time", $current_time);
            
            if ($stmt->execute()) {
                // Update daily summary after successful sign out
                $this->updateDailySummary($current_date);
                
                return ['success' => true];
            }
            return ['success' => false, 'message' => 'Unable to sign out student'];
            
        } catch (PDOException $e) {
            error_log("Error signing out student: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    // Get today's attendance for a specific student
    public function getStudentTodayAttendance($student_id) {
        try {
            $current_date = date('Y-m-d');
            
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE student_id = :student_id AND attendance_date = :attendance_date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->bindParam(":attendance_date", $current_date);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting student attendance: " . $e->getMessage());
            return false;
        }
    }

    // Get today's attendance summary
    public function getTodaysSummary() {
        try {
            $current_date = date('Y-m-d');
            
            $query = "
                SELECT 
                    (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
                    COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
                    COUNT(CASE WHEN a.status = 'Absent' OR a.status IS NULL THEN 1 END) as absent_count
                FROM students s
                LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = :current_date
                WHERE s.status = 'Active'
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_date", $current_date);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: ['total_students' => 0, 'present_count' => 0, 'late_count' => 0, 'absent_count' => 0];
            
        } catch (PDOException $e) {
            error_log("Error getting today's summary: " . $e->getMessage());
            return ['total_students' => 0, 'present_count' => 0, 'late_count' => 0, 'absent_count' => 0];
        }
    }

    // Get attendance data for dashboard (today's percentage)
    public function getTodayAttendancePercentage() {
        try {
            $current_date = date('Y-m-d');
            
            $query = "
                SELECT 
                    COUNT(CASE WHEN a.arrival_time IS NOT NULL THEN 1 END) as present_count,
                    COUNT(s.student_id) as total_students
                FROM students s
                LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = :current_date
                WHERE s.status = 'Active'
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_date", $current_date);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total_students'] > 0) {
                $percentage = round(($result['present_count'] / $result['total_students']) * 100);
            } else {
                $percentage = 0;
            }
            
            return $percentage;
            
        } catch (PDOException $e) {
            error_log("Error getting today's attendance percentage: " . $e->getMessage());
            return 0;
        }
    }

    // Get student attendance history for reports
    public function getStudentAttendanceHistory($student_id, $limit = 30) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE student_id = :student_id 
                     ORDER BY attendance_date DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting student attendance history: " . $e->getMessage());
            return [];
        }
    }

    // Get today's attendance records with student details
    public function getTodaysAttendance() {
        try {
            $current_date = date('Y-m-d');
            
            $query = "
                SELECT s.student_name, s.gender, s.period_of_attachment, 
                       a.status, 
                       TIME(a.arrival_time) as arrival_time,
                       TIME(a.departure_time) as departure_time
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.student_id
                WHERE a.attendance_date = :current_date
                ORDER BY a.arrival_time DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_date", $current_date);
            $stmt->execute();
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Error getting today's attendance: " . $e->getMessage());
            return false;
        }
    }

    // Get institution overview with student counts
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

    // Get monthly attendance summary for reports
    public function getMonthlySummary($year = null, $month = null) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('m');
            
            $start_date = "$year-$month-01";
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $query = "
                SELECT 
                    a.attendance_date,
                    COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
                    COUNT(CASE WHEN a.status = 'Absent' OR a.status IS NULL THEN 1 END) as absent_count,
                    COUNT(s.student_id) as total_students
                FROM students s
                CROSS JOIN (SELECT DISTINCT attendance_date FROM attendance 
                           WHERE attendance_date BETWEEN :start_date AND :end_date) dates
                LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = dates.attendance_date
                WHERE s.status = 'Active'
                GROUP BY a.attendance_date
                ORDER BY a.attendance_date
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting monthly summary: " . $e->getMessage());
            return [];
        }
    }

    // Get student attendance history
    public function getStudentAttendance($student_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE student_id = :student_id 
                     ORDER BY attendance_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->execute();
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Error getting student attendance history: " . $e->getMessage());
            return false;
        }
    }

    // Update daily summary table
    private function updateDailySummary($date) {
        try {
            // Calculate current summary
            $summary = $this->calculateDailySummary($date);
            
            // Insert or update daily_summaries
            $query = "
                INSERT INTO daily_summaries 
                (summary_date, total_students, present_count, late_count, absent_count, attendance_rate)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_students = VALUES(total_students),
                    present_count = VALUES(present_count),
                    late_count = VALUES(late_count),
                    absent_count = VALUES(absent_count),
                    attendance_rate = VALUES(attendance_rate)
            ";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                $date,
                $summary['total_students'],
                $summary['present_count'],
                $summary['late_count'],
                $summary['absent_count'],
                $summary['attendance_rate']
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating daily summary: " . $e->getMessage());
            return false;
        }
    }
    
    // Calculate daily summary from attendance data
    private function calculateDailySummary($date) {
        $query = "
            SELECT 
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
                COUNT(CASE WHEN a.status = 'Absent' OR a.status IS NULL THEN 1 END) as absent_count,
                CASE 
                    WHEN COUNT(DISTINCT s.student_id) > 0 THEN 
                        ROUND((COUNT(CASE WHEN a.status IN ('Present', 'Late') THEN 1 END) / COUNT(DISTINCT s.student_id)) * 100, 2)
                    ELSE 0 
                END as attendance_rate
            FROM students s
            LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = ?
            WHERE s.status = 'Active'
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$date]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $summary ?: [
            'total_students' => 0,
            'present_count' => 0,
            'late_count' => 0,
            'absent_count' => 0,
            'attendance_rate' => 0
        ];
    }

    public function getActiveStudentsByInstitution() {
    try {
        $query = "
            SELECT 
                i.institution_name,
                s.student_id,
                s.student_name,
                s.course_of_study,
                s.gender,
                s.period_of_attachment,
                s.photo_url,
                s.status
            FROM students s
            LEFT JOIN institutions i ON s.institution_id = i.institution_id
            WHERE s.status = 'Active'
            ORDER BY i.institution_name, s.student_name
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("Error getting active students by institution: " . $e->getMessage());
        return false;
    }
}
}
?>