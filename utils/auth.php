<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirectToLogin() {
    header("Location: login.php");
    exit();
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirectToLogin();
    }
}

function loginUser($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['user_name'];
    $_SESSION['email'] = $user['user_email'];
    $_SESSION['role'] = $user['user_role'];
    
    // Update last login
    global $database;
    $conn = $database->getConnection();
    $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user['user_id']]);
}

function logoutUser() {
    session_destroy();
    header("Location: login.php");
    exit();
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

function checkPermission($requiredRole = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        return false;
    }
    
    return true;
}
?>