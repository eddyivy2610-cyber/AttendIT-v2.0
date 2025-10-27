<?php
session_start();

// Try different paths for config.php
$config_paths = [
    __DIR__ . '/config.php',
    'config.php',
    './config.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        include_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Config file not found. Please check the file structure.");
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_email']) && isset($_POST['password'])) {
    $email = trim($_POST['user_email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        // Check if database connection works
        if (!isset($database)) {
            die("Database connection not established. Check config.php");
        }
        
        $conn = $database->getConnection();
        $sql = "SELECT user_id, user_name, user_email, user_role, password, is_active FROM users WHERE user_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['email'] = $user['user_email'];
                $_SESSION['role'] = $user['user_role'];
                
                // Update last login
                $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user['user_id']]);
                
                header("Location: home.php");
                exit();
            } else {
                $error = "Your account has been deactivated.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Attendit | Login</title>
</head>
<style>
   * {
  font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
  margin: 0;
  padding: 0;
}

body{
  background: url('https://images.unsplash.com/photo-1760627529541-b7f2a79fa283?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=870');
  min-height:100vh;
  justify-content: center;
  align-items: center;
  display: flex;
  
}
ion-icon{
  pointer-events: none;
}

header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20 100px;
  box-sizing: border-box;
  z-index: 99;
  
}
.background{
  width: 100%;
  height: 100vh;
  align-items: center;
  justify-content: center;
  display: flex;
  background: url("https://www.freepik.com/free-photo/psychedelic-paper-shapes-different-color-tones_25633747.htm#from_element=cross_selling__photo") no-repeat;
  background-position: center;
  background-size: cover;
  filter: blur(5px);
  
  
}
.navbar{
  display: flex;
  justify-content: space-around;
  padding: 1rem;
}
.nav-item{
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 1rem;
  flex: 1;
  max-width: 120px;
}
.navbar a {
  color: #fff;
  text-decoration: none;
  margin-left: 40px;
  font-size:25px;
  transition: 0.3s;
  position: relative;
}
.nav-item span{
  font-size: medium;
}

.navbar a::after{
  content: '';
  position: absolute;
  width: 100%;
  height: 3px;
  background: #f2f2f2;
  left: 0;
  bottom: -6px;
  border-radius: 8px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s;
}
.navbar a:hover::after{
  transform: scaleX(1);
  transform-origin: left;
}

.container{
  position: absolute;
  width: 75%;
  height: 550px;
  box-shadow: 0 0 5px rgba(176, 158, 158, 0.328);
  border-radius: 15px;
  background: url("https://www.freepik.com/free-photo/psychedelic-paper-shapes-different-color-tones_25633747.htm#from_element=cross_selling__photo") no-repeat;
  background-position: center;
  background-size: cover;
}
.container .content{
  position: absolute;
  top: 0;
  left: 0;
  width: 58%;
  height: 100%;
  
}
.texts{
  padding: 80px;
  display: flex;
  flex-direction: column;
  color: #f2f2f2;
  justify-content: space-between;
}

.logo{
  margin-bottom: 7rem;
}
.welcome h2{
  font-size: 40px;
}

.welcome h2 span{
  font-size: 25px;
}
.welcome p{
  font-size: 16px;
  margin: 20px 0;
}
.container .log-box{
  position: absolute;
  top: 0;
  right: 0;
  width: calc(100% - 58%);
  height: 100%;
  overflow: hidden;
}
.log-box .form-box{
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 100%;
  background: transparent;
  backdrop-filter: blur(20px);
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
  color: #f2f2f2;
 
}

.form-box h2{
  text-align: center;
  font-size: 30px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #fff;
}
.form-box .input-box{
  position: relative;
  width: 350px;
  margin: 30px 0;
  height: 50px;   
  border-bottom: 2px solid #fff;
}
.form-box .input-box label{
  position: absolute;
  top:50%;
  left: 5px;
  color: #fff;
  font-size: 1em;
  pointer-events: none;
  transform: translateY(-50%);
  transition: .5s;
  font-weight: 400;
}
.form-box .input-box input{
  min-width: 93%;
  height: 100%;
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-weight: 600;
}
.form-box .input-box .icon{
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.2rem;
  color: #fff;
  line-height: 57px;
} 
.form-box .input-box input:focus ~ label,
.form-box .input-box input:valid ~ label{
  top: -5px;
}               

.form-box .remember-forgot{
  font-size: 14px;
  font-weight: 400;
  color: #ffffff;
  margin: -15px 2 15px;
  display: flex;
  justify-content: space-between;
}
.form-box .remember-forgot label input{
  margin-right: 3px;
}
.form-box .remember-forgot a{
  color: #000000;
  text-decoration: none;
}
.form-box .remember-forgot a:hover{
  text-decoration: underline;
}
.form-box .btn{
  width: 100%;
  height: 50px;
  background: #ffffffbe;
  border: none;
  outline: none;
  border-radius: 6px;
  font-size: 1.2em;
  font-weight: 600;
  cursor: pointer;
  transition: .3s;
  margin-top: 20px;
}
.form-box .btn:hover{
  background: #fffdfa;

}
.form-box .login-register{
  font-size: 14px;
  color: #1f0909;
  text-align: center;
  margin: 20px 0 0;
}
.form-box .login-register a{
  color: #552c00;
  text-decoration: none;
  font-weight: 600;
}
.form-box .login-register a:hover{
  text-decoration: underline;
}
.log-box .register{
  transition: transform .5s ease;
  transform: translate(700px, -550px);
  transition-delay: 0s;


}
.log-box .form-box.login{
  transform: translateX(0);
  transition: transform .6s ease;
  transition-delay: .5s;
}
.log-box.active .form-box.login{
  transform: translateX(550px);
  transition-delay: 0s;
}
.log-box.active .register{
  transform:translate(0px, -550px);
  transition-delay: .5s;
  
}
.error { color: red; margin-bottom: 10px; }
.success { color: green; margin-bottom: 10px; }
</style>
<body>
    
    <header>
        <nav class="navbar">
            <a href="home.php" class="nav-item" onclick="return checkLogin('home');"><ion-icon name="home"></ion-icon><span>home</span></a>
            <a href="settings.php" class="nav-item"><ion-icon name="cog"></ion-icon><span>settings</span></a>
            <a href="register.php" class="nav-item"><ion-icon name="person-add"></ion-icon><span>new</span></a>
            <a href="register.php" class="nav-item"><ion-icon name="information-circle"></ion-icon><span>about</span></a>
        </nav>
    </header>

    <div class="background">
    </div>

    <div class="container">
        <div class="content">
            <div class="texts">
                <h2 class="logo">Attendit</h2>
                <div class="welcome">
                    <h2>Intern Management<br><span>Track attendance & performance</span></h2>
                    <?php if (isset($error)): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="log-box">
            <div class="form-box login">
                <form method="POST" action="login.php">
                    <h2>Sign In</h2>
                    
                    <div class="input-box">
                        <span class="icon"><ion-icon name="mail-outline"></ion-icon></span>
                        <input type="email" name="user_email" required value="<?= htmlspecialchars($_POST['user_email'] ?? '') ?>">
                        <label>Email</label>
                    </div>
                    
                    <div class="input-box">
                        <span class="icon"><ion-icon name="lock-closed-outline"></ion-icon></span>
                        <input type="password" name="password" required>
                        <label>Password</label>
                    </div>
                    
                    <div class="remember-forgot">
                        <label><input type="checkbox" name="remember">Remember me</label>
                        <a href="#">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn">Login</button>
                    
                    <div class="login-register">
                        <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Simple form toggle script
        document.addEventListener('DOMContentLoaded', function() {
            const registerLink = document.querySelector('.register-link');
            const loginLink = document.querySelector('.login-link');
            const logBox = document.querySelector('.log-box');
            
            if (registerLink) {
                registerLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    logBox.classList.add('active');
                });
            }
            
            if (loginLink) {
                loginLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    logBox.classList.remove('active');
                });
            }
        });
        // Check if user is logged in before navigating to home/dashboard
        function checkLogin(destination) {
            // This is a simple client-side check
            // The server will handle the actual authentication
            return true; // Let the server handle the redirect
        }
        
        // Demo credentials auto-fill for testing
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill demo credentials for easier testing
            const emailField = document.querySelector('input[name="user_email"]');
            const passwordField = document.querySelector('input[name="password"]');
            
            if (emailField && !emailField.value) {
                emailField.value = 'supervisor@company.com';
            }
            if (passwordField && !passwordField.value) {
                passwordField.value = 'password';
            }
        });
    </script>
    
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>