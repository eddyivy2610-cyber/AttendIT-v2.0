<?php
include_once __DIR__ . '/../models/Student.php';
include_once __DIR__ . '/../models/Institution.php';
include_once __DIR__ . '/../config/database.php';

 $database = new Database();
 $db = $database->getConnection();

 $student = new Student($db);
 $institution = new Institution($db);

 $students = $student->read();
 $institutions = $institution->read();
?>

<!-- Students Content -->
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
                <button class="export-btn">
                    <i class="fas fa-download"></i> Export
                </button>
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
                        <th class="sortable" data-sort="course">Course</th>
                        <th class="sortable" data-sort="school">School</th>
                        <th class="sortable" data-sort="start">Start Date</th>
                        <th class="sortable" data-sort="end">End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($students && $students->rowCount() > 0) {
                        $sn = 1;
                        while($row = $students->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <tr class="student-row" data-student-id="<?php echo $row['student_id']; ?>" onclick="viewStudentReport(<?php echo $row['student_id']; ?>)">
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <img src="https://picsum.photos/seed/<?php echo urlencode($row['student_name']); ?>/40/40.jpg" alt="<?php echo $row['student_name']; ?>" class="student-photo">
                                </td>
                                <td><?php echo $row['student_name']; ?></td>
                                <td><?php echo $row['gender'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['course_of_study']; ?></td>
                                <td><?php echo $row['institution_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['join_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['end_date'])); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
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

<style>
/* Students Page Styles */
.content {
    background: var(--bg-blue);
    padding: 10px;
}

body.dark-theme .content {
    background: var(--dark-black2);
}

/* Filter Section */
.filter-section {
    background: var(--white);
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 8px 12px;
    margin-bottom: 15px;
    display: flex;
    justify-content: flex-end;
}

body.dark-theme .filter-section {
    background: var(--dark-black2);
    box-shadow: 0 -4px var(--dark-blue);
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    justify-content: space-between;
}

/* Year Pagination in Filter Section */
.year-pagination {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 0;
}

.year-pagination::-webkit-scrollbar {
    height: 4px;
}

.year-pagination::-webkit-scrollbar-track {
    background: var(--gray);
}

.year-pagination::-webkit-scrollbar-thumb {
    background-color: var(--primary);
    border-radius: 4px;
}

.year-btn {
    padding: 4px 8px;
    background-color: var(--white);
    border: 1px solid var(--black2);
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    font-size: 13px;
}

body.dark-theme .year-btn {
    background: var(--black);
    border: 1px solid var(--dark-blue2);
    color: var(--white);
}

.year-btn.active {
    background-color: var(--hover-blue);
    color: var(--white);
    border-color: var(--blue);
}

.year-btn:hover {
    background-color: var(--blue);
    color: var(--white);
    border-color: var(--blue);
}

.search-box {
    position: relative;
    width: 200px;
}

.search-input {
    width: 100%;
    padding: 6px 30px 6px 10px;
    border: 1px solid var(--black2);
    border-radius: 20px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.3s;
}

body.dark-theme .search-input {
    background: var(--dark-blue2);
    border: none;
    color: var(--white);
}

.search-input:focus {
    border-color: var(--blue);
}

body.dark-theme .search-input:focus {
    background: var(--dark-blue);
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--black2);
    font-size: 13px;
}

body.dark-theme .search-icon {
    color: var(--white);
}

.export-btn {
    background: var(--blue) !important;
    color: var(--white);
    border: none;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: background-color 0.3s;
    white-space: nowrap;
}

body.dark-theme .export-btn {
    background-color: var(--dark-blue2);
}

.export-btn:hover {
    background-color: var(--hover-blue) !important;
}

body.dark-theme .export-btn:hover {
    background-color: var(--dark-blue);
}

/* Students Table */
.students-table-container {
    background: var(--white);
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 15px;
    height: calc(100vh - 200px);
}

body.dark-theme .students-table-container {
    background: var(--dark-black2);
    box-shadow: 0 -4px var(--dark-blue);
}

.students-table {
    width: 100%;
    border-collapse: collapse;
    height: 100%;
}

.students-table thead {
    background-color: var(--gray);
    position: sticky;
    top: 0;
    z-index: 10;
}

body.dark-theme .students-table thead {
    background: var(--dark-black);
    border-bottom: 1px solid var(--dark-blue);
}

.students-table th {
    padding: 8px 10px;
    text-align: left;
    font-weight: 600;
    color: var(--black1);
    font-size: 13px;
    white-space: nowrap;
}

body.dark-theme .students-table th {
    color: var(--white);
}

.students-table th.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.students-table th.sortable:hover {
    color: var(--blue);
}

.students-table th.sortable i {
    margin-right: 4px;
    font-size: 11px;
}

.students-table tbody {
    overflow-y: auto;
    display: block;
    max-height: calc(100vh - 250px);
}

.students-table thead, .students-table tbody tr {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.students-table td {
    padding: 6px 10px;
    font-size: 13px;
    color: var(--black1);
    border-bottom: 1px solid var(--gray);
}

body.dark-theme .students-table td {
    color: var(--white);
    border-bottom: 1px solid var(--dark-black);
}

.students-table tr:last-child td {
    border-bottom: none;
}

.students-table tr:hover {
    background-color: var(--hover-color);
    cursor: pointer;
}

body.dark-theme .students-table tr:hover {
    background-color: var(--dark-black);
}

.student-photo {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    object-fit: cover;
}

.no-students {
    text-align: center;
    padding: 20px;
    color: var(--black2);
}

body.dark-theme .no-students {
    color: var(--white);
}

/* Table Pagination */
.table-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--white);
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 8px 12px;
}

body.dark-theme .table-pagination {
    background: var(--dark-black2);
    box-shadow: 0 -4px var(--dark-blue);
}

.pagination-info {
    font-size: 13px;
    color: var(--black2);
}

body.dark-theme .pagination-info {
    color: var(--white);
}

.pagination-controls {
    display: flex;
    gap: 4px;
}

.pagination-btn {
    width: 28px;
    height: 28px;
    border-radius: 5px;
    background: none;
    border: 1px solid var(--black2);
    color: var(--black1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 12px;
}

body.dark-theme .pagination-btn {
    border: 1px solid var(--dark-blue2);
    color: var(--white);
}

.pagination-btn:hover {
    background-color: var(--blue);
    color: var(--white);
    border-color: var(--blue);
}

.pagination-btn.active {
    background-color: var(--blue);
    color: var(--white);
    border-color: var(--blue);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1024px) {
    .filter-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .search-box {
        width: 100%;
    }
    
    .students-table-container {
        height: calc(100vh - 240px);
    }
    
    .students-table tbody {
        max-height: calc(100vh - 290px);
    }
}

@media (max-width: 768px) {
    .year-pagination {
        width: 100%;
        justify-content: center;
    }
    
    .students-table-container {
        overflow-x: auto;
        height: calc(100vh - 280px);
    }
    
    .students-table {
        min-width: 700px;
    }
    
    .students-table tbody {
        max-height: calc(100vh - 330px);
    }
    
    .table-pagination {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<script>
// Students page specific initialization
function initStudentsPage() {
    console.log('Students page loaded successfully');
    setupStudentActions();
    setupSearchFunctionality();
}

function setupStudentActions() {
    // Year pagination
    const yearBtns = document.querySelectorAll('.year-btn');
    yearBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            yearBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            showNotification(`Showing students from ${this.textContent}`, 'info');
        });
    });

    // Table pagination
    const paginationBtns = document.querySelectorAll('.pagination-btn:not(:disabled)');
    paginationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelector('.pagination-btn.active')?.classList.remove('active');
            if (!this.querySelector('i')) {
                this.classList.add('active');
            }
            showNotification(`Loading page ${this.textContent}`, 'info');
        });
    });

    // Export button
    const exportBtn = document.querySelector('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            showNotification('Exporting student data...', 'info');
        });
    }
}

function setupSearchFunctionality() {
    const searchInput = document.getElementById('student-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.student-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Make function globally available
window.viewStudentReport = function(studentId) {
    showNotification(`Opening report for student ID: ${studentId}`, 'info');
    // Navigate to reports page with student ID
    setTimeout(() => {
        window.location.href = `?page=reports&student_id=${studentId}`;
    }, 1000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('students-page')) {
        initStudentsPage();
    }
});
</script>