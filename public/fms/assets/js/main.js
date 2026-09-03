/**
 * Faculty Management System (FMS)
 * Core Client-Side Utilities
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Mobile Sidebar Toggle & Backdrop
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.btn-sidebar-toggle');
    
    // Create backdrop element if it doesn't exist
    let backdrop = document.querySelector('.sidebar-backdrop');
    if (!backdrop && sidebar) {
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
    }
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            if (backdrop) backdrop.classList.toggle('active');
        });
    }
    
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('active');
            backdrop.classList.remove('active');
        });
    }

    // 2. Destructive Action Confirmation Dialogs
    const deleteButtons = document.querySelectorAll('[data-confirm], .btn-confirm-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to permanently delete this item? This action cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 3. Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 400);
            });
        }, 6000);
    }
});
