<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files with error checking
$requiredFiles = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../model/students.php',
    __DIR__ . '/../model/attendance.php',
    __DIR__ . '/../model/performance.php',
    __DIR__ . '/../model/SummaryService.php',
    __DIR__ . '/../model/projects.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file not found: $file");
    }
    include_once $file;
}

// Check if classes exist before using them
$requiredClasses = ['Database', 'Student', 'Performance', 'Attendance', 'SummaryService', 'Project'];
foreach ($requiredClasses as $className) {
    if (!class_exists($className)) {
        die("Error: Required class not found: $className");
    }
}

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$studentData = null;
$performanceData = null;
$attendanceData = null;
$projectCount = 0;
$institution_name = 'N/A';
$student_name = 'Select a Student';
$status = 'Unknown';

// Get all students for the sidebar list
$allStudents = [];
try {
    $studentModel = new Student($db);
    $studentsResult = $studentModel->read();
    if ($studentsResult) {
        while ($row = $studentsResult->fetch(PDO::FETCH_ASSOC)) {
            $allStudents[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error loading students list: " . $e->getMessage());
}

// Initialize SummaryService
$summaryService = new SummaryService($db);

// Initialize student performance and attendance data
$studentPerformance = [];
$studentAttendance = ['total_days' => 0, 'present_days' => 0, 'attendance_rate' => 0];

// Only try to load student data if we have a valid student_id
if($studentId && $studentId > 0) {
    try {
        // Load student data
        $student = new Student($db);
        $student->student_id = $studentId;
        
        if($student->readOne()) {
            $studentData = $student;
            
            // Set variables with proper fallbacks
            $institution_name = !empty($studentData->institution_name) ? $studentData->institution_name : 'N/A';
            $student_name = !empty($studentData->student_name) ? $studentData->student_name : 'Unknown Student';
            $status = !empty($studentData->status) ? $studentData->status : 'Unknown';
            
            // Get student performance summary
            $studentPerformance = $summaryService->getStudentPerformanceSummary($studentId, 30);
            
            // Get student attendance rate
            $studentAttendance = $summaryService->getStudentAttendanceRate($studentId, 30);
            
            // Get project count for this student
            $projectModel = new Project($db);
            $projectsResult = $projectModel->getStudentProjects($studentId);
            if ($projectsResult) {
                $projectCount = $projectsResult->rowCount();
            }
            
            // Load performance data
            $performance = new Performance($db);
            $performanceResult = $performance->getLatestPerformance($studentId);
            if($performanceResult) {
                $performanceData = $performanceResult;
            }
            
            // Load attendance data
            $attendance = new Attendance($db);
            $attendanceResult = $attendance->getStudentAttendance($studentId);
            if($attendanceResult) {
                $attendanceData = $attendanceResult->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        error_log("Error loading student data: " . $e->getMessage());
    }
}

// Handle performance form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_performance') {
    try {
        $performance = new Performance($db);
        
        // Set performance properties
        $performance->student_id = $studentId;
        $performance->evaluated_by = $_SESSION['user_id'] ?? 1;
        $performance->evaluation_date = date('Y-m-d');
        $performance->technical_skill = intval($_POST['technical_skill'] ?? 0);
        $performance->learning_activity = intval($_POST['learning_activity'] ?? 0);
        $performance->active_contribution = intval($_POST['active_contribution'] ?? 0);
        $performance->comments = trim($_POST['comments'] ?? '');
        
        // Create performance record
        if ($performance->create()) {
            // Reload performance data
            $performanceResult = $performance->getLatestPerformance($studentId);
            if($performanceResult) {
                $performanceData = $performanceResult;
            }
            $_SESSION['success_message'] = "Performance rating added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add performance rating.";
        }
    } catch (Exception $e) {
        error_log("Error adding performance data: " . $e->getMessage());
        $_SESSION['error_message'] = "System error. Please try again.";
    }
}

// Display messages from session
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!-- Reports Content -->
<div id="reports-page" class="page-content">
    <div class="reports-layout">
        <!-- Main Content Area -->
        <div class="reports-main">
            <?php if($success_message): ?>
            <div class="reports-alert reports-alert-success">
                <ion-icon name="checkmark-circle"></ion-icon> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
            <div class="reports-alert reports-alert-error">
                <ion-icon name="close-circle"></ion-icon> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if($studentData): ?>
            <!-- Student Header -->
            <div class="reports-student-header">
                <div class="reports-student-basic">
                    <img src="<?php echo !empty($studentData->photo_url) ? htmlspecialchars($studentData->photo_url) : 'https://picsum.photos/seed/' . $studentId . '/80/80.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($student_name); ?>" class="reports-student-avatar">
                    <div class="reports-student-info-compact">
                        <h2><?php echo htmlspecialchars($student_name); ?></h2>
                        <div class="reports-student-meta">
                            <span class="reports-status-badge reports-status-<?php echo htmlspecialchars(strtolower($status)); ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                            <span class="reports-course"><?php echo !empty($studentData->course_of_study) ? htmlspecialchars($studentData->course_of_study) : 'No Course'; ?></span>
                            <span class="reports-institution"><?php echo htmlspecialchars($institution_name); ?></span>
                        </div>
                    </div>
                </div>
                <div class="reports-student-actions">
                    <button class="reports-btn-outline" onclick="reportsExport()">
                        <ion-icon name="download-outline"></ion-icon> Export
                    </button>
                    <button class="reports-btn-primary" onclick="reportsSend()">
                        <ion-icon name="paper-plane-outline"></ion-icon> Send
                    </button>
                </div>
            </div>

           

            <!-- Student Information Grid -->
            <div class="reports-section">
                <div class="reports-section-header">
                    <h3>Student Information</h3>
                </div>
                <div class="reports-info-grid">
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="mail-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Email</label>
                            <span><?php echo !empty($studentData->email) ? htmlspecialchars($studentData->email) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="call-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Phone</label>
                            <span><?php echo !empty($studentData->phone) ? htmlspecialchars($studentData->phone) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Gender</label>
                            <span><?php echo !empty($studentData->gender) ? htmlspecialchars($studentData->gender) : 'N/A'; ?></span>
                        </div>
                    </div>
                    
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="school-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Course</label>
                            <span><?php echo !empty($studentData->course_of_study) ? htmlspecialchars($studentData->course_of_study) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="business-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Institution</label>
                            <span><?php echo htmlspecialchars($institution_name); ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="ribbon-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Skills Interest</label>
                            <span><?php echo !empty($studentData->skill_of_interest) ? htmlspecialchars($studentData->skill_of_interest) : 'N/A'; ?></span>
                        </div>
                    </div>
                    
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Commencement Date</label>
                            <span><?php echo !empty($studentData->join_date) ? date('M j, Y', strtotime($studentData->join_date)) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="flag-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Completion Date</label>
                            <span><?php echo !empty($studentData->end_date) ? date('M j, Y', strtotime($studentData->end_date)) : 'N/A'; ?></span>
                        </div>
                    </div>
                    <div class="reports-info-item">
                        <div class="reports-info-icon">
                            <ion-icon name="time-outline"></ion-icon>
                        </div>
                        <div class="reports-info-details">
                            <label>Program Period</label>
                            <span><?php echo !empty($studentData->period_of_attachment) ? htmlspecialchars($studentData->period_of_attachment) . ' months' : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Stats Overview -->
            <div class="reports-stats-overview">
                <div class="reports-stat-card">
                    <div class="reports-stat-icon">
                        <ion-icon name="calendar-outline"></ion-icon>
                    </div>
                    <div class="reports-stat-content">
                        <div class="reports-stat-value"><?php echo isset($studentAttendance['attendance_rate']) ? $studentAttendance['attendance_rate'] : 0; ?>%</div>
                        <div class="reports-stat-label">Attendance Rate</div>
                    </div>
                </div>
                <div class="reports-stat-card">
                    <div class="reports-stat-icon">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                    </div>
                    <div class="reports-stat-content">
                        <div class="reports-stat-value"><?php echo isset($studentAttendance['present_days']) ? $studentAttendance['present_days'] : 0; ?></div>
                        <div class="reports-stat-label">Days Present</div>
                    </div>
                </div>
                <div class="reports-stat-card">
                    <div class="reports-stat-icon">
                        <ion-icon name="time-outline"></ion-icon>
                    </div>
                    <div class="reports-stat-content">
                        <div class="reports-stat-value"><?php echo isset($studentAttendance['total_days']) && isset($studentAttendance['present_days']) ? $studentAttendance['total_days'] - $studentAttendance['present_days'] : 0; ?></div>
                        <div class="reports-stat-label">Days Late/Absent</div>
                    </div>
                </div>
                <div class="reports-stat-card">
                    <div class="reports-stat-icon">
                        <ion-icon name="trending-up-outline"></ion-icon>
                    </div>
                    <div class="reports-stat-content">
                        <div class="reports-stat-value"><?php echo isset($performanceData['overall_rating']) ? $performanceData['overall_rating'] : 0; ?>%</div>
                        <div class="reports-stat-label">Avg. Performance</div>
                    </div>
                </div>
                <div class="reports-stat-card">
                    <div class="reports-stat-icon">
                        <ion-icon name="folder-outline"></ion-icon>
                    </div>
                    <div class="reports-stat-content">
                        <div class="reports-stat-value"><?php echo $projectCount; ?></div>
                        <div class="reports-stat-label">Projects</div>
                    </div>
                </div>
            </div>

            <!-- Performance Section -->
            <div class="reports-section">
                <div class="reports-section-header">
                    <h3>Performance Metrics</h3>
                    <?php if($performanceData): ?>
                    <span class="reports-last-evaluated">Last: <?php echo !empty($performanceData['evaluation_date']) ? date('M j, Y', strtotime($performanceData['evaluation_date'])) : 'N/A'; ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if($performanceData): ?>
                <div class="reports-performance-grid">
                    <div class="reports-performance-metric">
                        <div class="reports-metric-header">
                            <span class="reports-metric-label">Technical Skills</span>
                            <span class="reports-metric-value"><?php echo htmlspecialchars($performanceData['technical_skill'] ?? 0); ?>%</span>
                        </div>
                        <div class="reports-progress-bar">
                            <div class="reports-progress-fill" style="width: <?php echo htmlspecialchars($performanceData['technical_skill'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                    <div class="reports-performance-metric">
                        <div class="reports-metric-header">
                            <span class="reports-metric-label">Learning Activity</span>
                            <span class="reports-metric-value"><?php echo htmlspecialchars($performanceData['learning_activity'] ?? 0); ?>%</span>
                        </div>
                        <div class="reports-progress-bar">
                            <div class="reports-progress-fill" style="width: <?php echo htmlspecialchars($performanceData['learning_activity'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                    <div class="reports-performance-metric">
                        <div class="reports-metric-header">
                            <span class="reports-metric-label">Active Contribution</span>
                            <span class="reports-metric-value"><?php echo htmlspecialchars($performanceData['active_contribution'] ?? 0); ?>%</span>
                        </div>
                        <div class="reports-progress-bar">
                            <div class="reports-progress-fill" style="width: <?php echo htmlspecialchars($performanceData['active_contribution'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <?php if(!empty($performanceData['comments'])): ?>
                <div class="reports-performance-comments">
                    <strong>Supervisor Comments:</strong>
                    <p><?php echo htmlspecialchars($performanceData['comments']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="reports-no-data">
                    <ion-icon name="stats-chart-outline"></ion-icon>
                    <p>No performance data available yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Compact Evaluation Input -->
            <div class="reports-section reports-compact-form">
                <div class="reports-section-header">
                    <h3>Quick Evaluation</h3>
                </div>
                <form method="POST" id="reports-performanceForm" class="reports-performance-form-compact">
                    <input type="hidden" name="action" value="add_performance">
                    
                    <div class="reports-evaluation-grid">
                        <div class="reports-eval-item">
                            <label class="reports-eval-label">Technical</label>
                            <input type="range" class="reports-eval-range" id="reports-technical_skill" name="technical_skill" min="0" max="100" value="75">
                            <span class="reports-eval-value" id="reports-technical_skill_value">75%</span>
                        </div>
                        
                        <div class="reports-eval-item">
                            <label class="reports-eval-label">Learning</label>
                            <input type="range" class="reports-eval-range" id="reports-learning_activity" name="learning_activity" min="0" max="100" value="75">
                            <span class="reports-eval-value" id="reports-learning_activity_value">75%</span>
                        </div>
                        
                        <div class="reports-eval-item">
                            <label class="reports-eval-label">Contribution</label>
                            <input type="range" class="reports-eval-range" id="reports-active_contribution" name="active_contribution" min="0" max="100" value="75">
                            <span class="reports-eval-value" id="reports-active_contribution_value">75%</span>
                        </div>
                    </div>
                    
                    <div class="reports-eval-comments">
                        <textarea class="reports-form-textarea-small" name="comments" placeholder="Add comments (optional)" rows="2"></textarea>
                        <button type="submit" class="reports-btn-primary reports-btn-compact">
                            <ion-icon name="save-outline"></ion-icon> Save
                        </button>
                    </div>
                </form>
            </div>

            <?php else: ?>
            <!-- No Student Selected -->
            <div class="reports-no-student-selected">
                <div class="reports-empty-state">
                    <ion-icon name="document-text-outline"></ion-icon>
                    <h3>No Student Selected</h3>
                    <p>Select a student from the list to view their reports and performance metrics.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Students Sidebar -->
        <div class="reports-students-sidebar">
            <div class="reports-sidebar-header">
                <h3>Students</h3>
                <div class="reports-search-box">
                    <ion-icon name="search-outline"></ion-icon>
                    <input type="text" id="reports-studentSearch" placeholder="Search students...">
                </div>
            </div>
            
            <div class="reports-students-list" id="reports-studentsList">
                <?php if(!empty($allStudents)): ?>
                    <?php foreach($allStudents as $student): ?>
                        <div class="reports-student-item <?php echo $studentId == $student['student_id'] ? 'reports-active' : ''; ?>" 
                             onclick="window.location.href='?page=reports&student_id=<?php echo $student['student_id']; ?>'">
                            <img src="<?php echo !empty($student['photo_url']) ? htmlspecialchars($student['photo_url']) : 'https://picsum.photos/seed/' . $student['student_id'] . '/40/40.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($student['student_name']); ?>" class="reports-student-avatar-small">
                            <div class="reports-student-info-small">
                                <div class="reports-student-name"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                <div class="reports-student-course"><?php echo htmlspecialchars($student['course_of_study'] ?? 'No Course'); ?></div>
                            </div>
                            <span class="reports-status-indicator reports-status-<?php echo htmlspecialchars(strtolower($student['status'] ?? 'inactive')); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="reports-no-students">
                        <ion-icon name="people-outline"></ion-icon>
                        <p>No students found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize range sliders
    const sliders = ['reports-technical_skill', 'reports-learning_activity', 'reports-active_contribution'];
    
    sliders.forEach(sliderId => {
        const slider = document.getElementById(sliderId);
        const valueDisplay = document.getElementById(sliderId + '_value');
        
        if (slider && valueDisplay) {
            slider.addEventListener('input', function() {
                valueDisplay.textContent = this.value + '%';
            });
        }
    });

    // Student search functionality
    const studentSearch = document.getElementById('reports-studentSearch');
    if (studentSearch) {
        studentSearch.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const studentItems = document.querySelectorAll('.reports-student-item');
            
            studentItems.forEach(item => {
                const studentName = item.querySelector('.reports-student-name').textContent.toLowerCase();
                const studentCourse = item.querySelector('.reports-student-course').textContent.toLowerCase();
                
                if (studentName.includes(searchTerm) || studentCourse.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Performance form submission
    const performanceForm = document.getElementById('reports-performanceForm');
    if (performanceForm) {
        performanceForm.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.innerHTML = '<ion-icon name="refresh-circle"></ion-icon> Saving...';
            submitButton.disabled = true;
        });
    }
});

function reportsExport() {
    reportsShowNotification('Export functionality coming soon!', 'info');
}

function reportsSend() {
    reportsShowNotification('Send report functionality coming soon!', 'info');
}

function reportsShowNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `reports-alert reports-alert-${type}`;
    notification.innerHTML = `<ion-icon name="information-circle"></ion-icon> ${message}`;
    
    // Add to page
    const mainContent = document.querySelector('.reports-main');
    if (mainContent) {
        mainContent.insertBefore(notification, mainContent.firstChild);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Make functions global
window.reportsExport = reportsExport;
window.reportsSend = reportsSend;
</script>