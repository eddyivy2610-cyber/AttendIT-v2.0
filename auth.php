<?php
include_once __DIR__ . '/../config.php';
include_once '../utils/auth.php';

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    handleLogin();
} elseif ($action === 'logout') {
    handleLogout();
} else {
    displayLoginPage();
}

function handleLogin() {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: ?page=login");
        exit();
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $sql = "SELECT user_id, user_name, user_email, user_role, password, is_active FROM users WHERE user_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_active']) {
            $_SESSION['error'] = "Your account has been deactivated.";
            header("Location: ?page=login");
            exit();
        }
        
        loginUser($user);
        $_SESSION['success'] = "Welcome back, " . $user['user_name'] . "!";
        header("Location: ?page=dashboard");
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: ?page=login");
        exit();
    }
}

function handleLogout() {
    logoutUser();
    $_SESSION['success'] = "You have been logged out successfully.";
    header("Location: ?page=login");
    exit();
}

function displayLoginPage() {
    // We'll use your existing login HTML
    if (file_exists('../../login.html')) {
        include '../../login.html';
    } else {
        // Fallback simple login form
        showSimpleLoginForm();
    }
}

function showSimpleLoginForm() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login - Intern Management</title>
        <style>
            body { font-family: Arial; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
            .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
            .input-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .error { color: red; margin-bottom: 10px; }
            .success { color: green; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Supervisor Login</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" action="?page=login">
                <input type="hidden" name="action" value="login">
                
                <div class="input-group">
                    <label>Email:</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="input-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                <small>Test: supervisor@company.com / password</small>
            </p>
        </div>
    </body>
    </html>
    <?php
}
?>