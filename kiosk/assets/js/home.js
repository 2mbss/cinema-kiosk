/*
   HOME PAGE JAVASCRIPT
   This file handles the click interaction and page transition
*/

// Wait for the page to fully load before running our code
document.addEventListener('DOMContentLoaded', function() {
    
    // Get references to important elements
    const homeContainer = document.getElementById('homeContainer');
    const video = document.querySelector('.background-video');
    
    // Handle video loading errors (show fallback background)
    video.addEventListener('error', function() {
        console.log('Video failed to load, using fallback background');
        video.style.display = 'none'; // Hide broken video
    });
    
    // Add click event listener to the entire container
    homeContainer.addEventListener('click', function() {
        startTransition();
    });
    
    // Also listen for keyboard events (accessibility)
    document.addEventListener('keydown', function(event) {
        // If user presses Enter or Space, start transition
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault(); // Prevent default browser behavior
            startTransition();
        }
    });
    
    // Function to handle the transition to the next page
    function startTransition() {
        // Add fade-out animation class
        homeContainer.classList.add('fade-out');
        
        // Wait for animation to complete, then redirect
        setTimeout(function() {
            // Redirect to movie selection page
            window.location.href = 'movies.php';
        }, 800); // 800ms matches the CSS animation duration
    }
    
    // Optional: Add some visual feedback when user hovers (for desktop)
    homeContainer.addEventListener('mouseenter', function() {
        // Slightly brighten the overlay on hover
        const overlay = document.querySelector('.overlay');
        overlay.style.background = 'rgba(0, 0, 0, 0.3)';
    });
    
    homeContainer.addEventListener('mouseleave', function() {
        // Return to normal overlay
        const overlay = document.querySelector('.overlay');
        overlay.style.background = 'rgba(0, 0, 0, 0.4)';
    });
    
    // Auto-start video if it was paused
    video.addEventListener('canplay', function() {
        video.play().catch(function(error) {
            console.log('Video autoplay failed:', error);
        });
    });
    
});

/*
   HOW THIS CODE WORKS:

   1. DOMContentLoaded: Waits for HTML to load before running JavaScript
   
   2. Event Listeners: 
      - Click anywhere on screen → triggers transition
      - Keyboard (Enter/Space) → triggers transition (accessibility)
      - Video error → hides video, shows fallback background
   
   3. startTransition(): 
      - Adds 'fade-out' CSS class for smooth animation
      - Waits 800ms for animation to complete
      - Redirects to movies.html page
   
   4. Hover Effects: 
      - Makes overlay slightly lighter on mouse hover
      - Provides visual feedback for desktop users
   
   5. Video Handling:
      - Ensures video plays automatically
      - Handles errors gracefully with fallback
*/