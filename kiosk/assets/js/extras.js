/*
   EXTRAS PAGE JAVASCRIPT
   Handles add-on selection, quantity management, and order summary
*/

// Global variables
let selectedExtras = {};
let showtimeInfo = {};
let selectedSeats = [];
let seatPrice = 0;

// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize extras system
    initializeExtras();
    
    // Load saved seat data
    loadSeatData();
    
    // Update display
    updateOrderSummary();
});

/**
 * INITIALIZE EXTRAS SYSTEM
 */
function initializeExtras() {
    // Get showtime data from PHP
    if (window.showtimeData) {
        showtimeInfo = window.showtimeData;
        seatPrice = parseFloat(showtimeInfo.price);
    }
    
    // Initialize selected extras object
    if (window.extrasData) {
        window.extrasData.forEach(extra => {
            selectedExtras[extra.id] = {
                id: extra.id,
                name: extra.name,
                price: parseFloat(extra.price),
                quantity: 0
            };
        });
    }
}

/**
 * LOAD SEAT DATA FROM STORAGE
 */
function loadSeatData() {
    try {
        const seatData = localStorage.getItem('selectedSeats');
        if (seatData) {
            const data = JSON.parse(seatData);
            selectedSeats = data.selectedSeats || [];
        }
    } catch (error) {
        console.error('Failed to load seat data:', error);
        selectedSeats = [];
    }
}

/**
 * FILTER CATEGORY
 */
function filterCategory(category) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Show/hide items
    document.querySelectorAll('.item-card').forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

/**
 * CHANGE QUANTITY
 */
function changeQuantity(extraId, change) {
    const currentQty = selectedExtras[extraId].quantity;
    const newQty = Math.max(0, currentQty + change);
    
    selectedExtras[extraId].quantity = newQty;
    
    // Update display
    document.getElementById(`qty-${extraId}`).textContent = newQty;
    
    // Update order summary
    updateOrderSummary();
    
    // Save to storage
    saveExtrasToStorage();
}

/**
 * UPDATE ORDER SUMMARY
 */
function updateOrderSummary() {
    // Update seat info
    const seatCount = selectedSeats.length;
    const seatTotal = seatCount * seatPrice;
    const seatInfoElement = document.getElementById('seatInfo');
    const seatMainElement = seatInfoElement.querySelector('.seat-main');
    const seatSubElement = seatInfoElement.querySelector('.seat-sub');
    
    seatMainElement.innerHTML = `<span>Ticket x${seatCount}</span><span>₱${seatTotal.toFixed(2)}</span>`;
    
    if (seatCount > 0) {
        seatSubElement.textContent = `(Seats: ${selectedSeats.join(', ')})`;
    } else {
        seatSubElement.textContent = '(Seats: None)';
    }
    
    // Update extras summary
    const extrasSummary = document.getElementById('extrasSummary');
    extrasSummary.innerHTML = '';
    
    let extrasTotal = 0;
    
    Object.values(selectedExtras).forEach(extra => {
        if (extra.quantity > 0) {
            const itemTotal = extra.quantity * extra.price;
            extrasTotal += itemTotal;
            
            const extraDiv = document.createElement('div');
            extraDiv.className = 'extra-item';
            extraDiv.innerHTML = `
                <span>${extra.name} x${extra.quantity}</span>
                <span>₱${itemTotal.toFixed(2)}</span>
            `;
            extrasSummary.appendChild(extraDiv);
        }
    });
    
    // Update total
    const grandTotal = seatTotal + extrasTotal;
    document.getElementById('totalAmount').textContent = `₱${grandTotal.toFixed(2)}`;
}

/**
 * SAVE EXTRAS TO STORAGE
 */
function saveExtrasToStorage() {
    const extrasData = {
        showtimeId: showtimeInfo.id,
        selectedExtras: selectedExtras,
        timestamp: Date.now()
    };
    
    try {
        localStorage.setItem('selectedExtras', JSON.stringify(extrasData));
    } catch (error) {
        console.error('Failed to save extras:', error);
    }
}

/**
 * GO BACK FUNCTION
 */
function goBack() {
    // Save current selection before leaving
    saveExtrasToStorage();
    
    // Navigate back to seat selection
    window.location.href = `seat_selection.php?showtime_id=${showtimeInfo.id}`;
}

/**
 * PROCEED TO CHECKOUT
 */
function proceedToCheckout() {
    // Save selection data
    saveExtrasToStorage();
    
    // Navigate to checkout
    window.location.href = `checkout.php?showtime_id=${showtimeInfo.id}`;
}