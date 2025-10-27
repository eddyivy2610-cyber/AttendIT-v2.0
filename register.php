<?php
session_start();

include_once 'config.php';
include_once 'model/students.php';
include_once 'model/institution.php';

// Initialize variables
$success_message = '';
$error_message = '';
$form_data = [];
$upload_error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processRegistration($_POST, $_FILES);
    
    if ($result['success']) {
        $success_message = $result['message'];
        $form_data = [];
    } else {
        $error_message = $result['message'];
        $upload_error = $result['upload_error'] ?? '';
        $form_data = $_POST;
    }
}

function processRegistration($post_data, $files) {
    try {
        // Initialize database connection
        $database = new Database();
        $db = $database->getConnection();
        $student = new Student($db);
        
        // Validate required fields
        $required_fields = ['student_name', 'email', 'phone', 'course_of_study', 'institution_id', 'gender'];
        foreach ($required_fields as $field) {
            if (empty(trim($post_data[$field] ?? ''))) {
                return ['success' => false, 'message' => "Please fill in all required fields."];
            }
        }
        
        // Validate email
        if (!filter_var($post_data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "Please enter a valid email address."];
        }
        
        // Handle passport image upload
        $photo_url = '';
        if (!empty($files['passport_image']) && $files['passport_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handlePassportUpload($files['passport_image']);
            if (!$upload_result['success']) {
                return [
                    'success' => false, 
                    'message' => "Registration failed due to image upload error.",
                    'upload_error' => $upload_result['message']
                ];
            }
            $photo_url = $upload_result['file_path'];
        }
        
        // Set student properties
        $student->student_name = trim($post_data['student_name']);
        $student->email = trim($post_data['email']);
        $student->phone = trim($post_data['phone']);
        $student->course_of_study = trim($post_data['course_of_study']);
        $student->institution_id = intval($post_data['institution_id']);
        $student->gender = trim($post_data['gender']);
        $student->period_of_attachment = trim($post_data['period_of_attachment'] ?? '');
        $student->birthday = !empty($post_data['birthday']) ? $post_data['birthday'] : null;
        $student->skill_of_interest = trim($post_data['skill_of_interest'] ?? '');
        $student->supervisor = trim($post_data['supervisor'] ?? '');
        $student->photo_url = $photo_url;
        $student->join_date = date('Y-m-d');
        $student->status = 'Active';

        // Calculate end date based on period of attachment
        if (!empty($post_data['period_of_attachment'])) {
            $period_months = intval($post_data['period_of_attachment']);
            $student->end_date = date('Y-m-d', strtotime("+$period_months months"));
        }
        
        // Create student record
        if ($student->create()) {
            return [
                'success' => true, 
                'message' => "Registration successful! Welcome to AttendIt."
            ];
        } else {
            return ['success' => false, 'message' => "Unable to complete registration. Please try again."];
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => "System error. Please contact administrator."];
    }
}

function handlePassportUpload($file) {
    try {
        // Check if file is an image
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return ['success' => false, 'message' => "File is not an image."];
        }
        
        // Check file size (limit to 2MB)
        if ($file['size'] > 2000000) {
            return ['success' => false, 'message' => "File is too large. Maximum size is 2MB."];
        }
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => "Only JPG, JPEG, PNG & GIF files are allowed."];
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/passports/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $new_filename = uniqid('passport_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;
        
        // Move file to uploads directory
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'file_path' => $target_file];
        } else {
            return ['success' => false, 'message' => "Error uploading file."];
        }
    } catch (Exception $e) {
        error_log("Image upload error: " . $e->getMessage());
        return ['success' => false, 'message' => "System error during image upload."];
    }
}

// Get institutions for dropdown
$institutions = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $institution_model = new Institution($db);
    $institutions_result = $institution_model->read();
    if ($institutions_result) {
        while ($row = $institutions_result->fetch(PDO::FETCH_ASSOC)) {
            $institutions[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error loading institutions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">
    <title>Attendit | Student Registration</title>
    <style>
        /* Additional styles for registration form */
        .registration-container {
            position: relative;
            width: 800px;
            height: auto;
            background: transparent;
            box-shadow: 0 0 5px rgba(176, 158, 158, 0.328);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            display: flex;
            overflow: hidden; 
            transform: scale(1);
            transition: transform .5s ease, height .2s ease;
            margin: 20px auto;
        }

        .registration-body {
            width: 100%;
            padding: 40px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .registration-body h2 {
            text-align: center;
            font-size: 30px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #fff;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #fff;
            font-size: 14px;
        }

        .form-label .required {
            color: #ff6b6b;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            background: transparent;
            border: 2px solid #fff;
            border-radius: 8px;
            font-size: 14px;
            color: #fff;
            transition: all 0.3s ease;
        }

        .form-input::placeholder, .form-select option {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #ffd9aa;
            box-shadow: 0 0 0 3px rgba(255, 217, 170, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: #ffffffbe;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: #fffdfa;
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #fff;
            font-size: 14px;
        }

        .login-link a {
            color: #ffd9aa;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Image Upload Styles matching the theme */
        .image-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #fff;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .image-upload:hover {
            border-color: #ffd9aa;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .image-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .image-preview {
            max-width: 100%;
            max-height: 150px;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
            border: 2px solid #fff;
        }

        .image-upload-icon {
            font-size: 36px;
            color: #fff;
            margin-bottom: 10px;
        }

        .image-upload-text {
            color: #fff;
            text-align: center;
        }

        .image-upload-text strong {
            color: #ffd9aa;
        }

        .upload-error {
            color: #ff6b6b;
            font-size: 13px;
            margin-top: 5px;
            text-align: center;
        }

        /* Alert styles matching login page */
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(235, 251, 238, 0.9);
            color: #51cf66;
            border: 1px solid #51cf66;
        }

        .alert-error {
            background: rgba(255, 234, 234, 0.9);
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
        }

        @media (max-width: 850px) {
            .registration-container {
                width: 95%;
                margin: 10px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .registration-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    
    <header>
        <nav class="navbar">
            <a href="home.php" class="nav-item" onclick="return checkLogin('home');">
                <ion-icon name="home"></ion-icon>
                <span>Home</span>
            </a>
            
            <a href="settings.php" class="nav-item">
                <ion-icon name="cog"></ion-icon>
                <span>Settings</span>
            </a>
            
            <a href="login.php" class="nav-item">
                <ion-icon name="log-in"></ion-icon>
                <span>Login</span>
            </a>
        </nav>
    </header>

    <div class="background">
    </div>

    <div class="registration-container">
        <div class="registration-body">
            <h2>Student Registration</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <ion-icon name="checkmark-circle"></ion-icon> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <ion-icon name="close-circle"></ion-icon> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registrationForm" enctype="multipart/form-data">
                <!-- Personal Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="student_name">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-input" id="student_name" name="student_name" 
                               value="<?php echo htmlspecialchars($form_data['student_name'] ?? ''); ?>" 
                               placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                        <input type="email" class="form-input" id="email" name="email" 
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" class="form-input" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                               placeholder="+2348012345678" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gender">Gender <span class="required">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo (($form_data['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($form_data['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($form_data['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="birthday">Date of Birth</label>
                        <input type="date" class="form-input" id="birthday" name="birthday" 
                               value="<?php echo htmlspecialchars($form_data['birthday'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="period_of_attachment">Attachment Period (Months)</label>
                        <input type="number" class="form-input" id="period_of_attachment" name="period_of_attachment" 
                               value="<?php echo htmlspecialchars($form_data['period_of_attachment'] ?? '6'); ?>" 
                               placeholder="e.g., 6" min="1" max="24">
                    </div>
                </div>

                <!-- Passport Image Upload -->
                <div class="form-group">
                    <label class="form-label" for="passport_image">Passport Photo</label>
                    <div class="image-upload" id="imageUpload">
                        <input type="file" id="passport_image" name="passport_image" accept="image/*">
                        <ion-icon name="cloud-upload-outline" class="image-upload-icon"></ion-icon>
                        <div class="image-upload-text">
                            <strong>Click to upload</strong> or drag and drop<br>
                            <small>JPG, PNG, GIF (Max 2MB)</small>
                        </div>
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Passport Preview">
                    <?php if ($upload_error): ?>
                        <div class="upload-error"><?php echo htmlspecialchars($upload_error); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Educational Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="course_of_study">Course of Study <span class="required">*</span></label>
                        <input type="text" class="form-input" id="course_of_study" name="course_of_study" 
                               value="<?php echo htmlspecialchars($form_data['course_of_study'] ?? ''); ?>" 
                               placeholder="e.g., Computer Science, Engineering" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="institution_id">Institution <span class="required">*</span></label>
                        <select class="form-select" id="institution_id" name="institution_id" required>
                            <option value="">Select your institution</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?php echo $institution['institution_id']; ?>" 
                                    <?php echo (($form_data['institution_id'] ?? '') == $institution['institution_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($institution['institution_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="skill_of_interest">Skill/Area of Interest</label>
                    <input type="text" class="form-input" id="skill_of_interest" name="skill_of_interest" 
                           value="<?php echo htmlspecialchars($form_data['skill_of_interest'] ?? ''); ?>" 
                           placeholder="e.g., Web Development, Data Analysis">
                </div>

                <div class="form-group">
                    <label class="form-label" for="supervisor">Supervisor Name</label>
                    <input type="text" class="form-input" id="supervisor" name="supervisor" 
                           value="<?php echo htmlspecialchars($form_data['supervisor'] ?? ''); ?>" 
                           placeholder="Enter supervisor's name">
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    <ion-icon name="person-add"></ion-icon> Complete Registration
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p>Admin? <a href="home.php">Access dashboard here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and functionality
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const form = this;
            const submitButton = document.getElementById('submitBtn');
            
            // Basic validation
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ff6b6b';
                } else {
                    field.style.borderColor = '#fff';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            submitButton.innerHTML = '<ion-icon name="refresh-circle"></ion-icon> Registering...';
            submitButton.disabled = true;
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('234') && value.length === 13) {
                value = '+' + value;
            } else if (value.length === 11 && value.startsWith('0')) {
                value = '+234' + value.substring(1);
            }
            e.target.value = value;
        });

        // Image preview
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('passport_image');
        const imagePreview = document.getElementById('imagePreview');
        
        imageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Drag and drop functionality
        imageUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ffd9aa';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
        });

        imageUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#fff';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        });

        imageUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#fff';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                imageInput.files = e.dataTransfer.files;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(e.dataTransfer.files[0]);
            }
        });

        // Auto-hide messages
        <?php if ($success_message): ?>
        setTimeout(() => {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                successAlert.style.display = 'none';
            }
        }, 8000);
        <?php endif; ?>
    </script>
    
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>