// Search functionality
document.getElementById('projectSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.projects-table tbody tr');
    
    rows.forEach(row => {
        const title = row.querySelector('.project-title').textContent.toLowerCase();
        const students = row.cells[1].textContent.toLowerCase();
        const tech = row.querySelector('.tech-stack').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || students.includes(searchTerm) || tech.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Modal functions
function showAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'block';
}

function closeAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'none';
}

function saveProject() {
    const form = document.getElementById('projectForm');
    const formData = new FormData(form);
    console.log('Saving project:', Object.fromEntries(formData));
    closeAddProjectModal();
}

function viewProjectSource(projectId) {
    console.log('Viewing source for project:', projectId);
    
}

function markComplete(projectId) {
    if (confirm('Mark this project as completed?')) {
        console.log('Marking project complete:', projectId);
        // Update status to "Completed"
        const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
        if (row) {
            row.classList.add('completed');
            const statusCell = row.cells[6];
            statusCell.innerHTML = '<span class="status-badge status-completed">Completed</span>';
            
            // Remove complete button
            const completeBtn = row.querySelector('.btn-icon.complete');
            if (completeBtn) completeBtn.remove();
        }
    }
}

function editProject(projectId) {
    console.log('Editing project:', projectId);
    // Open edit modal with project data
}

function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project?')) {
        console.log('Deleting project:', projectId);
        // Remove from table
        const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
        if (row) row.remove();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addProjectModal');
    if (event.target === modal) {
        closeAddProjectModal();
    }
}