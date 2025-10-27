<?php
class SummaryService {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get today's summary - tries cache first, then calculates
    public function getTodaysSummary() {
        try {
            $today = date('Y-m-d');
            
            // First try to get from daily_summaries
            $query = "SELECT * FROM daily_summaries WHERE summary_date = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$today]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // If not in cache, calculate from raw data
            return $this->calculateDailySummary($today);
            
        } catch (PDOException $e) {
            error_log("Error getting today's summary: " . $e->getMessage());
            return $this->getEmptySummary();
        }
    }

    // Calculate summary from attendance data
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
        
        return $summary ?: $this->getEmptySummary();
    }

    // Update daily summary (call this when submitting attendance)
    public function updateDailySummary($date) {
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
            $result = $stmt->execute([
                $date,
                $summary['total_students'],
                $summary['present_count'],
                $summary['late_count'],
                $summary['absent_count'],
                $summary['attendance_rate']
            ]);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error updating daily summary: " . $e->getMessage());
            return false;
        }
    }

    // Get student attendance summary
    public function getStudentAttendanceRate($student_id, $days = 30) {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) as present_days,
                    ROUND((COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) / GREATEST(COUNT(*), 1)) * 100, 2) as attendance_rate
                FROM attendance
                WHERE student_id = ? 
                AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$student_id, $days]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_days' => 0, 'present_days' => 0, 'attendance_rate' => 0];
            
        } catch (PDOException $e) {
            error_log("Error getting student attendance: " . $e->getMessage());
            return ['total_days' => 0, 'present_days' => 0, 'attendance_rate' => 0];
        }
    }

    // Get student performance summary
    public function getStudentPerformanceSummary($student_id, $days = 30) {
        try {
            $query = "
                SELECT 
                    AVG(technical_skill) as avg_technical_skill,
                    AVG(learning_activity) as avg_learning_activity,
                    AVG(active_contribution) as avg_active_contribution,
                    AVG(overall_rating) as avg_overall_rating,
                    COUNT(*) as evaluation_count
                FROM performance_metrics
                WHERE student_id = ? 
                AND evaluation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$student_id, $days]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'avg_technical_skill' => 0,
                'avg_learning_activity' => 0,
                'avg_active_contribution' => 0,
                'avg_overall_rating' => 0,
                'evaluation_count' => 0
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting student performance: " . $e->getMessage());
            return [
                'avg_technical_skill' => 0,
                'avg_learning_activity' => 0,
                'avg_active_contribution' => 0,
                'avg_overall_rating' => 0,
                'evaluation_count' => 0
            ];
        }
    }

    private function getEmptySummary() {
        return [
            'total_students' => 0,
            'present_count' => 0,
            'late_count' => 0,
            'absent_count' => 0,
            'attendance_rate' => 0
        ];
    }
}
?>