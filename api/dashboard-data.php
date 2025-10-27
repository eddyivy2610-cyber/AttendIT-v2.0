<?php
header('Content-Type: application/json');

include_once '../config.php';
include_once '../model/SummaryService.php';
include_once '../model/students.php';

$database = new Database();
$db = $database->getConnection();

$summaryService = new SummaryService($db);

try {
    $todaySummary = $summaryService->getTodaysSummary();
    
    // Calculate active students (students who haven't exceeded their program duration)
    $activeStudentsCount = 0;
    try {
        $studentModel = new Student($db);
        $activeStudents = $studentModel->getActiveStudents();
        if ($activeStudents) {
            $activeStudentsCount = $activeStudents->rowCount();
        }
    } catch (Exception $e) {
        error_log("Error counting active students: " . $e->getMessage());
        // Fallback: use present + late count
        $activeStudentsCount = ($todaySummary['present_count'] ?? 0) + ($todaySummary['late_count'] ?? 0);
    }
    
    echo json_encode([
        'totalStudents' => $todaySummary['total_students'] ?? 0,
        'activeStudents' => $activeStudentsCount,
        'absentStudents' => $todaySummary['absent_count'] ?? 0,
        'attendanceRate' => $todaySummary['attendance_rate'] ?? 0
    ]);
} catch (Exception $e) {
    echo json_encode([
        'totalStudents' => 0,
        'activeStudents' => 0,
        'absentStudents' => 0,
        'attendanceRate' => 0
    ]);
}
?>