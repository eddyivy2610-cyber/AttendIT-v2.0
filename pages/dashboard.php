<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files with error checking
 $requiredFiles = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../model/students.php',
    __DIR__ . '/../model/attendance.php',
    __DIR__ . '/../model/institution.php',
    __DIR__ . '/../model/SummaryService.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file not found: $file");
    }
    include_once $file;
}

// Check if classes exist before using them
 $requiredClasses = ['Database', 'Student', 'Attendance', 'Institution', 'SummaryService'];
foreach ($requiredClasses as $className) {
    if (!class_exists($className)) {
        die("Error: Required class not found: $className");
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("Error: Failed to connect to database");
    }
    
    $student = new Student($db);
    $attendance = new Attendance($db);
    $institution = new Institution($db);
    $summaryService = new SummaryService($db);
    
    // Get dashboard statistics with error handling
    try {
        // Get total active students
        $totalStudentsQuery = "SELECT COUNT(*) as total FROM students WHERE status = 'Active'";
        $totalStmt = $db->prepare($totalStudentsQuery);
        $totalStmt->execute();
        $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
        $totalStudents = $totalResult['total'] ?? 0;
        
        // Get today's summary from SummaryService
        $todaySummary = $summaryService->getTodaysSummary();
        
        // Use the values from the summary service
        $presentCount = $todaySummary['present_count'] ?? 0;
        $lateCount = $todaySummary['late_count'] ?? 0;
        $absentCount = $todaySummary['absent_count'] ?? 0;
        $totalToday = $todaySummary['total_students'] ?? $totalStudents;
        $attendanceRate = $todaySummary['attendance_rate'] ?? 0;
        
        // Calculate active students for display
        $activeStudents = $presentCount + $lateCount;
        
        // Get recently added students
        $recentStudentsQuery = "SELECT student_name, gender, period_of_attachment, created_at FROM students ORDER BY created_at DESC LIMIT 5";
        $recentStmt = $db->prepare($recentStudentsQuery);
        $recentStmt->execute();
        $recentStudents = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get institution overview
        $institutions = $institution->getInstitutionOverview();
        
    } catch (Exception $e) {
        error_log("Dashboard error: " . $e->getMessage());
        // Set default values on error
        $totalStudents = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $activeStudents = 0;
        $attendanceRate = 0;
        $recentStudents = [];
        $institutions = false;
    }
} catch (Exception $e) {
    die("Error initializing application: " . $e->getMessage());
}
?>
<div id="dashboard-page" class="page-content">
    <div class="dashboard-cards">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Total Students</div>
                <div class="card-icon students">
                    <ion-icon name="person-outline"></ion-icon>
                </div>
            </div>
            <div class="card-value"  id="total-students"><?php echo $totalStudents; ?></div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-title">Attendance Rate</div>
                <div class="card-icon attendance">
                    <ion-icon name="calendar-outline"></ion-icon>
                </div>
            </div>
            <div class="card-value" id="attendance-rate"><?php echo $attendanceRate; ?>%</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-title">Active Projects</div>
                <div class="card-icon projects">
                    <ion-icon name="folder-open-outline"></ion-icon>
                </div>
            </div>
            <div class="card-value">36</div>
        </div>
    
        <div class="card">
            <div class="card-header">
                <div class="card-title">Active Students</div>
                <div class="card-icon active-students">
                    <ion-icon name="people-outline"></ion-icon>
                </div>
            </div>
            <div class="card-value" id="active-students"><?php echo $activeStudents; ?></div>
        </div>
    </div>  
        
    <div class="quick-actions">

        <a href="?page=attendance" class="action-card-link">
            <div class="card actions">
                <div class="iconBx actions done">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                </div>
                <div>
                    <div class="numbers actions">Mark Attendance</div>
                    <div class="cardName">Update and manage daily attendance</div>
                </div>
            </div>
        </a>

        <a href="?page=reports" class="action-card-link">
            <div class="card actions">
                <div class="iconBx actions chart">
                    <ion-icon name="stats-chart-outline"></ion-icon>
                </div>
                <div>
                    <div class="numbers actions">View Reports</div>
                    <div class="cardName">Generate and analyze reports</div>
                </div>
            </div>
        </a>
            
        <a href="?page=students" class="action-card-link">
            <div class="card actions">
                <div class="iconBx actions list">
                    <ion-icon name="list-sharp"></ion-icon>
                </div>
                <div>
                    <div class="numbers actions">Manage Students</div>
                    <div class="cardName">View and manage student information</div>
                </div>
            </div>
        </a>
    </div>

    <!-- ================ Recently Added List ================= -->
    <div class="details">
        <div class="recentStudents">
            <div class="cardHeader">
                <h3>Recently Added</h3>
                <a href="?page=students" class="btn">View All</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <td>Image</td>
                        <td>Name</td>
                        <td>Gender</td>
                        <td>POA</td>
                        <td>Joined on</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($recentStudents)) {
                        foreach ($recentStudents as $student) {
                            $joinedDate = !empty($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : '--';
                            $gender = !empty($student['gender']) ? $student['gender'] : '--';
                            $poa = !empty($student['period_of_attachment']) ? $student['period_of_attachment'] . ' months' : '--';
                            
                            echo "
                            <tr>
                                <td width='40px'>
                                    <div class='imgBx'><img src='https://picsum.photos/seed/{$student['student_name']}/40/40.jpg' alt='{$student['student_name']}'></div>
                                </td>
                                <td>{$student['student_name']}</td>
                                <td>{$gender}</td>
                                <td>{$poa}</td>
                                <td>{$joinedDate}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr>
                                <td colspan='5' style='text-align: center; padding: 2rem;'>
                                    <i class='fas fa-user-plus' style='font-size: 2rem; color: #ccc; margin-bottom: 1rem;'></i>
                                    <br>No recently added students
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- ================= Schools Activity ================ -->
        <div class="schoolGrid">
            <div class="cardHeader">
                <h3>Institution Overview</h3>
            </div>
            <div class="institutions-table">
                <?php
                if ($institutions && $institutions->rowCount() > 0) {
                    while($row = $institutions->fetch(PDO::FETCH_ASSOC)) {
                        $studentCount = $row['student_count'] ?? 0;
                        $activeCount = $row['active_count'] ?? 0;
                        echo "
                        <div class='institution-item'>
                            <div class='imgBx'>
                                <img src='https://picsum.photos/seed/{$row['institution_name']}/60/60.jpg' alt='{$row['institution_name']}'>
                            </div>
                            <div class='institution-info'>
                                <h4>{$row['institution_name']}</h4>
                                <span>{$studentCount} Registered | {$activeCount} Active</span>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<div style='text-align: center; padding: 2rem; color: #666;'>
                            <i class='fas fa-university' style='font-size: 2rem; margin-bottom: 1rem;'></i>
                            <br>No institutions found
                          </div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>


</div>

