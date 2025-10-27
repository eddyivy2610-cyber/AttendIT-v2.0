<?php
session_start();
include_once __DIR__ . '/config.php';

if(!isset($_SESSION['user_id'])) {

    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
}

 $allowed_pages = ['dashboard', 'students', 'attendance', 'reports', 'settings', 'about'];
 $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

 $currentDate = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendit - Student Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/students.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    <link rel="stylesheet" href="assets/css/settings.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
    /* Notification Popup Styles */
    #notification {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        display: none;
        animation: slideIn 0.3s ease-in-out;
        transition: opacity 0.3s, transform 0.3s;
    }

    #notification.show {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #notification button {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        margin-left: 15px;
        opacity: 0.8;
    }

    #notification button:hover {
        opacity: 1;
    }

    #notification.info {
        background-color: #3498db;
    }

    #notification.success {
        background-color: #27ae60;
    }

    #notification.error {
        background-color: #e74c3c;
    }

    #notification.warning {
        background-color: #f39c12;
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }

    .fade-out {
        animation: slideOut 0.3s ease-in-out forwards;
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Sidebar -->
        <div class="navigation">
            <ul>
                <li>
                    <a href="?page=dashboard">
                        <span class="icon">
                            <ion-icon name="shield-checkmark"></ion-icon>
                        </span>
                        <h3 class="title">Attendit</h3>
                    </a>
                </li>

                <li class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                    <a href="?page=dashboard" data-tooltip="Dashboard" data-page="dashboard">
                        <span class="icon">
                            <ion-icon name="grid-outline"></ion-icon>
                        </span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo $page == 'students' ? 'active' : ''; ?>">
                    <a href="?page=students" data-tooltip="Students" data-page="students">
                        <span class="icon">
                            <ion-icon name="school-outline"></ion-icon>
                        </span>
                        <span class="title">Students</span>
                    </a>
                </li>

                <li class="<?php echo $page == 'attendance' ? 'active' : ''; ?>">
                    <a href="?page=attendance" data-tooltip="Attendance" data-page="attendance">
                        <span class="icon">
                            <ion-icon name="calendar-number-outline"></ion-icon>
                        </span>
                        <span class="title">Attendance</span>
                    </a>
                </li>

                <li class="<?php echo $page == 'reports' ? 'active' : ''; ?>">
                    <a href="?page=reports" data-tooltip="Reports" data-page="reports">
                        <span class="icon">
                            <ion-icon name="document-text-outline"></ion-icon>
                        </span>
                        <span class="title">Reports</span>
                    </a>
                </li>

                <li class="<?php echo $page == 'about' ? 'active' : ''; ?>">
                    <a href="?page=about" data-tooltip="About" data-page="about">
                        <span class="icon">
                            <ion-icon name="information-circle-outline"></ion-icon>
                        </span>
                        <span class="title">About</span>
                    </a>
                </li>

                <li class="down <?php echo $page == 'settings' ? 'active' : ''; ?>">
                    <a href="?page=settings" data-tooltip="Settings" data-page="settings">
                        <span class="icon">
                            <ion-icon name="settings-outline"></ion-icon>
                        </span>
                        <span class="title">Settings</span>
                    </a>
                </li>

                <li class="down">
                    <a href="logout.php" data-tooltip="Sign Out">
                        <span class="icon">
                            <ion-icon name="log-out-outline" style="color: rgb(255, 60, 60);"></ion-icon>
                        </span>
                        <span class="title" style="color:rgb(255, 60, 60);">Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <main class="main">
            
            <!-- Topbar -->
            <div class="topbar">
                <div class="toggle">
                   <ion-icon name="menu-outline"></ion-icon>
                </div>

                <div class="search">
                    <label>
                        <input type="text" placeholder="Search here" id="globalSearch">
                        <ion-icon name="search-outline"></ion-icon>
                    </label>
                </div>

                <div class="user">
                    <img src="assets/imgs/customer01.jpg" alt="User Profile">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title"><?php echo ucfirst($page); ?></h1>
                <span id="currentDate"><?php echo $currentDate; ?></span>
            </div>

            <!-- Content Area -->
            <div class="app-container" id="content-area">
                <!-- Loading Spinner -->
                <div id="loading-spinner" class="loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading data...</p>
                </div>

                <!-- Loading page: <?php echo htmlspecialchars($page); ?> -->
                
                <!-- Dynamic Content Based on Page -->
                <?php
                $page_file = "pages/" . $page . ".php";
                
                if (file_exists($page_file)) {
                    include $page_file;
                    echo "<!-- Page loaded successfully: " . htmlspecialchars($page_file) . " -->";
                } else {
                    echo "<div class='error-message'>Page not found: " . htmlspecialchars($page_file) . "</div>";
                    // List available pages for debugging
                    $available_pages = glob("pages/*.php");
                    echo "<div class='error-message'>Available pages: " . implode(", ", $available_pages) . "</div>";
                    
                    // Fallback to dashboard
                    if (file_exists('pages/dashboard.php')) {
                        include 'pages/dashboard.php';
                    }
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Notification System -->
    <div id="notification">
        <span id="notification-message"></span>
        <button onclick="hideNotification()">×</button>
    </div>

    <div id="sidebar-tooltip" class="sidebar-tooltip"></div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <!-- Simple, Robust JavaScript -->
    <script>
    // Prevent multiple initializations
    let pageInitialized = false;

    document.addEventListener('DOMContentLoaded', function() {
        if (pageInitialized) {
            console.log('Page already initialized, skipping');
            return;
        }
        pageInitialized = true;
        
        console.log('DOM loaded, initializing page');
        
        // Initialize menu toggle
        initMenuToggle();
        
        // Set current date
        updateCurrentDate();
        
        // Initialize the current page
        initializeCurrentPage();
        
        // Setup navigation
        setupNavigation();
        
        console.log('Page initialization complete');
    });

    // ===== MENU TOGGLE =====
    function initMenuToggle() {
        const toggle = document.querySelector('.toggle');
        const navigation = document.querySelector('.navigation');
        const main = document.querySelector('.main');
        
        if (toggle && navigation && main) {
            console.log('Setting up menu toggle');
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Menu toggle clicked');
                
                navigation.classList.toggle('active');
                main.classList.toggle('active');
                
                // Update toggle icon
                const icon = toggle.querySelector('ion-icon');
                if (icon) {
                    if (navigation.classList.contains('active')) {
                        icon.setAttribute('name', 'close-outline');
                    } else {
                        icon.setAttribute('name', 'menu-outline');
                    }
                }
            });
            
            // Close menu when clicking on main content
            main.addEventListener('click', function(e) {
                if (navigation.classList.contains('active') && window.innerWidth < 768) {
                    navigation.classList.remove('active');
                    main.classList.remove('active');
                    const icon = toggle.querySelector('ion-icon');
                    if (icon) {
                        icon.setAttribute('name', 'menu-outline');
                    }
                }
            });
        } else {
            console.log('Menu toggle elements not found:', {toggle, navigation, main});
        }
    }

    // Simple date update
    function updateCurrentDate() {
        const currentDateElement = document.getElementById('currentDate');
        if (currentDateElement) {
            currentDateElement.textContent = new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }

    // Initialize the current page
    function initializeCurrentPage() {
        const currentPage = '<?php echo $page; ?>';
        console.log('Initializing page:', currentPage);
        
        // Set active navigation
        setActiveNav(currentPage);
        
        // Initialize page-specific functionality
        initPageFunctionality(currentPage);
    }

    // Set active navigation
    function setActiveNav(page) {
        console.log('Setting active nav for:', page);
        // Remove active class from all
        document.querySelectorAll('.navigation li').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to current
        const activeLink = document.querySelector(`.navigation a[data-page="${page}"]`);
        if (activeLink && activeLink.parentElement) {
            activeLink.parentElement.classList.add('active');
            console.log('Active nav set to:', page);
        } else {
            console.log('Active link not found for page:', page);
        }
    }

    // Initialize page-specific functionality
    function initPageFunctionality(page) {
        console.log('Initializing functionality for:', page);
        switch(page) {
            case 'students':
                initStudents();
                break;
            case 'attendance':
                initAttendance();
                break;
            case 'reports':
                initReports();
                break;
            case 'dashboard':
                initDashboard();
                break;
            case 'settings':
                initSettings();
                break;
            case 'about':
                initAbout();
                break;
            default:
                console.log('No specific initialization for page:', page);
        }
    }

    // ===== NAVIGATION =====
    function setupNavigation() {
        console.log('Setting up navigation');
        
        // Handle navigation clicks safely
        document.addEventListener('click', function(e) {
            // Navigation links in sidebar
            const navLink = e.target.closest('.navigation a[data-page]');
            if (navLink) {
                e.preventDefault();
                e.stopPropagation();
                const page = navLink.getAttribute('data-page');
                console.log('Navigation link clicked:', page);
                navigateTo(page);
                return;
            }
            
            // Regular links with ?page= in href
            const pageLink = e.target.closest('a[href*="?page="]');
            if (pageLink) {
                e.preventDefault();
                e.stopPropagation();
                const href = pageLink.getAttribute('href');
                const pageMatch = href.match(/[?&]page=([^&]*)/);
                if (pageMatch && pageMatch[1]) {
                    console.log('Page link clicked:', pageMatch[1]);
                    navigateTo(pageMatch[1]);
                }
                return;
            }
        });
    }

    // Simple navigation that reloads the page
    function navigateTo(page) {
        console.log('Navigating to:', page);
        showLoading();
        
        // Small delay to show loading spinner
        setTimeout(function() {
            window.location.href = `?page=${page}`;
        }, 100);
    }

    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type = 'info') {
        console.log('Notification:', message, type);
        
        let notification = document.getElementById('notification');
        let messageEl = document.getElementById('notification-message');
        
        if (!notification || !messageEl) {
            console.log('Creating notification element');
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.innerHTML = `
                <span id="notification-message"></span>
                <button onclick="hideNotification()">×</button>
            `;
            document.body.appendChild(notification);
            messageEl = document.getElementById('notification-message');
        }
        
        if (messageEl) {
            messageEl.textContent = message;
        }
        
        // Set color based on type
        const colors = {
            info: '#3498db',
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12'
        };
        notification.style.backgroundColor = colors[type] || colors.info;
        notification.className = type; // Add type as class for styling
        
        // Show notification
        notification.style.display = 'flex';
        notification.classList.add('show');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideNotification();
        }, 5000);
    }

    function hideNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.classList.add('fade-out');
            setTimeout(function() {
                notification.style.display = 'none';
                notification.classList.remove('show', 'fade-out');
            }, 300);
        }
    }

    // ===== LOADING FUNCTIONS =====
    function showLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'flex';
        }
    }

    function hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    // ===== PAGE-SPECIFIC INITIALIZERS =====
    function initStudents() {
        console.log('Initializing Students page');
        
        // View toggle
        const listViewBtn = document.getElementById('list-view-btn');
        const gridViewBtn = document.getElementById('grid-view-btn');
        const listView = document.getElementById('students-list-view');
        const gridView = document.getElementById('students-grid-view');

        if (listViewBtn && gridViewBtn && listView && gridView) {
            listViewBtn.addEventListener('click', function() {
                listViewBtn.classList.add('active');
                gridViewBtn.classList.remove('active');
                listView.style.display = 'block';
                gridView.style.display = 'none';
            });

            gridViewBtn.addEventListener('click', function() {
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                gridView.style.display = 'grid';
                listView.style.display = 'none';
            });
        }

        // Year pagination
        document.querySelectorAll('.year-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.year-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                showNotification(`Showing students from ${this.textContent}`, 'info');
            });
        });

        // Scroll to bottom functionality
        const scrollToBottomBtn = document.getElementById('scroll-to-bottom-btn');
        const content = document.querySelector('.students-container, .content');

        if (scrollToBottomBtn && content) {
            scrollToBottomBtn.addEventListener('click', function() {
                content.scrollTo({
                    top: content.scrollHeight,
                    behavior: 'smooth'
                });
            });

            // Hide/show scroll button based on scroll position
            if (content.addEventListener) {
                content.addEventListener('scroll', function() {
                    scrollToBottomBtn.style.display = content.scrollTop > 100 ? 'block' : 'none';
                });
            }
        }

        // Student actions
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const studentElement = this.closest('.student-row, .student-card');
                const studentName = studentElement?.querySelector('.student-name')?.textContent;
                
                if (!studentName) return;
                
                if (this.querySelector('.fa-edit')) {
                    showNotification(`Edit student: ${studentName}`, 'info');
                } else if (this.querySelector('.fa-trash-alt')) {
                    if (confirm(`Are you sure you want to delete ${studentName}?`)) {
                        showNotification(`Deleted student: ${studentName}`, 'success');
                    }
                } else if (this.querySelector('.fa-file-alt')) {
                    showNotification(`View report for: ${studentName}`, 'info');
                }
            });
        });
        
        console.log('Students page initialized successfully');
    }
    
    // Function to view student report - redirects to reports page with student ID
    function viewStudentReport(studentId) {
        console.log('Viewing report for student:', studentId);
        showNotification('Loading student report...', 'info');
        
        // Redirect to reports page with student_id parameter
        window.location.href = `?page=reports&student_id=${studentId}`;
    }

    // Make it globally available
    window.viewStudentReport = viewStudentReport;

    function initAttendance() {
        console.log('Initializing Attendance page');
        
        // Search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.students-table tbody tr');
                
                rows.forEach(row => {
                    const studentName = row.querySelector('.student-name')?.textContent.toLowerCase();
                    row.style.display = studentName?.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // Sign in/out buttons
        document.querySelectorAll('.btn-signin').forEach(btn => {
            btn.addEventListener('click', handleSignIn);
        });
        
        document.querySelectorAll('.btn-signout').forEach(btn => {
            btn.addEventListener('click', handleSignOut);
        });
        
        // Batch operations buttons
        const markAllBtn = document.querySelector('button[onclick*="markAllSignedIn"]');
        const resetAllBtn = document.querySelector('button[onclick*="resetAllAttendance"]');
        
        if (markAllBtn) {
            markAllBtn.addEventListener('click', markAllSignedIn);
        }
        if (resetAllBtn) {
            resetAllBtn.addEventListener('click', resetAllAttendance);
        }
        
        console.log('Attendance page initialized successfully');
    }

    function initReports() {
        console.log('Initializing Reports page');
        // Reports page initialization will go here
    }

    function initDashboard() {
        console.log('Initializing Dashboard page');
        // Dashboard page initialization will go here
    }

    function initSettings() {
        console.log('Initializing Settings page');
        // Settings page initialization will go here
    }

    function initAbout() {
        console.log('Initializing About page');
        // About page initialization will go here
    }

    // ===== ATTENDANCE FUNCTIONS =====
    function handleSignIn(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.currentTarget;
        const row = button.closest('tr');
        if (!row) return;
        
        const arrivalTimeCell = row.querySelector('td:nth-child(4)');
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
        
        if (arrivalTimeCell) {
            arrivalTimeCell.textContent = timeString;
        }
        
        // Check if late (after 8:30 AM)
        const lateTime = new Date();
        lateTime.setHours(8, 30, 0, 0);
        
        if (now > lateTime) {
            button.className = 'btn btn-late';
            button.innerHTML = '<i class="fas fa-clock"></i> Late';
        } else {
            button.className = 'btn btn-signed';
            button.innerHTML = '<i class="fas fa-check"></i> Signed In';
        }
        
        button.disabled = true;
        button.removeEventListener('click', handleSignIn);
        
        // Enable sign out button
        const signOutButton = row.querySelector('.btn-signout');
        if (signOutButton) {
            signOutButton.disabled = false;
            signOutButton.addEventListener('click', handleSignOut);
        }
        
        updateAttendanceCounts();
        showNotification('Student signed in successfully', 'success');
    }

    function handleSignOut(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.currentTarget;
        const row = button.closest('tr');
        if (!row) return;
        
        const departTimeCell = row.querySelector('td:nth-child(6)');
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
        
        if (departTimeCell) {
            departTimeCell.textContent = timeString;
        }
        
        button.disabled = true;
        button.className = 'btn btn-signed-out';
        button.innerHTML = '<i class="fas fa-check"></i> Signed Out';
        button.removeEventListener('click', handleSignOut);
        
        updateAttendanceCounts();
        showNotification('Student signed out successfully', 'success');
    }

    function markAllSignedIn() {
        if (confirm('Mark all students as signed in?')) {
            const rows = document.querySelectorAll('.students-table tbody tr');
            rows.forEach(row => {
                const signInBtn = row.querySelector('.btn-signin');
                if (signInBtn && !signInBtn.disabled) {
                    // Create a mock event object
                    const mockEvent = {
                        preventDefault: function() {},
                        stopPropagation: function() {},
                        currentTarget: signInBtn
                    };
                    handleSignIn(mockEvent);
                }
            });
        }
    }

    function resetAllAttendance() {
        if (confirm('Reset all attendance for today?')) {
            const rows = document.querySelectorAll('.students-table tbody tr');
            rows.forEach(row => {
                const arrivalCell = row.querySelector('td:nth-child(4)');
                const departCell = row.querySelector('td:nth-child(6)');
                const signInBtn = row.querySelector('.btn-signin, .btn-signed, .btn-late');
                const signOutBtn = row.querySelector('.btn-signout, .btn-signed-out');
                
                if (arrivalCell) arrivalCell.textContent = '--';
                if (departCell) departCell.textContent = '--';
                
                if (signInBtn) {
                    signInBtn.disabled = false;
                    signInBtn.className = 'btn btn-signin';
                    signInBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                    signInBtn.addEventListener('click', handleSignIn);
                }
                
                if (signOutBtn) {
                    signOutBtn.disabled = true;
                    signOutBtn.className = 'btn btn-signout';
                    signOutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sign Out';
                }
            });
            
            updateAttendanceCounts();
            showNotification('Attendance reset successfully', 'success');
        }
    }

    function updateAttendanceCounts() {
        const totalStudents = document.querySelectorAll('.students-table tbody tr').length;
        const presentStudents = document.querySelectorAll('.btn-signed').length;
        const lateStudents = document.querySelectorAll('.btn-late').length;
        const absentStudents = totalStudents - presentStudents - lateStudents;
        
        // Update summary cards
        const presentElement = document.querySelector('.summary-card.present h3');
        const absentElement = document.querySelector('.summary-card.absent h3');
        const lateElement = document.querySelector('.summary-card.late h3');
        const expectedElement = document.querySelector('.summary-card.expected h3');
        
        if (presentElement) presentElement.textContent = presentStudents;
        if (absentElement) absentElement.textContent = absentStudents;
        if (lateElement) lateElement.textContent = lateStudents;
        if (expectedElement) expectedElement.textContent = totalStudents;
    }

    // Make essential functions global
    window.navigateTo = navigateTo;
    window.showNotification = showNotification;
    window.hideNotification = hideNotification;
    window.handleSignIn = handleSignIn;
    window.handleSignOut = handleSignOut;
    window.markAllSignedIn = markAllSignedIn;
    window.resetAllAttendance = resetAllAttendance;
    </script>
</body>
</html>