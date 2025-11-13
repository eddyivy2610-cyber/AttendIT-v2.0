<?php
include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../model/students.php';
include_once __DIR__ . '/../model/Institution.php';

$database = new Database();
$db = $database->getConnection();

$student = new Student($db);
$students = $student->read();

$institution = new Institution($db);
$institutions = $institution->read();

$success_message = '';
$error_message = '';
$form_data = [];
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processRegistration($_POST, files: $_FILES);
    
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
       
        $database = new Database();
        $db = $database->getConnection();
        $student = new Student($db);
        
        
        $required_fields = ['student_name', 'email', 'phone', 'course_of_study', 'institution_id', 'gender'];
        foreach ($required_fields as $field) {
            if (empty(trim($post_data[$field] ?? ''))) {
                return ['success' => false, 'message' => "Please fill in all required fields."];
            }
        }
        
        
        if (!filter_var($post_data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "Please enter a valid email address."];
        }
        
        
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

        
        if (!empty($post_data['period_of_attachment'])) {
            $period_months = intval($post_data['period_of_attachment']);
            $student->end_date = date('Y-m-d', strtotime("+$period_months months"));
        }
        
        
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

?>
<div id="students-page" class="page-content">
    <div class="content">
       
        <div class="filter-section">
            <div class="filter-actions">
                <div class="year-pagination">
                    <button class="year-btn active">2025</button>
                    <button class="year-btn">2024</button>
                    <button class="year-btn">2023</button>
                    <button class="year-btn">2022</button>
                    <button class="year-btn">2021</button>
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Search students..." class="search-input" id="student-search">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="action-btn">
                    <button class="export-btn" onclick="showAddStudentModal()">
                        <i class="fa-solid fa-user-plus"></i> Add Student
                    </button>
                    <button class="export-btn">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    
        <!-- Students Table -->
        <div class="students-table-container">
            <table class="students-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="sn">S/N</th>
                        <th class="sortable" data-sort="image">
                            <i class="fas fa-image"></i>
                        </th>
                        <th class="sortable" data-sort="name">Name</th>
                        <th class="sortable" data-sort="gender">Gender</th>
                        <th class="sortable" data-sort="course">Department</th>
                        <th class="sortable" data-sort="school">School</th>
                        <th class="sortable" data-sort="skill">Skill Interest</th>
                        <th class="sortable" data-sort="start">Start Date</th>
                        <th class="sortable" data-sort="end">End Date</th>
                        <th class="actions-header">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($students && $students->rowCount() > 0) {
                        $sn = 1;
                        while($row = $students->fetch(PDO::FETCH_ASSOC)) {
                            $skill_interest = !empty($row['skill_of_interest']) ? $row['skill_of_interest'] : 'Not specified';
                            ?>
                            <tr class="student-row" data-student-id="<?php echo $row['student_id']; ?>">
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <img src="https://picsum.photos/seed/<?php echo urlencode($row['student_name']); ?>/40/40.jpg" 
                                         alt="<?php echo $row['student_name']; ?>" 
                                         class="student-photo"
                                         onclick="viewStudentReport(<?php echo $row['student_id']; ?>)">
                                </td>
                                <td class="student-name" onclick="viewStudentReport(<?php echo $row['student_id']; ?>)">
                                    <?php echo $row['student_name']; ?>
                                </td>
                                <td><?php echo $row['gender'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['course_of_study']; ?></td>
                                <td><?php echo $row['institution_name']; ?></td>
                                <td>
                                    <div class="skill-interest">
                                        <span class="skill-text"><?php echo htmlspecialchars($skill_interest); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($row['join_date'])); ?></td>
                                <td>
                                    <div class="end-date <?php echo (strtotime($row['end_date']) < time()) ? 'expired' : ''; ?>">
                                        <?php echo date('M j, Y', strtotime($row['end_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons side">
                                        <button class="action-btn edit-btn" 
                                                onclick="editStudent(<?php echo $row['student_id']; ?>)"
                                                data-tooltip="Edit Student">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                onclick="deleteStudent(<?php echo $row['student_id']; ?>)"
                                                data-tooltip="Delete Student">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 20px;">
                                <div class="no-students">
                                    <i class="fas fa-user-graduate" style="font-size: 2rem; color: #ccc; margin-bottom: 10px;"></i>
                                    <p>No students found. Please add some students to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table Pagination -->
        <div class="table-pagination">
            <div class="pagination-info">
                Showing <span class="start-record">1</span> to <span class="end-record">
                <?php 
                if ($students && $students->rowCount() > 0) {
                    echo $students->rowCount();
                } else {
                    echo "0";
                }
                ?>
                </span> of <span class="total-records">500</span> students
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="pagination-btn active">1</button>
                <button class="pagination-btn">2</button>
                <button class="pagination-btn">3</button>
                <button class="pagination-btn">4</button>
                <button class="pagination-btn">5</button>
                <button class="pagination-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Student</h3>
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
            <span class="close" onclick="closeAddStudentModal()">&times;</span>
        </div>

        
        <div class="modal-body">
            <form method="POST" id="addStudentForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_name">Full Name *</label>
                        <input type="text" id="student_name" name="student_name" 
                               value="<?php echo htmlspecialchars($form_data['student_name'] ?? ''); ?>" 
                               placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                       <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                               placeholder="+2348012345678" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                         <select id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo (($form_data['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($form_data['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($form_data['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_of_study">Course of Study *</label>
                        <input type="text" class="form-input" id="course_of_study" name="course_of_study" 
                               value="<?php echo htmlspecialchars($form_data['course_of_study'] ?? ''); ?>" 
                               placeholder="e.g., Computer Science, Engineering" required>
                    </div>
                    <div class="form-group">
                        <label for="institution_id">Institution *</label>
                        <select name="institution_id" required>
                            <option value="">Select Institution</option>
                            <?php
                            if ($institutions) {
                                while($inst = $institutions->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $inst['institution_id'] . '">' . $inst['institution_name'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="skill_of_interest">Skill Interest</label>
                        <input type="text" id="skill_of_interest" name="skill_of_interest" 
                           value="<?php echo htmlspecialchars($form_data['skill_of_interest'] ?? ''); ?>" 
                           placeholder="e.g., Web Development, Data Analysis">
                    </div>
                    <div class="form-group">
                        <label for="period_of_attachment">Period of Attachment (months)</label>
                        <input type="number"  id="period_of_attachment" name="period_of_attachment" 
                               value="<?php echo htmlspecialchars($form_data['period_of_attachment'] ?? '6'); ?>" 
                               placeholder="e.g., 6" min="1" max="24">
                    </div>
                </div>
                <div class="form-row">
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
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
            <button type="submit" class="btn-primary" id="submitBtn" onclick="saveStudent()">Save Student</button>
        </div>
    </div>
</div>

<style>
</style>

<script>
// Student Management Functions
function viewStudentReport(studentId) {
    window.location.href = '?page=reports&student_id=' + studentId;
}

function showAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'block';
}

function closeAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'none';
    document.getElementById('addStudentForm').reset();
}

function saveStudent() {
    const form = document.getElementById('addStudentForm');
    const formData = new FormData(form);
    
    // Basic validation
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            valid = false;
            field.style.borderColor = 'var(--danger)';
        } else {
            field.style.borderColor = '';
        }
    });
    
    if (!valid) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Here you would typically send the data to your backend
    console.log('Saving student:', Object.fromEntries(formData));
    
    // Close modal and show success message
    closeAddStudentModal();
    showNotification('Student added successfully!', 'success');
}

function editStudent(studentId) {
    // Prevent row click event from firing
    event.stopPropagation();
    
    // Here you would typically open an edit modal with student data
    console.log('Editing student:', studentId);
    showNotification('Edit functionality coming soon!', 'info');
}

function deleteStudent(studentId) {
    // Prevent row click event from firing
    event.stopPropagation();
    
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        // Here you would typically send delete request to backend
        console.log('Deleting student:', studentId);
        
        // Remove from table (temporary until backend integration)
        const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
        if (row) {
            row.style.opacity = '0.5';
            setTimeout(() => {
                row.remove();
                showNotification('Student deleted successfully!', 'success');
            }, 500);
        }
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 1001;
        animation: slideIn 0.3s ease;
        ${type === 'success' ? 'background: var(--success);' : 'background: var(--primary);'}
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Search functionality
document.getElementById('student-search').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.student-row');
    
    rows.forEach(row => {
        const studentName = row.querySelector('.student-name').textContent.toLowerCase();
        const course = row.cells[4].textContent.toLowerCase();
        const school = row.cells[5].textContent.toLowerCase();
        const skill = row.querySelector('.skill-text').textContent.toLowerCase();
        
        if (studentName.includes(searchTerm) || course.includes(searchTerm) || 
            school.includes(searchTerm) || skill.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addStudentModal');
    if (event.target === modal) {
        closeAddStudentModal();
    }
}


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

// Make functions global
window.viewStudentReport = viewStudentReport;
window.showAddStudentModal = showAddStudentModal;
window.closeAddStudentModal = closeAddStudentModal;
window.saveStudent = saveStudent;
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
</script>