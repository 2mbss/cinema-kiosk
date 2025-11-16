/**
 * KIOSK IDLE TIMEOUT SYSTEM
 * Automatically redirects to home page after 30 seconds of inactivity
 */

(function() {
    'use strict';
    
    // Configuration
    const IDLE_TIME = 130000; // 1:30min seconds in milliseconds
    const HOME_URL = 'index.html'; // Change to your home page
    
    let idleTimer;
    let isIdle = false;
    
    // Events that indicate user activity
    const activityEvents = [
        'mousedown', 'mousemove', 'keypress', 'scroll', 
        'touchstart', 'click', 'keydown', 'keyup',
        'touchmove', 'touchend', 'wheel'
    ];
    
    /**
     * Reset the idle timer
     */
    function resetIdleTimer() {
        // Clear existing timer
        clearTimeout(idleTimer);
        isIdle = false;
        
        // Start new timer
        idleTimer = setTimeout(function() {
            redirectToHome();
        }, IDLE_TIME);
    }
    
    /**
     * Redirect to home page
     */
    function redirectToHome() {
        isIdle = true;
        
        // Get current page to determine correct home path
        const currentPath = window.location.pathname;
        let homePath = HOME_URL;
        
        // Adjust path based on current directory structure
        if (currentPath.includes('/kiosk/')) {
            homePath = HOME_URL; // Same directory
        } else if (currentPath.includes('/admin/')) {
            return; // Don't redirect admin pages
        }
        
        // Redirect to home
        window.location.href = homePath;
    }
    
    /**
     * Initialize the idle timeout system
     */
    function initIdleTimeout() {
        // Add event listeners for all activity events
        activityEvents.forEach(function(event) {
            document.addEventListener(event, resetIdleTimer, true);
        });
        
        // Start the initial timer
        resetIdleTimer();
        
        // Handle page visibility changes (tab switching)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                resetIdleTimer();
            }
        });
    }
    
    /**
     * Clean up function (optional)
     */
    function destroyIdleTimeout() {
        clearTimeout(idleTimer);
        activityEvents.forEach(function(event) {
            document.removeEventListener(event, resetIdleTimer, true);
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIdleTimeout);
    } else {
        initIdleTimeout();
    }
    
    // Expose cleanup function globally (optional)
    window.kioskIdleTimeout = {
        destroy: destroyIdleTimeout,
        reset: resetIdleTimer
    };
    
})();