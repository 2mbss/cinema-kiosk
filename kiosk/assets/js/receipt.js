/*
   RECEIPT PAGE JAVASCRIPT
   Handles QR code generation and receipt functionality
*/

// Global variables
let receiptData = {};

// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize receipt system
    initializeReceipt();
    
    // Generate QR code
    generateQRCode();
    
    // Add keyboard navigation
    initializeKeyboardNavigation();
    
    // Auto-focus for accessibility
    document.body.focus();
    
});

/**
 * INITIALIZE RECEIPT SYSTEM
 * Sets up the receipt functionality
 */
function initializeReceipt() {
    // Get receipt data from PHP
    if (window.receiptData) {
        receiptData = window.receiptData;
        console.log('Receipt data loaded:', receiptData);
    }
    
    // Clear any remaining order data from localStorage
    localStorage.removeItem('selectedSeats');
    localStorage.removeItem('selectedExtras');
    localStorage.removeItem('completeOrder');
    
    // Save receipt data for future reference
    saveReceiptData();
}

/**
 * GENERATE QR CODE
 * Creates QR code linking to this receipt
 */
function generateQRCode() {
    const canvas = document.getElementById('qrcode');
    
    if (!canvas || !receiptData.receiptUrl) {
        console.error('QR code generation failed: Missing canvas or URL');
        showQRError();
        return;
    }
    
    try {
        // Use simple QR code generation
        const qr = qrcode(0, 'M');
        qr.addData(receiptData.receiptUrl);
        qr.make();
        
        // Create QR code as image
        const qrImage = qr.createImgTag(4, 8);
        
        // Replace canvas with image
        const qrContainer = canvas.parentNode;
        qrContainer.innerHTML = qrImage + '<p class="qr-text">Scan to view this receipt online</p>';
        
        // Style the image
        const img = qrContainer.querySelector('img');
        if (img) {
            img.style.cssText = `
                border: 2px solid #4ecdc4;
                border-radius: 8px;
                padding: 1rem;
                background: white;
                width: 200px;
                height: 200px;
            `;
        }
        
        console.log('QR code generated successfully');
        animateQRCode();
        
    } catch (error) {
        console.error('QR code generation error:', error);
        showQRError();
    }
}

/**
 * SHOW QR ERROR
 * Displays fallback when QR generation fails
 */
function showQRError() {
    const qrContainer = document.querySelector('.qr-container');
    
    if (qrContainer) {
        qrContainer.innerHTML = `
            <div style="
                width: 200px;
                height: 200px;
                border: 2px solid #4ecdc4;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f9f9f9;
                color: #666;
                text-align: center;
                padding: 1rem;
            ">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📱</div>
                    <div>QR Code<br>Unavailable</div>
                </div>
            </div>
            <p class="qr-text">Receipt #${receiptData.saleId}</p>
        `;
    }
}

/**
 * ANIMATE QR CODE
 * Adds entrance animation to QR code
 */
function animateQRCode() {
    const qrContainer = document.querySelector('.qr-container');
    if (qrContainer) {
        const img = qrContainer.querySelector('img');
        if (img) {
            img.style.animation = 'fadeIn 1s ease-out';
        }
    }
}

/**
 * SAVE RECEIPT DATA
 * Saves receipt information to localStorage for future access
 */
function saveReceiptData() {
    const receiptInfo = {
        saleId: receiptData.saleId,
        movieTitle: receiptData.movieTitle,
        showDate: receiptData.showDate,
        showTime: receiptData.showTime,
        seats: receiptData.seats,
        total: receiptData.total,
        timestamp: Date.now()
    };
    
    try {
        localStorage.setItem('lastReceipt', JSON.stringify(receiptInfo));
        console.log('Receipt data saved for future reference');
    } catch (error) {
        console.error('Failed to save receipt data:', error);
    }
}

/**
 * PRINT RECEIPT
 * Handles receipt printing
 */
function printReceipt() {
    // Show loading feedback
    const printBtn = document.querySelector('.print-btn');
    const originalText = printBtn.innerHTML;
    printBtn.innerHTML = '🖨️ Preparing...';
    printBtn.disabled = true;
    
    // Brief delay for better UX
    setTimeout(() => {
        // Trigger browser print dialog
        window.print();
        
        // Reset button after print dialog
        setTimeout(() => {
            printBtn.innerHTML = originalText;
            printBtn.disabled = false;
        }, 1000);
    }, 500);
}

/**
 * GO HOME FUNCTION
 * Returns to the main kiosk home page
 */
function goHome() {
    // Show confirmation for better UX
    showFeedback('Thank you for using Cinema Kiosk!');
    
    // Add fade-out effect
    document.body.style.transition = 'opacity 0.8s ease-out';
    document.body.style.opacity = '0';
    
    // Navigate after animation
    setTimeout(() => {
        window.location.href = 'home.html';
    }, 800);
}

/**
 * SHOW FEEDBACK MESSAGE
 * Displays temporary feedback to user
 */
function showFeedback(message) {
    // Remove existing feedback
    const existingFeedback = document.querySelector('.receipt-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Create new feedback
    const feedback = document.createElement('div');
    feedback.className = 'receipt-feedback';
    feedback.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(78, 205, 196, 0.95);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 25px;
        z-index: 1000;
        font-weight: bold;
        font-size: 1.1rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: feedbackPulse 2s ease-out;
    `;
    feedback.textContent = message;
    
    document.body.appendChild(feedback);
    
    // Remove after animation
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.remove();
        }
    }, 2000);
}

/**
 * KEYBOARD NAVIGATION
 * Adds keyboard shortcuts for receipt actions
 */
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(event) {
        switch(event.key) {
            case 'Escape':
            case 'Home':
                // ESC or Home - go to home page
                goHome();
                break;
                
            case 'p':
            case 'P':
                // P - print receipt
                if (event.ctrlKey) {
                    event.preventDefault();
                    printReceipt();
                }
                break;
                
            case 'Enter':
                // Enter - go home (default action)
                goHome();
                break;
        }
    });
}

/**
 * SHARE RECEIPT
 * Allows sharing receipt URL (future enhancement)
 */
function shareReceipt() {
    if (navigator.share && receiptData.receiptUrl) {
        navigator.share({
            title: `Cinema Kiosk Receipt #${receiptData.saleId}`,
            text: `Movie: ${receiptData.movieTitle} - ${receiptData.showDate} ${receiptData.showTime}`,
            url: receiptData.receiptUrl
        }).then(() => {
            showFeedback('Receipt shared successfully!');
        }).catch((error) => {
            console.log('Share failed:', error);
            copyReceiptUrl();
        });
    } else {
        copyReceiptUrl();
    }
}

/**
 * COPY RECEIPT URL
 * Copies receipt URL to clipboard
 */
function copyReceiptUrl() {
    if (receiptData.receiptUrl) {
        navigator.clipboard.writeText(receiptData.receiptUrl).then(() => {
            showFeedback('Receipt URL copied to clipboard!');
        }).catch((error) => {
            console.error('Copy failed:', error);
            showFeedback('Unable to copy URL');
        });
    }
}

/**
 * AUTO-CLEANUP
 * Cleans up old receipt data periodically
 */
function cleanupOldReceipts() {
    try {
        const lastReceipt = localStorage.getItem('lastReceipt');
        if (lastReceipt) {
            const receiptInfo = JSON.parse(lastReceipt);
            const daysSinceReceipt = (Date.now() - receiptInfo.timestamp) / (1000 * 60 * 60 * 24);
            
            // Remove receipts older than 30 days
            if (daysSinceReceipt > 30) {
                localStorage.removeItem('lastReceipt');
                console.log('Old receipt data cleaned up');
            }
        }
    } catch (error) {
        console.error('Cleanup error:', error);
    }
}

// Run cleanup on page load
cleanupOldReceipts();

// Add CSS for feedback animation
const style = document.createElement('style');
style.textContent = `
    @keyframes feedbackPulse {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8);
        }
        20% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.05);
        }
        100% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }
`;
document.head.appendChild(style);

/*
   HOW DATA IS RETRIEVED FOR RECEIPT:

   1. URL PARAMETER:
      - Receipt accessed via: receipt.php?sale_id=123
      - PHP validates sale_id and fetches from database

   2. DATABASE QUERIES:
      - Main sale info: sales + showtimes + movies tables
      - Seat details: seats table filtered by showtime_id
      - Add-ons: sales_extras + extras tables joined

   3. QR CODE GENERATION:
      - Uses QRCode.js library (CDN)
      - Generates QR linking to receipt URL
      - Fallback display if generation fails

   4. DATA FLOW:
      Database → PHP → HTML → JavaScript → QR Code

   HOW TO TEST LOCALLY:

   1. COMPLETE A TRANSACTION:
      - Go through full booking process
      - Complete payment to get sale_id
      - Automatic redirect to receipt page

   2. DIRECT ACCESS:
      - URL: http://localhost/cinema-kiosk/kiosk/receipt.php?sale_id=1
      - Replace '1' with actual sale_id from database

   3. DATABASE CHECK:
      - Verify sale exists in 'sales' table
      - Check related seats and extras records
      - Ensure all foreign keys are valid

   4. QR CODE TESTING:
      - QR should link to same receipt URL
      - Scan with phone to test accessibility
      - Verify URL works from external access
*/