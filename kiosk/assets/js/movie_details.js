/*
   MOVIE DETAILS PAGE JAVASCRIPT
   Handles navigation and trailer interactions
*/

// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize trailer functionality
    initializeTrailer();
    
    // Add keyboard navigation
    initializeKeyboardNavigation();
    
    // Add smooth scroll behavior
    initializeSmoothScroll();
    
});

/**
 * INITIALIZE TRAILER FUNCTIONALITY
 * Handles trailer video interactions and fallbacks
 */
function initializeTrailer() {
    const trailerVideo = document.querySelector('.trailer-video');
    
    if (trailerVideo) {
        // Handle iframe load errors
        trailerVideo.addEventListener('error', function() {
            console.log('Trailer failed to load');
            showTrailerError();
        });
        
        // Optional: Add click tracking for analytics
        trailerVideo.addEventListener('load', function() {
            console.log('Trailer loaded successfully');
        });
    }
}

/**
 * SHOW TRAILER ERROR
 * Displays fallback content if trailer fails to load
 */
function showTrailerError() {
    const trailerContainer = document.querySelector('.trailer-container');
    
    if (trailerContainer) {
        trailerContainer.innerHTML = `
            <div class="no-trailer">
                <div class="no-trailer-content">
                    <span class="no-trailer-icon">⚠️</span>
                    <h3>Trailer Unavailable</h3>
                    <p>Unable to load the trailer at this time</p>
                    <button onclick="retryTrailer()" class="retry-btn" style="
                        margin-top: 1rem;
                        padding: 0.5rem 1rem;
                        background: #4ecdc4;
                        color: white;
                        border: none;
                        border-radius: 15px;
                        cursor: pointer;
                    ">Try Again</button>
                </div>
            </div>
        `;
    }
}

/**
 * RETRY TRAILER LOADING
 * Reloads the page to retry trailer loading
 */
function retryTrailer() {
    location.reload();
}

/**
 * GO BACK FUNCTION
 * Returns to movie selection page with smooth transition
 */
function goBack() {
    // Add fade-out effect
    document.body.style.transition = 'opacity 0.5s ease-out';
    document.body.style.opacity = '0';
    
    // Navigate after animation
    setTimeout(() => {
        window.location.href = 'movies.php';
    }, 500);
}

/**
 * SELECT SHOWTIME FUNCTION
 * Navigates to showtime selection page with movie ID
 */
function selectShowtime(movieId) {
    // Add visual feedback
    const nextBtn = document.querySelector('.next-btn');
    if (nextBtn) {
        nextBtn.style.transform = 'scale(0.95)';
        nextBtn.innerHTML = 'Loading...';
        nextBtn.disabled = true;
    }
    
    // Show loading state
    showLoading();
    
    // Navigate to showtime selection
    setTimeout(() => {
        window.location.href = `showtimes.php?movie_id=${movieId}`;
    }, 800);
}

/**
 * SHOW LOADING STATE
 * Displays loading overlay during navigation
 */
function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loadingOverlay';
    loadingOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        color: white;
        font-size: 1.2rem;
    `;
    
    loadingOverlay.innerHTML = `
        <div style="text-align: center;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 1rem;">Loading showtimes...</p>
        </div>
    `;
    
    // Add loading spinner styles
    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(loadingOverlay);
}

/**
 * KEYBOARD NAVIGATION
 * Adds keyboard shortcuts for better accessibility
 */
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(event) {
        switch(event.key) {
            case 'Escape':
                // ESC key - go back
                goBack();
                break;
                
            case 'Enter':
                // Enter key - proceed to showtimes (if movie ID available)
                const movieId = getMovieIdFromUrl();
                if (movieId) {
                    selectShowtime(movieId);
                }
                break;
                
            case 'ArrowLeft':
                // Left arrow - go back
                goBack();
                break;
                
            case 'ArrowRight':
                // Right arrow - proceed to showtimes
                const movieIdRight = getMovieIdFromUrl();
                if (movieIdRight) {
                    selectShowtime(movieIdRight);
                }
                break;
        }
    });
}

/**
 * GET MOVIE ID FROM URL
 * Extracts movie ID from current page URL
 */
function getMovieIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

/**
 * INITIALIZE SMOOTH SCROLL
 * Adds smooth scrolling behavior for better UX
 */
function initializeSmoothScroll() {
    // Smooth scroll to trailer when page loads (if hash in URL)
    if (window.location.hash === '#trailer') {
        setTimeout(() => {
            const trailerSection = document.querySelector('.trailer-section');
            if (trailerSection) {
                trailerSection.scrollIntoView({ behavior: 'smooth' });
            }
        }, 500);
    }
}

/**
 * TRAILER CONTROLS (Optional Enhancement)
 * Adds custom controls for trailer interaction
 */
function addTrailerControls() {
    const trailerVideo = document.querySelector('.trailer-video');
    
    if (trailerVideo) {
        // Add fullscreen button
        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.innerHTML = '⛶ Fullscreen';
        fullscreenBtn.className = 'fullscreen-btn';
        fullscreenBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            z-index: 10;
        `;
        
        fullscreenBtn.addEventListener('click', function() {
            if (trailerVideo.requestFullscreen) {
                trailerVideo.requestFullscreen();
            }
        });
        
        const trailerContainer = document.querySelector('.trailer-container');
        if (trailerContainer) {
            trailerContainer.style.position = 'relative';
            trailerContainer.appendChild(fullscreenBtn);
        }
    }
}

/**
 * HANDLE POSTER IMAGE ERRORS
 * Provides fallback for missing poster images
 */
document.addEventListener('DOMContentLoaded', function() {
    const posterImage = document.querySelector('.movie-poster');
    
    if (posterImage) {
        posterImage.addEventListener('error', function() {
            // Replace with placeholder
            const placeholder = document.createElement('div');
            placeholder.className = 'placeholder-poster';
            placeholder.innerHTML = `
                <span>🎬</span>
                <p>No Poster Available</p>
            `;
            
            this.parentNode.replaceChild(placeholder, this);
        });
    }
});

/*
   HOW THIS CODE WORKS:

   1. TRAILER EMBEDDING:
      - PHP converts YouTube URLs to embed format
      - Uses iframe with YouTube embed API
      - Handles errors gracefully with fallback content
      - Supports various YouTube URL formats

   2. NAVIGATION:
      - Back button returns to movies.php
      - Next button goes to showtimes.php with movie ID
      - Smooth transitions with loading states
      - Keyboard shortcuts for accessibility

   3. ERROR HANDLING:
      - Trailer loading failures show retry option
      - Missing poster images show placeholder
      - Database errors redirect to movie selection

   4. USER EXPERIENCE:
      - Loading animations during navigation
      - Smooth scrolling and transitions
      - Responsive design for all devices
      - Keyboard navigation support

   5. YOUTUBE EMBED PROCESS:
      - Extract video ID from various URL formats
      - Convert to embed URL with parameters
      - Disable autoplay and related videos
      - Provide fallback for unavailable videos
*/