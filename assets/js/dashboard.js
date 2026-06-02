/**
 * Attendit - Dashboard JavaScript
 * Handles dashboard-specific functionality including real-time updates
 */

(function() {
    'use strict';

    const Dashboard = {
        config: {
            refreshInterval: 30000, // 30 seconds
            apiEndpoint: '/api/dashboard-data',
            animationDuration: 300
        },

        init: function() {
            console.log('Dashboard initialized');
            this.loadDashboardData();
            this.setupAutoRefresh();
            this.setupEventListeners();
            this.setupAnimations();
        },

        setupAutoRefresh: function() {
            setInterval(() => {
                this.loadDashboardData();
            }, this.config.refreshInterval);
        },

        loadDashboardData: function() {
            fetch(this.config.apiEndpoint)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    this.updateDashboardCards(data);
                    this.updateChangeIndicators(data);
                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                });
        },

        updateDashboardCards: function(data) {
            if (data.totalStudents !== undefined) {
                this.animateValue('total-students', data.totalStudents);
            }
            if (data.attendanceRate !== undefined) {
                this.animateValue('attendance-rate', data.attendanceRate + '%');
            }
            if (data.activeStudents !== undefined) {
                this.animateValue('active-students', data.activeStudents);
            }
            if (data.activeProjects !== undefined) {
                this.animateValue('active-projects', data.activeProjects);
            }
        },

        animateValue: function(elementId, newValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            element.parentElement.classList.add('card-updating');
            setTimeout(() => {
                element.textContent = newValue;
                setTimeout(() => {
                    element.parentElement.classList.remove('card-updating');
                }, this.config.animationDuration);
            }, this.config.animationDuration);
        },

        updateChangeIndicators: function(data) {
            console.log('Updating change indicators with data:', data);
        },

        setupEventListeners: function() {
            const cards = document.querySelectorAll('.dashboard-cards .card');
            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.target.tagName === 'A' || e.target.closest('a')) return;
                    card.classList.add('card-updating');
                    setTimeout(() => {
                        card.classList.remove('card-updating');
                    }, 1000);
                    this.loadDashboardData();
                });
            });

            const actionCards = document.querySelectorAll('.action-card-link');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    this.loadDashboardData();
                }
            });
        },

        setupAnimations: function() {
            const cards = document.querySelectorAll('.dashboard-cards .card, .action-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        },

        showNotification: function(message, type = 'info') {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type, 3000);
            } else {
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        },

        refresh: function() {
            this.loadDashboardData();
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        Dashboard.init();
    });

    window.Dashboard = Dashboard;
    window.updateDashboardData = function() { Dashboard.loadDashboardData(); };

})();