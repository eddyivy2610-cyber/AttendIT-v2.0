<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

 $requiredFiles = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../model/students.php',
    __DIR__ . '/../model/attendance.php',
    __DIR__ . '/../model/SummaryService.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file not found: $file");
    }
    include_once $file;
}

 $requiredClasses = ['Database', 'Student', 'Attendance', 'SummaryService'];
foreach ($requiredClasses as $className) {
    if (!class_exists($className)) {
        die("Error: Required class not found: $className");
    }
}

 $database = new Database();
 $db = $database->getConnection();

// Test database connection
try {
    $testQuery = $db->query("SELECT 1");
    if (!$testQuery) {
        die("Database connection failed");
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

 $attendance = new Attendance($db);
 $student = new Student($db);
 $summaryService = new SummaryService($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received: " . print_r($_POST, true));
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'sign_in':
                if (isset($_POST['student_id'])) {
                    error_log("Processing sign_in for student_id: " . $_POST['student_id']);
                    $result = $attendance->signIn($_POST['student_id']);
                    error_log("Sign in result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = "Student signed in successfully" . 
                                         ($result['status'] === 'Late' ? ' (Late)' : '');
                    } else {
                        $_SESSION['error_message'] = $result['message'] ?? "Failed to sign in student";
                    }
                }
                break;
                
            case 'sign_out':
                if (isset($_POST['student_id'])) {
                    error_log("Processing sign_out for student_id: " . $_POST['student_id']);
                    $result = $attendance->signOut($_POST['student_id']);
                    error_log("Sign out result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = "Student signed out successfully";
                    } else {
                        $_SESSION['error_message'] = $result['message'] ?? "Failed to sign out student";
                    }
                }
                break;
                
            case 'reset_today':
                // Reset all attendance for today
                try {
                    $current_date = date('Y-m-d');
                    error_log("Resetting attendance for date: $current_date");
                    
                    $query = "DELETE FROM attendance WHERE attendance_date = :attendance_date";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":attendance_date", $current_date);
                    
                    if ($stmt->execute()) {
                        $affectedRows = $stmt->rowCount();
                        error_log("Deleted $affectedRows attendance records");
                        
                        // Update daily summary after reset
                        $summaryResult = $summaryService->updateDailySummary($current_date);
                        error_log("Daily summary update result: " . ($summaryResult ? 'success' : 'failure'));
                        
                        $_SESSION['success_message'] = "Today's attendance reset successfully ($affectedRows records deleted)";
                    } else {
                        $_SESSION['error_message'] = "Failed to reset attendance";
                    }
                } catch (Exception $e) {
                    error_log("Error resetting attendance: " . $e->getMessage());
                    $_SESSION['error_message'] = "Error resetting attendance: " . $e->getMessage();
                }
                break;
                
            case 'submit_report':
                // Handle attendance report submission
                try {
                    $current_date = date('Y-m-d');
                    error_log("Submitting attendance report for date: $current_date");
                    
                    // Step 1: Check current attendance records
                    $checkQuery = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ?";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->execute([$current_date]);
                    $attendanceCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
                    error_log("Current attendance records for today: $attendanceCount");
                    
                    // Step 2: Calculate summary from actual data
                    $summaryQuery = "
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
                    
                    $summaryStmt = $db->prepare($summaryQuery);
                    $summaryStmt->execute([$current_date]);
                    $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Calculated summary data: " . print_r($summaryData, true));
                    
                    // Step 3: Update daily summary table
                    $updateQuery = "
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
                    
                    $updateStmt = $db->prepare($updateQuery);
                    $updateResult = $updateStmt->execute([
                        $current_date,
                        $summaryData['total_students'],
                        $summaryData['present_count'],
                        $summaryData['late_count'],
                        $summaryData['absent_count'],
                        $summaryData['attendance_rate']
                    ]);
                    
                    error_log("Daily summary update result: " . ($updateResult ? 'success' : 'failure'));
                    
                    // Step 4: Verify the update
                    $verifyQuery = "SELECT * FROM daily_summaries WHERE summary_date = ?";
                    $verifyStmt = $db->prepare($verifyQuery);
                    $verifyStmt->execute([$current_date]);
                    $verifyData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Verification data: " . print_r($verifyData, true));
                    
                    if ($updateResult && $verifyData) {
                        $_SESSION['success_message'] = "Attendance report submitted successfully! Summary updated with {$verifyData['present_count']} present, {$verifyData['late_count']} late, and {$verifyData['absent_count']} absent.";
                    } else {
                        $_SESSION['error_message'] = "Failed to update daily summary. Check error logs.";
                    }
                } catch (Exception $e) {
                    error_log("Error submitting report: " . $e->getMessage());
                    $_SESSION['error_message'] = "Error submitting report: " . $e->getMessage();
                }
                break;
        }
    
    }
}


 $success_message = $_SESSION['success_message'] ?? null;
 $error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

 $todaySummary = $attendance->getTodaysSummary();
 $allStudents = $student->read();
?>

<!-- Attendance Content -->
<div id="attendance-page" class="page-content">

    <?php if($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Attendance Summary Cards -->
    <div class="attendance-summary">
        <div class="summary-card present" >
            <i class="fas fa-user-check"></i>
            <div>
                <h3 id="present-count"><?php echo $todaySummary['present_count'] ?? 0; ?></h3>
                <p>Present</p>
            </div>
        </div>
        <div class="summary-card absent">
            <i class="fas fa-user-times"></i>
            <div>
                <h3 id="absent-count"><?php echo $todaySummary['absent_count'] ?? 0; ?></h3>
                <p>Absent</p>
            </div>
        </div>
        <div class="summary-card late">
            <i class="fas fa-user-clock"></i>
            <div>
                <h3 id="late-count"><?php echo $todaySummary['late_count'] ?? 0; ?></h3>
                <p>Late</p>
            </div>
        </div>
        <div class="summary-card expected">
            <i class="fas fa-users"></i>
            <div>
                <h3 id="expected-count"><?php echo $todaySummary['total_students'] ?? 0; ?></h3>
                <p>Expected</p>
            </div>
        </div>
    </div>

        <!-- Students Table -->
    <div class="students-container">
        <div class="students-header">
            <h2>Interns (<?php echo $todaySummary['total_students'] ?? 0; ?>)</h2>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search students..." id="searchInput">
            </div>
            <div class="attendance-actions">
                <button class="btn btn-primary" onclick="markAllSignedIn()">
                    <i class="fas fa-check-circle"></i> Mark All Signed In
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_today">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Reset all attendance for today?')">
                        <i class="fas fa-undo"></i> Reset Today
                    </button>
                </form>
            </div>
        </div>
    
        <table class="students-table">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Institution</th>
                    <th>Arrival Time</th>
                    <th>Actions</th>
                    <th>Depart Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serial = 1;
                $currentInstitution = '';
                
                // Get students grouped by institution
                $studentsByInstitution = $attendance->getActiveStudentsByInstitution();
                
                if ($studentsByInstitution) {
                    $studentsByInstitution->execute();
                    $students = $studentsByInstitution->fetchAll(PDO::FETCH_ASSOC);
                    
                    
                    $groupedStudents = [];
                    foreach ($students as $student) {
                        $institution = $student['institution_name'] ?? 'Unknown Institution';
                        $groupedStudents[$institution][] = $student;
                    }
                    
                    foreach ($groupedStudents as $institutionName => $institutionStudents) {
                        
                        echo '
                        <tr class="institution-header">
                            <td colspan="8" class="institution-name">
                                <i class="fas fa-university"></i> ' . htmlspecialchars($institutionName) . 
                                ' <span class="student-count">(' . count($institutionStudents) . ' students)</span>
                            </td>
                        </tr>';
                        
                        // Display students for this institution
                        foreach ($institutionStudents as $studentRow) {
                            
                            $todayAttendance = false;
                            try {
                                $todayAttendance = $attendance->getStudentTodayAttendance($studentRow['student_id']);
                            } catch (Exception $e) {
                                error_log("Error getting today's attendance for student {$studentRow['student_id']}: " . $e->getMessage());
                                $todayAttendance = false;
                            }
                            
                            $isSignedIn = $todayAttendance && $todayAttendance['arrival_time'];
                            $isSignedOut = $todayAttendance && $todayAttendance['departure_time'];
                            $arrivalTime = $isSignedIn ? date('g:i A', strtotime($todayAttendance['arrival_time'])) : '--';
                            $departTime = $isSignedOut ? date('g:i A', strtotime($todayAttendance['departure_time'])) : '--';
                            $status = $todayAttendance ? $todayAttendance['status'] : 'Absent';
                            
                            echo '
                            <tr data-student-id="'.$studentRow['student_id'].'" data-institution="'.htmlspecialchars($institutionName).'">
                                <td class="serial-number">'.$serial.'</td>
                                <td>
                                    <img src="'.(!empty($studentRow['photo_url']) ? htmlspecialchars($studentRow['photo_url']) : 'https://picsum.photos/seed/'.urlencode($studentRow['student_name']).'/50/50.jpg').'" 
                                        alt="'.$studentRow['student_name'].'" class="student-img">
                                </td>
                                <td>
                                    <div class="student-name">'.$studentRow['student_name'].'</div>
                                    <div class="student-gender">'.$studentRow['gender'].'</div>
                                </td>
                                <td class="student-course">'.$studentRow['course_of_study'].'</td>
                                <td class="student-institution">'.htmlspecialchars($institutionName).'</td>
                                <td class="time-display">'.$arrivalTime.'</td>
                                <td>
                                    <div class="action-buttons">';
                                    
                                    // Sign In Button
                                    if (!$isSignedIn) {
                                        echo '<form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="sign_in">
                                                <input type="hidden" name="student_id" value="'.$studentRow['student_id'].'">
                                                <button type="submit" class="btn btn-signin">
                                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                                </button>
                                            </form>';
                                    } else {
                                        echo '<button class="btn '.($status === 'Late' ? 'btn-late' : 'btn-signed').'" disabled>
                                                <i class="fas '.($status === 'Late' ? 'fa-clock' : 'fa-check').'"></i> 
                                                '.$status.'
                                            </button>';
                                    }
                                    
                                    // Sign Out Button
                                    if ($isSignedIn && !$isSignedOut) {
                                        echo '<form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="sign_out">
                                                <input type="hidden" name="student_id" value="'.$studentRow['student_id'].'">
                                                <button type="submit" class="btn btn-signout">
                                                    <i class="fas fa-sign-out-alt"></i> Sign Out
                                                </button>
                                            </form>';
                                    } else if ($isSignedOut) {
                                        echo '<button class="btn btn-signed-out" disabled>
                                                <i class="fas fa-check"></i> Signed Out
                                            </button>';
                                    } else {
                                        echo '<button class="btn btn-signout" disabled>
                                                <i class="fas fa-sign-out-alt"></i> Sign Out
                                            </button>';
                                    }
                                    
                            echo '</div>
                                </td>
                                <td class="time-display">'.$departTime.'</td>
                            </tr>';
                            $serial++;
                        }
                    }
                } else {
                    echo '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No active students found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    
        <!-- Submit Attendance Button -->
        <div class="submit-attendance-container">
            <form method="POST">
                <input type="hidden" name="action" value="submit_report">
                <button type="submit" class="btn btn-submit" onclick="return confirm('Submit today\'s attendance report? This will finalize all records for today.')">
                    <i class="fas fa-paper-plane"></i> Submit Attendance Report
                </button>
            </form> 
        </div>
    </div> 
</div>

<script>


</script>

