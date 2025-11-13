const toggle = document.querySelector('.toggle');
const nav = document.querySelector('.navigation');
const theme = document.querySelector('.theme');
const container = document.querySelector('.container');
const icon = document.getElementById('collapse');
const mode = theme.querySelector('ion-icon');
const main = document.querySelector('.main-content');
const display = document.getElementById('display')

toggle.addEventListener('click', () => {
    nav.classList.toggle('active');
    main.classList.toggle('active');
    if(nav.classList.contains('active')){
        icon.classList.remove('bx-arrow-from-right-stroke');
        icon.classList.add('bx-arrow-from-left-stroke');
    }else{
        icon.classList.toggle('bx-arrow-from-right-stroke');
    }
});

//Tooltip functionality
let navLinks = document.querySelectorAll('.nav-bar li a[data-tooltip]');
let tooltip = document.getElementById('sidebar-tooltip');

navLinks.forEach(link => {
  link.addEventListener('mouseenter', function(e) {
    if (nav.classList.contains('active')) {
      tooltip.textContent = link.getAttribute('data-tooltip');
      const rect = link.getBoundingClientRect();
      tooltip.style.top = rect.top + window.scrollY + 'px';
      tooltip.classList.add('active');
    }
  });
  link.addEventListener('mouseleave', function(e) {
    tooltip.classList.remove('active');
  });
});

toggle.onclick = function () {
  navigation.classList.toggle("active");
  main.classList.toggle("active");
  tooltip.classList.remove('active'); // Hide tooltip when toggling
};



// ----- THEME TOGGLE -----
theme.addEventListener('click', () => {
  container.classList.toggle('dark');

  if (container.classList.contains('dark')) {
    mode?.setAttribute('name', 'contrast');
    display.textContent = 'Light UI';
    localStorage.setItem('theme', 'dark');
  } else {
    mode?.setAttribute('name', 'contrast-outline');
    display.textContent = 'Dark UI';
    localStorage.setItem('theme', 'light');
  }
});

// ----- PAGE INITIALIZATION -----
window.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('theme');

  if (savedTheme) {
    
    if (savedTheme === 'dark') {
      container.classList.add('dark');
      mode?.setAttribute('name', 'contrast');
      display.textContent = 'Light UI';
    } else {
      container.classList.remove('dark');
      mode?.setAttribute('name', 'contrast-outline');
      display.textContent = 'Dark UI';
    }
  } else {
  
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      container.classList.add('dark');
      mode?.setAttribute('name', 'contrast');
      display.textContent = 'Light UI';
    }
  }
});

window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", e => {
  if (!localStorage.getItem("theme")) {
    if (e.matches) container.classList.add("dark");
    else container.classList.remove("dark");
  }
});


// ===== PAGE INITIALIZATION =====
let pageInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    if (pageInitialized) {
        console.log('Page already initialized, skipping');
        return;
    }
    pageInitialized = true;
    
    console.log('DOM loaded, initializing page');
    
    initMenuToggle();
    
    updateCurrentDate();
    
    initializeCurrentPage();
    
    setupNavigation();
    
    console.log('Page initialization complete');
});

// Simple date update
function updateCurrentDate() {
    const currentDateElement = document.getElementById('currentDate');
    if (currentDateElement) {
        currentDateElement.textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}

// Initialize the current page
function initializeCurrentPage() {
    const currentPage = '<?php echo $page; ?>';
    console.log('Initializing page:', currentPage);
    
    setActiveNav(currentPage);
    
    initPageFunctionality(currentPage);
}

 // Set active navigation
function setActiveNav(page) {
    console.log('Setting active nav for:', page);

    document.querySelectorAll('.navigation li').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`.navigation a[data-page="${page}"]`);
    if (activeLink && activeLink.parentElement) {
        activeLink.parentElement.classList.add('active');
        console.log('Active nav set to:', page);
    } else {
        console.log('Active link not found for page:', page);
    }
}

// Initialize specific page functionality
function initPageFunctionality(page) {
        console.log('Initializing functionality for:', page);
        switch(page) {
            case 'students':
                initStudents();
                break;
            case 'attendance':
                initAttendance();
                break;
            case 'reports':
                initReports();
                break;
            case 'dashboard':
                initDashboard();
                break;
            case 'settings':
                initSettings();
                break;
            case 'projects':
                initAbout();
                break;
            default:
                console.log('No specific initialization for page:', page);
        }
    }

    // ===== NAVIGATION =====
    function setupNavigation() {
        console.log('Setting up navigation');
        
        document.addEventListener('click', function(e) {

            // Navigation links 
            const navLink = e.target.closest('.navigation a[data-page]');
            if (navLink) {
                e.preventDefault();
                e.stopPropagation();
                const page = navLink.getAttribute('data-page');
                console.log('Navigation link clicked:', page);
                navigateTo(page);
                return;
            }
            
            // Links
            const pageLink = e.target.closest('a[href*="?page="]');
            if (pageLink) {
                e.preventDefault();
                e.stopPropagation();
                const href = pageLink.getAttribute('href');
                const pageMatch = href.match(/[?&]page=([^&]*)/);
                if (pageMatch && pageMatch[1]) {
                    console.log('Page link clicked:', pageMatch[1]);
                    navigateTo(pageMatch[1]);
                }
                return;
            }
        });
    }

    // Reload
    function navigateTo(page) {
        console.log('Navigating to:', page);
        showLoading();
        
       
        setTimeout(function() {
            window.location.href = `?page=${page}`;
        }, 100);
    }

    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type = 'info') {
        console.log('Notification:', message, type);
        
        let notification = document.getElementById('notification');
        let messageEl = document.getElementById('notification-message');
        
        if (!notification || !messageEl) {
            console.log('Creating notification element');
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.innerHTML = `
                <span id="notification-message"></span>
                <button onclick="hideNotification()">×</button>
            `;
            document.body.appendChild(notification);
            messageEl = document.getElementById('notification-message');
        }
        
        if (messageEl) {
            messageEl.textContent = message;
        }
        
        // Set color based on type
        const colors = {
            info: '#3498db',
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12'
        };
        notification.style.backgroundColor = colors[type] || colors.info;
        notification.className = type; // Add type as class for styling
        
        // Show notification
        notification.style.display = 'flex';
        notification.classList.add('show');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideNotification();
        }, 5000);
    }

    function hideNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.classList.add('fade-out');
            setTimeout(function() {
                notification.style.display = 'none';
                notification.classList.remove('show', 'fade-out');
            }, 300);
        }
    }

    // ===== LOADING FUNCTIONS =====
    function showLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'flex';
        }
    }

    function hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    window.navigateTo = navigateTo;
    window.showNotification = showNotification;
    window.hideNotification = hideNotification;
    window.handleSignIn = handleSignIn;
    window.handleSignOut = handleSignOut;
    window.markAllSignedIn = markAllSignedIn;
    window.resetAllAttendance = resetAllAttendance;