<?php
session_start();
require_once 'config.php';
require_once 'utils/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Remove auto-login and check real authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$allowed_pages = ['dashboard', 'students', 'attendance', 'reports', 'settings', 'projects'];
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

$currentDate = date('l, F j, Y');
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendit - Student Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdn.boxicons.com/fonts/brands/boxicons-brands.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/students.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    <link rel="stylesheet" href="assets/css/settings.css">
    <link rel="stylesheet" href="assets/css/projects.css">
 
</head>
<body>
    <div class="container"> 
        <div class="navigation">
            <div>
                <div class="nav-header">
                    <a href="?page=dashboard" class="logo">
                        <img src="1761425618541.png" alt="" >
                        <span>Attendit</span>
                    </a>
                </div>

                <ul class="nav-bar">
                    <li class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                        <a href="?page=dashboard" data-tooltip="Dashboard" data-page="dashboard">
                            <ion-icon name="grid-outline"></ion-icon>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="<?php echo $page == 'students' ? 'active' : ''; ?>">
                        <a href="?page=students" data-tooltip="Students" data-page="students">
                            <ion-icon name="person-outline"></ion-icon>
                            <span>Students</span>
                        </a>
                    </li>

                    <li class="<?php echo $page == 'attendance' ? 'active' : ''; ?>">
                        <a href="?page=attendance" data-tooltip="Attendance" data-page="attendance">
                            <ion-icon name="calendar-number-outline"></ion-icon>
                            <span>Attendance</span>
                        </a>
                    </li>

                    <li class="<?php echo $page == 'projects' ? 'active' : ''; ?>">
                        <a href="?page=projects" data-tooltip="Projects" data-page="projects">
                            <ion-icon name="folder-outline"></ion-icon>
                            <span>Projects</span>
                        </a>
                    </li>

                    <li class="<?php echo $page == 'reports' ? 'active' : ''; ?>">
                        <a  href="?page=reports" data-tooltip="Reports" data-page="reports">
                            <ion-icon name="create-outline"></ion-icon>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-footer">
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="cropped-Latest-LOGO-NCAT-.1.png" alt="user">
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($currentUser['username']) ?></div>
                        <div class="user-role"><?= ucfirst(htmlspecialchars($currentUser['role'])) ?></div>
                    </div>
                </div>

                <ul class="nav-bar down">
                    <li>
                        <div class="theme">
                            <ion-icon name="contrast"></ion-icon>
                            <span id="display">Dark UI</span>
                        </div>
                    </li>
                    <li>
                        <div class="toggle">
                            <i class='bxr bx-arrow-from-right-stroke' id="collapse"></i> 
                            <span>Collapse</span>
                        </div>
                    </li>
                    <li class="<?php echo $page == 'settings' ? 'active' : ''; ?>">
                        <a href="?page=settings" data-tooltip="Settings">
                            <ion-icon name="settings-outline"></ion-icon>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" data-tooltip="Sign Out">
                            <ion-icon name="log-out-outline"></ion-icon>
                            <span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo ucfirst($page); ?></h1>
                <span id="currentDate" class="date"><?php echo $currentDate; ?></span>
            </div>

            <div id="loading-spinner" class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
                <p>Loading data...</p>
            </div>

            <?php
                $page_file = "pages/" . $page . ".php";
                
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo "<div class='error-message'>Page not found: " . htmlspecialchars($page_file) . "</div>";
                    if (file_exists('pages/dashboard.php')) {
                        include 'pages/dashboard.php';
                    }
                }
            ?>
        </main>
    </div>

    <!-- Notification System -->
    <div id="notification">
        <span id="notification-message"></span>
        <button onclick="hideNotification()">×</button>
    </div>
    
    <div id="sidebar-tooltip" class="sidebar-tooltip"></div>
   
    <!-- Scripts -->
    <script src="assets/js/home.js"></script>
    <script src="assets/js/students.js"></script>
    <script src="assets/js/attendance.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/reports.js"></script>
    <script src="assets/js/projects.js"></script>

    <!-- ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>