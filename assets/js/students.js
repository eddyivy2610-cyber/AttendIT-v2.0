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