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
?>
<div id="students-page" class="page-content">
    <div class="content">
        <!-- Unified Filter Section -->
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
            <span class="close" onclick="closeAddStudentModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addStudentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Course of Study *</label>
                        <input type="text" name="course_of_study" required>
                    </div>
                    <div class="form-group">
                        <label>Institution *</label>
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
                        <label>Skill Interest</label>
                        <input type="text" name="skill_of_interest" placeholder="e.g., Web Development, Data Analysis">
                    </div>
                    <div class="form-group">
                        <label>Period of Attachment (months)</label>
                        <input type="number" name="period_of_attachment" min="1" max="24" value="6">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Supervisor</label>
                        <input type="text" name="supervisor">
                    </div>
                    <div class="form-group">
                        <label>Birthday</label>
                        <input type="date" name="birthday">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
            <button class="btn-primary" onclick="saveStudent()">Save Student</button>
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

// Make functions global
window.viewStudentReport = viewStudentReport;
window.showAddStudentModal = showAddStudentModal;
window.closeAddStudentModal = closeAddStudentModal;
window.saveStudent = saveStudent;
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
</script>