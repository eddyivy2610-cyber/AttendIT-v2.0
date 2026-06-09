document.addEventListener('DOMContentLoaded', function() {
  
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const topNavToggle = document.getElementById('topNavToggle');
    const topNav = document.getElementById('topNav');
    const themeToggle = document.getElementById('themeToggle');
    const mobileThemeToggle = document.getElementById('mobileThemeToggle');
    const collapseToggle = document.getElementById('collapseToggle');
    const themeOverlay = document.getElementById('themeOverlay');
    const themeText = document.getElementById('themeText');
    const collapseText = document.getElementById('collapseText');
    const collapseIcon = collapseToggle ? collapseToggle.querySelector('.collapse-icon') : null;
    
    // Navigation elements
    const desktopNavItems = document.querySelectorAll('.nav-bar li a');
    const topNavItems = document.querySelectorAll('.top-nav-item');
    const sidebarButtons = document.querySelectorAll('.sidebar-btn');
    
    // 
    const sidebarTooltip = document.getElementById('sidebar-tooltip');
    
    let isThemeTransitioning = false;
    let isSidebarCollapsed = false;
    let isDarkTheme = localStorage.getItem('darkTheme') === 'true';
    let isMobile = window.innerWidth <= 1024;
    

    //
    function initialize() {
        applySavedStates();
        setupEventListeners();
        updateResponsiveState();
        setupTooltips();
    }
    
    function applySavedStates() {
        if (collapseToggle) {
            updateCollapseButton();
        }
        
        if (isDarkTheme) {
            enableDarkTheme();
        } else {
            enableLightTheme();
        }
        
        syncActiveNavigation();
    }
    
    function syncActiveNavigation() {
        const titleElement = document.querySelector('.page-title') || document.querySelector('.header-title');
        if (!titleElement) return;
        
        const currentPage = titleElement.textContent.toLowerCase();
        const pageMap = {
            'dashboard': 0,
            'students': 1,
            'attendance': 2,
            'projects': 3,
            'reports': 4,
            'settings': 5
        };
        
        if (pageMap[currentPage] !== undefined) {
            setActiveNavItem(pageMap[currentPage], 'desktop');
        }
    }
    
    function enableDarkTheme() {
        document.body.classList.add('dark-theme');
        themeToggle.querySelector('ion-icon').setAttribute('name', 'sunny-outline');
        if (mobileThemeToggle) {
            mobileThemeToggle.querySelector('ion-icon').setAttribute('name', 'sunny-outline');
        }
        themeText.textContent = 'Light UI';
        localStorage.setItem('darkTheme', 'true');
    }
    
    function enableLightTheme() {
        document.body.classList.remove('dark-theme');
        themeToggle.querySelector('ion-icon').setAttribute('name', 'contrast-outline');
        if (mobileThemeToggle) {
            mobileThemeToggle.querySelector('ion-icon').setAttribute('name', 'contrast-outline');
        }
        themeText.textContent = 'Dark UI';
        localStorage.setItem('darkTheme', 'false');
    }
    
    function toggleTheme() {
        if (isThemeTransitioning) return;
        
        isThemeTransitioning = true;
        
        if (themeOverlay) {
            themeOverlay.classList.add('active');
        }
       
        const themeIcon = themeToggle.querySelector('ion-icon');
        themeIcon.style.transform = 'rotate(180deg) scale(1.3)';
        
        setTimeout(() => {
            if (document.body.classList.contains('dark-theme')) {
                enableLightTheme();
            } else {
                enableDarkTheme();
            }
            
            
            themeIcon.style.transform = 'rotate(0) scale(1)';
            
            
            setTimeout(() => {
                if (themeOverlay) {
                    themeOverlay.classList.remove('active');
                }
                isThemeTransitioning = false;
            }, 300);
        }, 150);
    }
    
   
    // 
    function updateCollapseButton() {
        if (!isMobile) {
            if (isSidebarCollapsed) {
                if (collapseIcon) {
                    collapseIcon.setAttribute('name', 'chevron-forward-outline');
                    collapseIcon.style.transform = 'rotate(0)';
                }
                if (collapseText) collapseText.textContent = 'Expand';
            } else {
                if (collapseIcon) {
                    collapseIcon.setAttribute('name', 'chevron-back-outline');
                    collapseIcon.style.transform = 'rotate(0)';
                }
                if (collapseText) collapseText.textContent = 'Collapse';
            }
        }
    }
    
    function toggleSidebar() {
        if (isMobile) return; 
        
        isSidebarCollapsed = !isSidebarCollapsed;
        sidebar.classList.toggle('collapsed');
        
   
        localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
        
       
        updateCollapseButton();
    }
    
    function toggleMobileSidebar() {
        sidebar.classList.toggle('active');
    }
    
    
    // 
    function toggleTopNav() {
        topNav.classList.toggle('active');
        
       
        const icon = topNavToggle.querySelector('ion-icon');
        if (topNav.classList.contains('active')) {
            icon.setAttribute('name', 'close-outline');
            topNavToggle.style.transform = 'rotate(90deg) scale(1.1)';
        } else {
            icon.setAttribute('name', 'menu-outline');
            topNavToggle.style.transform = 'rotate(0) scale(1)';
        }
    }
    
   
    // 
    function setActiveNavItem(index, source) {
      
        desktopNavItems.forEach(item => {
            item.parentElement.classList.remove('active');
        });
        
        topNavItems.forEach(item => {
            item.classList.remove('active');
        });
        
       
        sidebarButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
      
        if (desktopNavItems[index]) {
            desktopNavItems[index].parentElement.classList.add('active');
        }
        
        if (topNavItems[index]) {
            topNavItems[index].classList.add('active');
        }
        
        
        if (index === 5) {
            const settingsBtn = document.querySelector('.settings-btn');
            if (settingsBtn) {
                settingsBtn.classList.add('active');
            }
        }
        
      
        if (source === 'mobile' && isMobile) {
            toggleTopNav();
        }
        
        
        if (isMobile && sidebar.classList.contains('active')) {
            toggleMobileSidebar();
        }
    }
    
    
    function setupTooltips() {
        if (isMobile) return;
        
        const navItems = document.querySelectorAll('.nav-bar li a, .sidebar-btn');
        
        navItems.forEach(item => {
            item.addEventListener('mouseenter', function(e) {
                if (sidebar.classList.contains('collapsed')) {
                    const tooltipText = this.getAttribute('data-tooltip') || 
                                      this.querySelector('span')?.textContent || 
                                      this.textContent;
                    
                    if (tooltipText) {
                        const rect = this.getBoundingClientRect();
                        sidebarTooltip.textContent = tooltipText;
                        sidebarTooltip.style.top = `${rect.top + window.scrollY}px`;
                        sidebarTooltip.style.left = `${rect.right + 10}px`;
                        sidebarTooltip.classList.add('show');
                    }
                }
            });
            
            item.addEventListener('mouseleave', function() {
                sidebarTooltip.classList.remove('show');
            });
        });
    }
    
  
    function updateResponsiveState() {
        isMobile = window.innerWidth <= 1024;
        
        if (isMobile) {
            
            if (sidebar) sidebar.classList.remove('collapsed');
            if (collapseIcon) collapseIcon.setAttribute('name', 'chevron-back-outline');
            if (collapseText) collapseText.textContent = 'Collapse';
        } else {
           
            if (isSidebarCollapsed) {
                if (sidebar) sidebar.classList.add('collapsed');
            } else {
                if (sidebar) sidebar.classList.remove('collapsed');
            }
        }
    }
    
    function handleResize() {
        updateResponsiveState();
        
        
        if (!isMobile && topNav && topNav.classList.contains('active')) {
            topNav.classList.remove('active');
            if (topNavToggle) {
                topNavToggle.querySelector('ion-icon').setAttribute('name', 'menu-outline');
                topNavToggle.style.transform = 'rotate(0) scale(1)';
            }
        }
        
        
        if (isMobile && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    }
   
    //
    function setupEventListeners() {
     
        themeToggle.addEventListener('click', toggleTheme);
        if (mobileThemeToggle) {
            mobileThemeToggle.addEventListener('click', toggleTheme);
        }
        
        if (collapseToggle) {
            collapseToggle.addEventListener('click', toggleSidebar);
        }
        
        if (topNavToggle) {
            topNavToggle.addEventListener('click', toggleTopNav);
        }
        
      
        desktopNavItems.forEach((item, index) => {
            item.addEventListener('click', function(e) {
                setActiveNavItem(index, 'desktop');
            });
        });
        
        
        topNavItems.forEach((item, index) => {
            if (item.id !== 'mobileThemeToggle') {
                item.addEventListener('click', function(e) {
                    setActiveNavItem(index, 'mobile');
                });
            }
        });
        
       
        document.addEventListener('click', function(event) {
        
            if (isMobile && 
                topNav &&
                topNav.classList.contains('active') && 
                !topNav.contains(event.target) && 
                (!topNavToggle || !topNavToggle.contains(event.target))) {
                toggleTopNav();
            }
            
           
            if (isMobile && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && 
                !event.target.closest('.top-nav-toggle')) {
                sidebar.classList.remove('active');
            }
        });
        
       
        window.addEventListener('resize', handleResize);
        
       
        document.addEventListener('keydown', function(e) {
            
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                if (!isMobile) {
                    toggleSidebar();
                }
            }
            
           
            if ((e.ctrlKey || e.metaKey) && e.key === 't') {
                e.preventDefault();
                toggleTheme();
            }
            
           
            if (e.key === 'Escape') {
                if (isMobile && topNav && topNav.classList.contains('active')) {
                    toggleTopNav();
                }
                if (isMobile && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
            
           
            if (e.altKey && e.key >= '1' && e.key <= '6') {
                e.preventDefault();
                const index = parseInt(e.key) - 1;
                if (index >= 0 && index <= 5) {
                    setActiveNavItem(index, 'desktop');
                   
                    const pages = ['dashboard', 'students', 'attendance', 'projects', 'reports', 'settings'];
                    window.location.href = `?page=${pages[index]}`;
                }
            }
        });
        
       
        sidebarButtons.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (!isMobile && !sidebar.classList.contains('collapsed')) {
                    this.style.transform = 'translateX(5px)';
                }
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    }
    
   
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.getElementById('notification');
        const messageElement = document.getElementById('notification-message');
        
        messageElement.textContent = message;
        
        // Set type-based styling
        notification.className = '';
        notification.classList.add(type);
        
       
        notification.classList.add('show');
        
        
        setTimeout(() => {
            hideNotification();
        }, duration);
    };
    
    window.hideNotification = function() {
        const notification = document.getElementById('notification');
        notification.classList.remove('show');
    };
    
   
    initialize();
});