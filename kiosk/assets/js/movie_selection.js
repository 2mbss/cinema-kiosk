/*
   MOVIE SELECTION PAGE JAVASCRIPT
   Handles movie selection, search, filtering, and navigation
*/

// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Get references to important elements
    const searchInput = document.getElementById('movieSearch');
    const ratingFilter = document.getElementById('ratingFilter');
    const moviesGrid = document.getElementById('moviesGrid');
    const movieCards = document.querySelectorAll('.movie-card');
    
    // Initialize search and filter functionality
    initializeSearch();
    initializeFilter();
    
    // Add click events to movie cards
    movieCards.forEach(card => {
        card.addEventListener('click', function() {
            const movieId = this.dataset.movieId;
            selectMovie(movieId);
        });
    });
});

/**
 * SEARCH FUNCTIONALITY
 * Filters movies based on title search
 */
function initializeSearch() {
    const searchInput = document.getElementById('movieSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterMovies();
        });
    }
}

/**
 * FILTER FUNCTIONALITY
 * Filters movies based on rating selection
 */
function initializeFilter() {
    const ratingFilter = document.getElementById('ratingFilter');
    
    if (ratingFilter) {
        ratingFilter.addEventListener('change', function() {
            filterMovies();
        });
    }
}

/**
 * FILTER MOVIES
 * Combines search and rating filter
 */
function filterMovies() {
    const searchTerm = document.getElementById('movieSearch').value.toLowerCase().trim();
    const selectedRating = document.getElementById('ratingFilter').value;
    const movieCards = document.querySelectorAll('.movie-card');
    
    let visibleCount = 0;
    
    movieCards.forEach(card => {
        const title = card.dataset.title.toLowerCase();
        const rating = card.dataset.rating;
        
        // Check if movie matches search term
        const matchesSearch = searchTerm === '' || title.includes(searchTerm);
        
        // Check if movie matches rating filter
        const matchesRating = selectedRating === '' || rating === selectedRating;
        
        // Show or hide the card
        if (matchesSearch && matchesRating) {
            card.style.display = 'block';
            card.style.animation = 'fadeInUp 0.4s ease-out forwards';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show "no results" message if no movies match
    showNoResultsMessage(visibleCount === 0);
}

/**
 * SHOW NO RESULTS MESSAGE
 * Displays message when no movies match the filter
 */
function showNoResultsMessage(show) {
    let noResultsDiv = document.getElementById('noResults');
    
    if (show && !noResultsDiv) {
        // Create no results message
        noResultsDiv = document.createElement('div');
        noResultsDiv.id = 'noResults';
        noResultsDiv.className = 'no-movies';
        noResultsDiv.innerHTML = `
            <h2>No Movies Found</h2>
            <p>Try adjusting your search or filter criteria</p>
            <button onclick="clearFilters()" class="retry-btn">Clear Filters</button>
        `;
        
        const moviesGrid = document.getElementById('moviesGrid');
        moviesGrid.parentNode.insertBefore(noResultsDiv, moviesGrid.nextSibling);
        
    } else if (!show && noResultsDiv) {
        // Remove no results message
        noResultsDiv.remove();
    }
}

/**
 * CLEAR ALL FILTERS
 * Resets search and filter to show all movies
 */
function clearFilters() {
    document.getElementById('movieSearch').value = '';
    document.getElementById('ratingFilter').value = '';
    filterMovies();
}

/**
 * SELECT MOVIE FUNCTION
 * Handles movie selection and navigation to movie details
 */
function selectMovie(movieId) {
    // Add visual feedback
    const selectedCard = document.querySelector(`[data-movie-id="${movieId}"]`);
    if (selectedCard) {
        selectedCard.style.transform = 'scale(0.95)';
        selectedCard.style.opacity = '0.7';
    }
    
    // Show loading state
    showLoading();
    
    // Simulate brief loading time for better UX
    setTimeout(() => {
        // Redirect to movie details page with movie ID
        window.location.href = `movie_details.php?id=${movieId}`;
    }, 500);
}

/**
 * SHOW LOADING STATE
 * Displays loading indicator during navigation
 */
function showLoading() {
    // Create loading overlay
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
            <div class="loading"></div>
            <p style="margin-top: 1rem;">Loading movie details...</p>
        </div>
    `;
    
    document.body.appendChild(loadingOverlay);
}

/**
 * GO BACK FUNCTION
 * Returns to home page with smooth transition
 */
function goBack() {
    // Add fade-out effect
    document.body.style.transition = 'opacity 0.5s ease-out';
    document.body.style.opacity = '0';
    
    // Navigate after animation
    setTimeout(() => {
        window.location.href = 'home.html';
    }, 500);
}

/**
 * KEYBOARD NAVIGATION
 * Adds keyboard support for accessibility
 */
document.addEventListener('keydown', function(event) {
    // ESC key - go back
    if (event.key === 'Escape') {
        goBack();
    }
    
    // Enter key on focused movie card
    if (event.key === 'Enter') {
        const focusedCard = document.activeElement;
        if (focusedCard && focusedCard.classList.contains('movie-card')) {
            const movieId = focusedCard.dataset.movieId;
            selectMovie(movieId);
        }
    }
});

/**
 * TOUCH SUPPORT FOR MOBILE
 * Adds touch feedback for mobile devices
 */
document.querySelectorAll('.movie-card').forEach(card => {
    // Touch start - add pressed effect
    card.addEventListener('touchstart', function() {
        this.style.transform = 'scale(0.98)';
    });
    
    // Touch end - remove pressed effect
    card.addEventListener('touchend', function() {
        this.style.transform = '';
    });
});

/*
   HOW THIS CODE WORKS:

   1. INITIALIZATION:
      - Sets up event listeners when page loads
      - Initializes search and filter functionality
      - Adds click events to movie cards

   2. SEARCH & FILTER:
      - Real-time search as user types
      - Filter by movie rating
      - Combines both filters simultaneously
      - Shows "no results" message when needed

   3. MOVIE SELECTION:
      - Adds visual feedback when movie is clicked
      - Shows loading indicator
      - Redirects to movie details page with movie ID

   4. NAVIGATION:
      - Back button returns to home page
      - Keyboard support (ESC to go back, Enter to select)
      - Smooth transitions between pages

   5. MOBILE SUPPORT:
      - Touch feedback for better mobile experience
      - Responsive design considerations
*/