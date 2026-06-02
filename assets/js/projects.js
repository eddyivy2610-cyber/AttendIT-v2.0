function showAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('projectForm').reset();
}

function saveProject() {
    const form = document.getElementById('projectForm');
    const formData = new FormData(form);
    
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
        showNotification('Please fill in all required fields.', 'error');
        return;
    }
    
    const submitBtn = document.querySelector('.modal-footer .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<ion-icon name="refresh-circle"></ion-icon> Saving...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        const successMsg = tempDiv.querySelector('.notification.success');
        const errorMsg = tempDiv.querySelector('.notification.error');
        
        if (successMsg) {
            showNotification('Project added successfully!', 'success');
            closeAddProjectModal();
            setTimeout(() => window.location.reload(), 1500);
        } else if (errorMsg) {
            showNotification(errorMsg.textContent.trim(), 'error');
        } else {
            showNotification('An unknown error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to save project. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function editProject(projectId) {
    event.stopPropagation();
    console.log('Editing project:', projectId);
    showNotification('Edit functionality coming soon!', 'info');
}

function deleteProject(projectId) {
    event.stopPropagation();
    
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        console.log('Deleting project:', projectId);
        
        const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
        if (row) {
            row.style.opacity = '0.5';
            setTimeout(() => {
                row.remove();
                showNotification('Project deleted successfully!', 'success');
            }, 500);
        }
    }
}

function markComplete(projectId) {
    event.stopPropagation();
    console.log('Marking project as complete:', projectId);
    
    const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
    if (row) {
        row.classList.add('completed');
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
            statusCell.textContent = 'Completed';
            statusCell.className = 'status-badge status-completed';
        }
        
        showNotification('Project marked as complete!', 'success');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <ion-icon name="${type === 'success' ? 'checkmark-circle' : 'close-circle'}"></ion-icon>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 10px 15px;
        border-radius: 4px;
        color: white;
        font-weight: 500;
        z-index: 1001;
        animation: slideIn 0.3s ease;
        ${type === 'success' ? 'background: var(--success);' : 'background: var(--danger);'}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

document.getElementById('projectSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.projects-table tbody tr');
    
    rows.forEach(row => {
        const title = row.querySelector('.project-title').textContent.toLowerCase();
        const stack = row.querySelector('.tech-stack').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || stack.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

window.onclick = function(event) {
    const modal = document.getElementById('addProjectModal');
    if (event.target === modal) {
        closeAddProjectModal();
    }
}

document.getElementById('projectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveProject();
});

document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    });
});

window.showAddProjectModal = showAddProjectModal;
window.closeAddProjectModal = closeAddProjectModal;
window.saveProject = saveProject;
window.editProject = editProject;
window.deleteProject = deleteProject;
window.markComplete = markComplete;