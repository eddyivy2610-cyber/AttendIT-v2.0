function initAttendance() {
    console.log('Attendance page initialized');
    setupSearch();
}



function markAllSignedIn() {
    if (confirm('Mark all students as signed in?')) {
        const signInForms = document.querySelectorAll('form input[name="action"][value="sign_in"]');
        let submitted = 0;
        
        signInForms.forEach(form => {
            // Add a small delay between submissions to avoid overwhelming the server
            setTimeout(() => {
                form.closest('form').submit();
            }, submitted * 100);
            submitted++;
        });
        
        if (submitted === 0) {
            alert('No students available to sign in.');
        } else {
            alert('Signing in ' + submitted + ' students...');
        }
    }
}

// Initialize attendance page when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initAttendance();
});

document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.students-table tbody tr');
    
    rows.forEach(row => {
        if (row.classList.contains('institution-header')) {
           
            let institutionHasVisibleStudents = false;
            let nextRow = row.nextElementSibling;
            
            while (nextRow && !nextRow.classList.contains('institution-header')) {
                const studentName = nextRow.querySelector('.student-name').textContent.toLowerCase();
                const studentCourse = nextRow.querySelector('.student-course').textContent.toLowerCase();
                const studentInstitution = nextRow.querySelector('.student-institution').textContent.toLowerCase();
                
                if (studentName.includes(searchTerm) || 
                    studentCourse.includes(searchTerm) || 
                    studentInstitution.includes(searchTerm)) {
                    institutionHasVisibleStudents = true;
                    break;
                }
                nextRow = nextRow.nextElementSibling;
            }
            
            row.style.display = institutionHasVisibleStudents || searchTerm === '' ? '' : 'none';
        } else {
           
            const studentName = row.querySelector('.student-name').textContent.toLowerCase();
            const studentCourse = row.querySelector('.student-course').textContent.toLowerCase();
            const studentInstitution = row.querySelector('.student-institution').textContent.toLowerCase();
            
            const isVisible = studentName.includes(searchTerm) || 
                            studentCourse.includes(searchTerm) || 
                            studentInstitution.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
        }
    });
});

// Function to mark all students in an institution as signed in
function markInstitutionSignedIn(institutionName) {
    if (confirm(`Mark all students from ${institutionName} as signed in?`)) {
        const students = document.querySelectorAll(`tr[data-institution="${institutionName}"]`);
        students.forEach(studentRow => {
            const signInForm = studentRow.querySelector('form[action="sign_in"]');
            if (signInForm) {
                signInForm.submit();
            }
        });
    }
}