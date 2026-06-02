/**
 * Attendit - Attendance Page JavaScript
 * Handles attendance-specific functionality
 */

(function() {
    'use strict';
    
    // Attendance Module
    const Attendance = {
        // Initialize attendance page
        init: function() {
            console.log('Attendance page initialized');
            this.setupSearch();
            this.setupEventListeners();
            this.setupAnimations();
        },
        // Add this to the init function
        setupMobileOptimizations: function() {
            this.checkMobileLayout();
            window.addEventListener('resize', this.checkMobileLayout.bind(this));
        },

        checkMobileLayout: function() {
            const isMobile = window.innerWidth <= 768;
            const table = document.querySelector('.students-table');
            
            if (table && isMobile) {
                // Add mobile class for additional styling
                table.classList.add('mobile-view');
                
                // Show mobile info sections
                document.querySelectorAll('.student-mobile-info').forEach(info => {
                    info.style.display = 'block';
                });
            } else if (table) {
                table.classList.remove('mobile-view');
                
                // Hide mobile info on desktop
                document.querySelectorAll('.student-mobile-info').forEach(info => {
                    info.style.display = 'none';
                });
            }
        },
        // Setup search functionality
        setupSearch: function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', this.handleSearch.bind(this));
            }
        },
        
        // Handle search input
        handleSearch: function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.students-table tbody tr');
            let visibleRows = 0;
            
            rows.forEach(row => {
                if (row.classList.contains('institution-header')) {
                    // Handle institution header rows
                    let institutionHasVisibleStudents = false;
                    let nextRow = row.nextElementSibling;
                    
                    while (nextRow && !nextRow.classList.contains('institution-header')) {
                        if (this.isRowVisible(nextRow, searchTerm)) {
                            institutionHasVisibleStudents = true;
                            break;
                        }
                        nextRow = nextRow.nextElementSibling;
                    }
                    
                    row.style.display = institutionHasVisibleStudents || searchTerm === '' ? '' : 'none';
                } else {
                    // Handle student rows
                    const isVisible = this.isRowVisible(row, searchTerm);
                    row.style.display = isVisible ? '' : 'none';
                    
                    if (isVisible) {
                        visibleRows++;
                    }
                }
            });
            
            // Update student count in header
            const header = document.querySelector('.students-header h2');
            if (header && searchTerm) {
                const originalCount = header.getAttribute('data-original-count') || 
                                    header.textContent.match(/\((\d+)\)/)?.[1] || '0';
                header.textContent = `Interns (${searchTerm ? visibleRows : originalCount})`;
            }
        },
        
        // Check if row should be visible based on search term
        isRowVisible: function(row, searchTerm) {
            if (!searchTerm) return true;
            
            const studentName = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
            const studentCourse = row.querySelector('.student-course')?.textContent.toLowerCase() || '';
            const studentInstitution = row.querySelector('.student-institution')?.textContent.toLowerCase() || '';
            
            return studentName.includes(searchTerm) || 
                   studentCourse.includes(searchTerm) || 
                   studentInstitution.includes(searchTerm);
        },
        
        // Mark all students as signed in
        markAllSignedIn: function() {
            if (confirm('Mark all students as signed in?')) {
                const signInForms = document.querySelectorAll('form input[name="action"][value="sign_in"]');
                let submitted = 0;
                
                // Show loading state
                this.showLoading('Signing in students...');
                
                signInForms.forEach((form, index) => {
                    setTimeout(() => {
                        const formElement = form.closest('form');
                        if (formElement) {
                            formElement.submit();
                            submitted++;
                        }
                    }, index * 100); // Stagger submissions to avoid overwhelming server
                });
                
                setTimeout(() => {
                    if (submitted === 0) {
                        this.showNotification('No students available to sign in.', 'info');
                    } else {
                        this.showNotification(`Signing in ${submitted} students...`, 'success');
                    }
                    this.hideLoading();
                }, 500);
            }
        },
        
        // Mark all students in an institution as signed in
        markInstitutionSignedIn: function(institutionName) {
            if (confirm(`Mark all students from ${institutionName} as signed in?`)) {
                const students = document.querySelectorAll(`tr[data-institution="${institutionName}"]`);
                let submitted = 0;
                
                students.forEach(studentRow => {
                    const signInForm = studentRow.querySelector('form[action="sign_in"]') || 
                                     studentRow.querySelector('form input[name="action"][value="sign_in"]')?.closest('form');
                    
                    if (signInForm) {
                        setTimeout(() => {
                            signInForm.submit();
                            submitted++;
                        }, submitted * 100);
                    }
                });
                
                if (submitted > 0) {
                    this.showNotification(`Signing in ${submitted} students from ${institutionName}...`, 'success');
                } else {
                    this.showNotification(`No students available to sign in from ${institutionName}.`, 'info');
                }
            }
        },
        
        // Setup event listeners
        setupEventListeners: function() {
            // Add click handler to institution headers
            document.addEventListener('click', (e) => {
                if (e.target.closest('.institution-header') || 
                    e.target.closest('.institution-name')) {
                    const institutionRow = e.target.closest('.institution-header') || 
                                         e.target.closest('.institution-name')?.closest('.institution-header');
                    if (institutionRow) {
                        const institutionName = institutionRow.querySelector('.institution-name')
                            .textContent.replace(/\(.*\)/, '').trim();
                        this.toggleInstitutionStudents(institutionRow, institutionName);
                    }
                }
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + F to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
                
                // Ctrl/Cmd + S to submit report
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const submitBtn = document.querySelector('.btn-submit');
                    if (submitBtn) {
                        submitBtn.click();
                    }
                }
            });
            
            // Update student count on page load
            const header = document.querySelector('.students-header h2');
            if (header) {
                const count = header.textContent.match(/\((\d+)\)/)?.[1] || '0';
                header.setAttribute('data-original-count', count);
            }
        },
        
        // Toggle institution students visibility
        toggleInstitutionStudents: function(institutionRow, institutionName) {
            const nextRow = institutionRow.nextElementSibling;
            if (nextRow && !nextRow.classList.contains('institution-header')) {
                let isHidden = nextRow.style.display === 'none';
                let currentRow = nextRow;
                
                while (currentRow && !currentRow.classList.contains('institution-header')) {
                    currentRow.style.display = isHidden ? '' : 'none';
                    currentRow = currentRow.nextElementSibling;
                }
                
                const icon = institutionRow.querySelector('.fa-university');
                if (icon) {
                    icon.className = isHidden ? 'fas fa-university' : 'fas fa-chevron-down';
                }
                
                this.showNotification(
                    `${institutionName} students ${isHidden ? 'expanded' : 'collapsed'}`,
                    'info'
                );
            }
        },
        
        // Setup animations
        setupAnimations: function() {
            // Add fade-in animation to summary cards
            const cards = document.querySelectorAll('.summary-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.opacity = '0';
                card.style.animation = 'fadeIn 0.5s ease forwards';
            });
        },
        
        // Show loading indicator
        showLoading: function(message) {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'loading-overlay';
            loadingDiv.innerHTML = `
                <div class="loading-content">
                    <div class="spinner"></div>
                    <p>${message || 'Loading...'}</p>
                </div>
            `;
            document.body.appendChild(loadingDiv);
        },
        
        // Hide loading indicator
        hideLoading: function() {
            const loadingDiv = document.querySelector('.loading-overlay');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type, 3000);
            } else {
                console.log(`${type.toUpperCase()}: ${message}`);
                // Fallback notification
                const notification = document.createElement('div');
                notification.className = `alert alert-${type}`;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    ${message}
                `;
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '10000';
                notification.style.animation = 'slideDown 0.3s ease';
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-20px)';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        },
        
        // Update summary counts (for real-time updates)
        updateSummaryCounts: function(data) {
            const elements = {
                'present-count': data.presentCount,
                'absent-count': data.absentCount,
                'late-count': data.lateCount,
                'expected-count': data.totalStudents
            };
            
            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    this.animateValueChange(element, value);
                }
            });
        },
        
        // Animate value change
        animateValueChange: function(element, newValue) {
            element.classList.add('updating');
            setTimeout(() => {
                element.textContent = newValue;
                setTimeout(() => {
                    element.classList.remove('updating');
                }, 300);
            }, 300);
        },
        
        // Public methods
        refresh: function() {
            location.reload();
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        Attendance.init();
        
    });
    
    // Make Attendance available globally
    window.Attendance = Attendance;
    
})();