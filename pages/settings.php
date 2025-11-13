<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error-message">Please log in to access settings.</div>';
    return;
}

// Get current user info
$current_user = [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['user_email'],
    'role' => $_SESSION['role']
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = updateUserProfile($_POST, $current_user['user_id']);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            // Update session with new username if changed
            if (isset($_POST['username'])) {
                $_SESSION['username'] = $_POST['username'];
            }
        } else {
            $_SESSION['error'] = $result['message'];
        }
        header("Location: ?page=settings");
        exit();
    }
    
    if (isset($_POST['update_preferences'])) {
        $result = updateUserPreferences($_POST, $current_user['user_id']);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        header("Location: ?page=settings");
        exit();
    }
}

function updateUserProfile($data, $user_id) {
    try {
        include_once '../config.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "UPDATE users SET username = :username, email = :email WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        }
        return ['success' => false, 'message' => 'Failed to update profile.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

function updateUserPreferences($data, $user_id) {
  
    return ['success' => true, 'message' => 'Preferences updated successfully!'];
}
?>

<div class="settings-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <ion-icon name="checkmark-circle"></ion-icon>
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <ion-icon name="close-circle"></ion-icon>
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-layout">
        <!-- Sidebar Navigation -->
        <div class="settings-sidebar">
            <div class="sidebar-section">
                <h3>User Settings</h3>
                <nav class="sidebar-nav">
                    <a href="#profile" class="nav-item active" data-tab="profile">
                        <ion-icon name="person-outline"></ion-icon>
                        Profile Information
                    </a>
                    <a href="#security" class="nav-item" data-tab="security">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        Security & Password
                    </a>
                    <a href="#notifications" class="nav-item" data-tab="notifications">
                        <ion-icon name="notifications-outline"></ion-icon>
                        Notifications
                    </a>
                </nav>
            </div>
            
            <div class="sidebar-section">
                <h3>System Settings</h3>
                <nav class="sidebar-nav">
                    <a href="#preferences" class="nav-item" data-tab="preferences">
                        <ion-icon name="settings-outline"></ion-icon>
                        App Preferences
                    </a>
                    <a href="#system-info" class="nav-item" data-tab="system-info">
                        <ion-icon name="information-circle-outline"></ion-icon>
                        System Information
                    </a>
                    <a href="#backup" class="nav-item" data-tab="backup">
                        <ion-icon name="cloud-download-outline"></ion-icon>
                        Backup & Export
                    </a>
                </nav>
            </div>

           
            <div class="sidebar-section">
                <h3>About</h3>
                <nav class="sidebar-nav">
                    <a href="#credits" class="nav-item" data-tab="credits">
                        <ion-icon name="heart-outline"></ion-icon>
                        Project Credits
                    </a>
                </nav>
            </div>  

        </div>

        <!-- Main Content -->
        <div class="settings-content">
            <!-- Profile Information Tab -->
            <div id="profile" class="settings-tab active">
                <div class="tab-header">
                    <h2>Profile Information</h2>
                    <p>Update your personal information and account details</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" 
                                       value="<?= htmlspecialchars($current_user['username']) ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?= htmlspecialchars($current_user['email']) ?>" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Additional Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" 
                                       placeholder="Enter your full name">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" name="department" 
                                       placeholder="e.g., Computer Science">
                            </div>
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" name="position" 
                                       placeholder="e.g., Senior Supervisor">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-secondary">Reset Changes</button>
                        <button type="submit" class="btn-primary">
                            <ion-icon name="save-outline"></ion-icon>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security & Password Tab -->
            <div id="security" class="settings-tab">
                <div class="tab-header">
                    <h2>Security & Password</h2>
                    <p>Manage your password and security settings</p>
                </div>
                
                <form class="settings-form">
                    <div class="form-section">
                        <h3>Change Password</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <div class="password-strength">
                                    <div class="strength-bar"></div>
                                    <span class="strength-text">Password strength</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Security Preferences</h3>
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Two-Factor Authentication</h4>
                                <p>Add an extra layer of security to your account</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Login Notifications</h4>
                                <p>Get notified when someone logs into your account</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-primary">
                            <ion-icon name="key-outline"></ion-icon>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications Tab -->
            <div id="notifications" class="settings-tab">
                <div class="tab-header">
                    <h2>Notification Preferences</h2>
                    <p>Choose how and when you want to be notified</p>
                </div>
                
                <form class="settings-form">
                    <div class="form-section">
                        <h3>Email Notifications</h3>
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Project Updates</h4>
                                <p>Get notified when projects are updated or completed</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Student Registrations</h4>
                                <p>Receive alerts when new students register</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>System Announcements</h4>
                                <p>Important updates about the platform</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>In-App Notifications</h3>
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Desktop Notifications</h4>
                                <p>Show notifications on your desktop</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Sound Alerts</h4>
                                <p>Play sound for important notifications</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-primary">
                            <ion-icon name="notifications-outline"></ion-icon>
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>

            <!-- App Preferences Tab -->
            <div id="preferences" class="settings-tab">
                <div class="tab-header">
                    <h2>Application Preferences</h2>
                    <p>Customize your experience with the application</p>
                </div>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="update_preferences" value="1">
                    
                    <div class="form-section">
                        <h3>Interface Preferences</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="theme">Theme</label>
                                <select id="themeSelect" name="theme">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            
                            <div class="form-group">
                                <label for="date_format">Date Format</label>
                                <select id="date_format" name="date_format">
                                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Dashboard Preferences</h3>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Show Recent Activity</h4>
                                <p>Display recent activity on dashboard</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-info">
                                <h4>Auto-refresh Data</h4>
                                <p>Automatically refresh data every 5 minutes</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <ion-icon name="save-outline"></ion-icon>
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Information Tab -->
            <div id="system-info" class="settings-tab">
                <div class="tab-header">
                    <h2>System Information</h2>
                    <p>Technical details about your installation</p>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon">
                            <ion-icon name="desktop-outline"></ion-icon>
                        </div>
                        <div class="info-content">
                            <h3>Application</h3>
                            <div class="info-item">
                                <span class="label">Version:</span>
                                <span class="value">Attendit v2.1.0</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Environment:</span>
                                <span class="value">Local Host</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Last Updated:</span>
                                <span class="value"><?= date('M j, Y') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <ion-icon name="server-outline"></ion-icon>
                        </div>
                        <div class="info-content">
                            <h3>Server</h3>
                            <div class="info-item">
                                <span class="label">PHP Version:</span>
                                <span class="value"><?= phpversion() ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Database:</span>
                                <span class="value">MySQL</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Web Server:</span>
                                <span class="value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <ion-icon name="stats-chart-outline"></ion-icon>
                        </div>
                        <div class="info-content">
                            <h3>Statistics</h3>
                            <div class="info-item">
                                <span class="label">Total Students:</span>
                                <span class="value">247</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Active Projects:</span>
                                <span class="value">18</span>
                            </div>
                            <div class="info-item">
                                <span class="label">System Uptime:</span>
                                <span class="value">99.8%</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <ion-icon name="shield-checkmark-outline"></ion-icon>
                        </div>
                        <div class="info-content">
                            <h3>Security</h3>
                            <div class="info-item">
                                <span class="label">SSL Certificate:</span>
                                <span class="value status-success">Valid</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Firewall:</span>
                                <span class="value status-success">Active</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Last Security Scan:</span>
                                <span class="value"><?= date('M j, Y') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup & Export Tab -->
            <div id="backup" class="settings-tab">
                <div class="tab-header">
                    <h2>Backup & Export</h2>
                    <p>Manage your data backups and exports</p>
                </div>
                
                <div class="backup-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <ion-icon name="download-outline"></ion-icon>
                        </div>
                        <div class="action-content">
                            <h3>Export Data</h3>
                            <p>Download your data in various formats</p>
                            <div class="action-buttons">
                                <button class="btn-outline">
                                    <ion-icon name="document-outline"></ion-icon>
                                    JSON Export
                                </button>
                                <button class="btn-outline">
                                    <ion-icon name="document-attach-outline"></ion-icon>
                                    PDF Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                        </div>
                        <div class="action-content">
                            <h3>Backup Database</h3>
                            <p>Create a full backup of your database</p>
                            <div class="action-buttons">
                                <button class="btn-primary">
                                    <ion-icon name="server-outline"></ion-icon>
                                    Create Backup
                                </button>
                                <button class="btn-outline">
                                    <ion-icon name="time-outline"></ion-icon>
                                    Schedule Auto-backup
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon">
                            <ion-icon name="trash-outline"></ion-icon>
                        </div>
                        <div class="action-content">
                            <h3>Data Management</h3>
                            <p>Manage your stored data and privacy</p>
                            <div class="action-buttons">
                                <button class="btn-outline danger">
                                    <ion-icon name="trash-outline"></ion-icon>
                                    Clear Cache
                                </button>
                                <button class="btn-outline danger">
                                    <ion-icon name="archive-outline"></ion-icon>
                                    Archive Old Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Credits Tab -->
            <div id="credits" class="settings-tab">
                <div class="tab-header">
                    <h2>Project Credits & Contributions</h2>
                    <p>Meet the Team</p>
                </div>
                
                <div class="credits-content">

                    <div class="credits-intro">
                        <h3>About Attendit</h3>
                        <p>Attendit is a comprehensive student attendance management system designed to streamline tracking, reporting, and management of student records in a standard environment.</p>
                    </div>

                    <!-- Management Team -->
                    <div class="team-section">
                        <h3>
                            <ion-icon name="laptop-outline"></ion-icon>
                           Management Team
                        </h3>
                        <div class="team-grid">
                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emmy/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Adeyefa Faith</h4>
                                    <p class="member-role">Project Lead</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">System Architecture</span>
                                        <span class="contribution-tag">Team Organisation</span>
                                        <span class="contribution-tag">UI/UX Implementation</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/orlando/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Ananu Awodi</h4>
                                    <p class="member-role">Project Management</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Database Design</span>
                                        <span class="contribution-tag">Team Organisation</span>
                                        <span class="contribution-tag">Performance Briefing</span>
                                        <span class="contribution-tag">Documentation</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emily/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Anisa Kwami</h4>
                                    <p class="member-role">Project Management</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Database Design</span>
                                        <span class="contribution-tag">Team Organisation</span>
                                        <span class="contribution-tag">Performance Briefing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Development Team -->
                    <div class="team-section">
                        <h3>
                            <ion-icon name="laptop-outline"></ion-icon>
                           Design & Development Team
                        </h3>
                        <div class="team-grid">
                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emmy/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Peter Banes</h4>
                                    <p class="member-role">Interface and Visual Planner</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">User Experience Planner</span>
                                        <span class="contribution-tag">Project Name</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/orlando/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>John Lucky</h4>
                                    <p class="member-role">Lead Developer</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Frontend Design</span>
                                        <span class="contribution-tag">Backend Design</span>
                                        <span class="contribution-tag">Database Configuration</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emily/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Jeremiah Wakawa</h4>
                                    <p class="member-role">Project Management</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Frontend Design-Dashboard</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emily/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Okechukwu Onaiah</h4>
                                    <p class="member-role">Developer</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Frontend Design-Attendance</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emily/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Mariam Abubakar</h4>
                                    <p class="member-role">Developer</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Frontend Design-Register</span>
                                    </div>
                                </div>
                            </div>

                            <div class="team-member">
                                <div class="member-avatar">
                                    <img src="https://picsum.photos/seed/emily/80/80.jpg" alt="">
                                </div>
                                <div class="member-info">
                                    <h4>Paschal Onyedikachi</h4>
                                    <p class="member-role">Developer</p>
                                    <div class="member-contributions">
                                        <span class="contribution-tag">Frontend Design-Attendance</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Project Information -->
                    <div class="project-info-section">
                        <h3>
                            <ion-icon name="information-circle-outline"></ion-icon>
                            Project Information
                        </h3>
                        <div class="project-info-grid">
                            
                            <div class="project-info-card">
                                <div class="info-icon">
                                    <ion-icon name="logo-github"></ion-icon>
                                </div>
                                <div class="info-content">
                                    <h4>Repository</h4>
                                    <a href="#" class="repo-link">View on GitHub</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  

        </div>
    </div>
</div>

