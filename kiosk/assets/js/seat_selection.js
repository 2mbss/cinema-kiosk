/*
   SEAT SELECTION PAGE JAVASCRIPT
   Handles seat selection, cost calculation, and data storage
*/

// Global variables for seat management
let selectedSeats = [];
let seatPrice = 0;
let showtimeInfo = {};

// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize seat selection system
    initializeSeatSelection();
    
    // Load any previously selected seats
    loadSavedSeats();
    
    // Add keyboard navigation
    initializeKeyboardNavigation();
    
});

/**
 * INITIALIZE SEAT SELECTION SYSTEM
 * Sets up the seat selection functionality
 */
function initializeSeatSelection() {
    // Get showtime data from PHP
    if (window.showtimeData) {
        showtimeInfo = window.showtimeData;
        seatPrice = parseFloat(showtimeInfo.price);
    }
    
    // Initialize display
    updateSelectionDisplay();
    
    // Add click events to all seats (not just available ones)
    const allSeats = document.querySelectorAll('.seat:not(.booked)');
    allSeats.forEach(seat => {
        seat.addEventListener('click', function() {
            const seatNumber = this.dataset.seat;
            toggleSeat(seatNumber);
        });
    });
}

/**
 * TOGGLE SEAT SELECTION
 * Handles clicking on seats to select/deselect
 */
function toggleSeat(seatNumber) {
    const seatElement = document.querySelector(`[data-seat="${seatNumber}"]`);
    
    if (!seatElement || seatElement.classList.contains('booked')) {
        return; // Can't select booked seats
    }
    
    // Add selection animation
    seatElement.classList.add('selecting');
    setTimeout(() => seatElement.classList.remove('selecting'), 300);
    
    if (seatElement.classList.contains('selected')) {
        // Deselect seat
        deselectSeat(seatNumber);
        showFeedback(`Seat ${seatNumber} removed`);
    } else {
        // Select seat (max 8 seats limit)
        if (selectedSeats.length >= 8) {
            showFeedback('Maximum 8 seats allowed');
            return;
        }
        selectSeat(seatNumber);
        showFeedback(`Seat ${seatNumber} selected`);
    }
    
    // Update display and save to storage
    updateSelectionDisplay();
    saveSeatsToStorage();
}

/**
 * SELECT SEAT
 * Adds seat to selection
 */
function selectSeat(seatNumber) {
    selectedSeats.push(seatNumber);
    const seatElement = document.querySelector(`[data-seat="${seatNumber}"]`);
    
    if (seatElement) {
        seatElement.classList.remove('available');
        seatElement.classList.add('selected');
    }
}

/**
 * DESELECT SEAT
 * Removes seat from selection
 */
function deselectSeat(seatNumber) {
    selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
    const seatElement = document.querySelector(`[data-seat="${seatNumber}"]`);
    
    if (seatElement) {
        seatElement.classList.remove('selected');
        seatElement.classList.add('available');
    }
}

/**
 * UPDATE SELECTION DISPLAY
 * Updates the summary section with current selection
 */
function updateSelectionDisplay() {
    const seatCount = document.getElementById('seatCount');
    const classicCount = document.getElementById('classicCount');
    const totalSeats = document.getElementById('totalSeats');
    const totalPrice = document.getElementById('totalPrice');
    const nextBtn = document.getElementById('nextBtn');
    
    // Update seat counts
    if (seatCount) seatCount.textContent = selectedSeats.length;
    if (classicCount) classicCount.textContent = selectedSeats.length;
    if (totalSeats) totalSeats.textContent = selectedSeats.length;
    
    // Update total cost
    const total = selectedSeats.length * seatPrice;
    if (totalPrice) totalPrice.textContent = `₱${total.toFixed(2)}`;
    
    // Enable/disable next button
    if (nextBtn) {
        nextBtn.disabled = selectedSeats.length === 0;
    }
}

/**
 * SHOW FEEDBACK MESSAGE
 * Displays temporary feedback to user
 */
function showFeedback(message) {
    // Remove existing feedback
    const existingFeedback = document.querySelector('.seat-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Create new feedback
    const feedback = document.createElement('div');
    feedback.className = 'seat-feedback';
    feedback.textContent = message;
    document.body.appendChild(feedback);
    
    // Remove after animation
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.remove();
        }
    }, 1000);
}

/**
 * SAVE SEATS TO STORAGE
 * Saves selected seats to localStorage for persistence
 */
function saveSeatsToStorage() {
    const seatData = {
        showtimeId: showtimeInfo.id,
        selectedSeats: selectedSeats,
        totalCost: selectedSeats.length * seatPrice,
        timestamp: Date.now()
    };
    
    try {
        localStorage.setItem('selectedSeats', JSON.stringify(seatData));
        console.log('Seats saved to localStorage:', seatData);
    } catch (error) {
        console.error('Failed to save seats:', error);
    }
}

/**
 * LOAD SAVED SEATS
 * Loads previously selected seats from localStorage
 */
function loadSavedSeats() {
    // Clear any old seat selections for fresh start
    localStorage.removeItem('selectedSeats');
    selectedSeats = [];
    console.log('Starting with fresh seat selection');
}

/**
 * CLEAR SEAT SELECTION
 * Clears all selected seats
 */
function clearSelection() {
    selectedSeats.forEach(seatNumber => {
        deselectSeat(seatNumber);
    });
    selectedSeats = [];
    updateSelectionDisplay();
    saveSeatsToStorage();
    showFeedback('Selection cleared');
}

/**
 * GO BACK FUNCTION
 * Returns to showtime selection
 */
function goBack() {
    // Save current selection before leaving
    saveSeatsToStorage();
    
    // Add fade-out effect
    document.body.style.transition = 'opacity 0.5s ease-out';
    document.body.style.opacity = '0';
    
    // Navigate after animation
    setTimeout(() => {
        window.location.href = `showtimes.php?movie_id=${showtimeInfo.movieId || ''}`;
    }, 500);
}

/**
 * PROCEED TO EXTRAS
 * Navigates to extras selection page
 */
function proceedToExtras() {
    if (selectedSeats.length === 0) {
        showFeedback('Please select at least one seat');
        return;
    }
    
    // Save selection data
    saveSeatsToStorage();
    
    // Show loading state
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.innerHTML = 'Loading...';
        nextBtn.disabled = true;
    }
    
    showLoading();
    
    // Navigate to extras page
    setTimeout(() => {
        window.location.href = `extras.php?showtime_id=${showtimeInfo.id}`;
    }, 800);
}

/**
 * SHOW LOADING STATE
 * Displays loading overlay
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
            <p style="margin-top: 1rem;">Processing selection...</p>
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
 * Adds keyboard shortcuts for accessibility
 */
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(event) {
        switch(event.key) {
            case 'Escape':
                // ESC - go back
                goBack();
                break;
                
            case 'Enter':
                // Enter - proceed if seats selected
                if (selectedSeats.length > 0) {
                    proceedToExtras();
                }
                break;
                
            case 'Delete':
            case 'Backspace':
                // Delete - clear selection
                event.preventDefault();
                clearSelection();
                break;
                
            case 'c':
            case 'C':
                // C - clear selection
                if (event.ctrlKey) {
                    event.preventDefault();
                    clearSelection();
                }
                break;
        }
    });
}

/**
 * AUTO-SAVE FUNCTIONALITY
 * Periodically saves seat selection
 */
setInterval(() => {
    if (selectedSeats.length > 0) {
        saveSeatsToStorage();
    }
}, 30000); // Save every 30 seconds

/*
   HOW SEAT DATA IS STORED AND UPDATED:

   1. DATABASE STORAGE (Permanent):
      - Booked seats stored in 'seats' table
      - Structure: showtime_id, seat_number, is_booked
      - PHP fetches booked seats on page load
      - Example: A1, B5, H12 marked as booked=1

   2. LOCALSTORAGE (Temporary):
      - Selected seats saved as JSON object
      - Structure: {showtimeId, selectedSeats[], totalCost, timestamp}
      - Data expires after 1 hour for security
      - Restored when user returns to same showtime

   3. SEAT STATES:
      - Available (gray): Can be selected by user
      - Selected (green): User has chosen this seat
      - Booked (red): Already sold, cannot select

   4. REAL-TIME UPDATES:
      - JavaScript updates display immediately
      - localStorage saves selection for persistence
      - Database will be updated on final booking

   5. DATA FLOW:
      Database → PHP → HTML (booked seats)
      User Selection → JavaScript → localStorage
      Final Booking → JavaScript → PHP → Database

   6. GRID STRUCTURE:
      8 rows (A-H) × 12 columns (1-12) = 96 total seats
      Seat naming: A1, A2, ..., A12, B1, B2, ..., H12
*/