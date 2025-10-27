function initDashboard() {
    console.log('Dashboard initialized');
    
    // Auto-refresh dashboard data every 30 seconds
    setInterval(loadDashboardData, 30000);
    
    // Add hover effects to action cards
    const actionCards = document.querySelectorAll('.action-card-link');
    actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Dashboard real-time data updates
function updateDashboardData() {
    fetch('api/dashboard-data.php')
        .then(response => response.json())
        .then(data => {
            // Update card values
            document.getElementById('total-students').textContent = data.totalStudents;
            document.getElementById('attendance-rate').textContent = data.attendanceRate + '%';
            document.getElementById('active-students').textContent = data.activeStudents;
            
            // Update change indicators with dynamic data
            updateChangeIndicators(data);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}


f
// Initial load
document.addEventListener('DOMContentLoaded', function() {
    updateDashboardData();
    
    // Add click handlers for card updates
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', function() {
            this.classList.add('card-updating');
            setTimeout(() => {
                this.classList.remove('card-updating');
            }, 1000);
        });
    });
});

// Make function global for manual updates
window.updateDashboardData = updateDashboardData;

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});