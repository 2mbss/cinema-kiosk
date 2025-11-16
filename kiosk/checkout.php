<?php
/**
 * PAYMENT PAGE
 * Handles payment method selection and order processing
 */

// Include database configuration
require_once '../db/config.php';

// Get showtime ID from URL parameter
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;

if ($showtime_id <= 0) {
    header('Location: movies.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get showtime and movie details
    $sql = "SELECT s.*, m.title, m.poster_image 
            FROM showtimes s 
            JOIN movies m ON s.movie_id = m.id 
            WHERE s.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$showtime_id]);
    $showtime = $stmt->fetch();
    
    if (!$showtime) {
        header('Location: movies.php');
        exit;
    }
    
    // Get extras for order summary
    $sql = "SELECT * FROM extras WHERE status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $extras = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Unable to load checkout information.";
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $pdo->beginTransaction();
        
        // Get order data from POST
        $order_data = json_decode($_POST['order_data'], true);
        $payment_method = $_POST['payment_method'];
        
        // Insert sale record
        $sql = "INSERT INTO sales (showtime_id, seats_booked, total_amount, sale_date) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $showtime_id,
            count($order_data['seats']),
            $order_data['total']
        ]);
        
        $sale_id = $pdo->lastInsertId();
        
        // Book the seats
        foreach ($order_data['seats'] as $seat) {
            $sql = "INSERT INTO seats (showtime_id, seat_number, is_booked) VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE is_booked = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$showtime_id, $seat]);
        }
        
        // Update available seats count
        $sql = "UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([count($order_data['seats']), $showtime_id]);
        
        // Insert extras if any
        if (!empty($order_data['extras'])) {
            foreach ($order_data['extras'] as $extra_id => $quantity) {
                if ($quantity > 0) {
                    $sql = "INSERT INTO sales_extras (sale_id, extra_id, quantity) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$sale_id, $extra_id, $quantity]);
                }
            }
        }
        
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'sale_id' => $sale_id,
            'payment_method' => $payment_method
        ]);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Payment processing failed. Please try again.'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo htmlspecialchars($showtime['title']); ?></title>
    <link rel="stylesheet" href="assets/css/checkout.css">
    <script src="assets/js/idle-timeout.js"></script> 
</head>
<body>
    <?php if (isset($error_message)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-content">
                <h1>⚠️ Error Loading Checkout</h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goBack()" class="back-btn">← Go Back</button>
            </div>
        </div>
    <?php else: ?>
        <!-- Top Title Section -->
        <div class="top-title">
            <h1>SELECT PAYMENT METHOD</h1>
        </div>

        <!-- Main Content -->
        <div class="checkout-container">
            <main class="main-content">
                <div class="content-grid">
                    
                    <!-- Left Column: Payment Methods -->
                    <div class="payment-section">
                        <div class="payment-methods">
                            <div class="payment-method-item">
                                <div class="payment-label">Pay By Cash</div>
                                <div class="payment-option" data-method="cash" data-title="Pay By Cash">
                                    <input type="radio" name="payment_method" value="cash" id="cash" style="display: none;">
                                </div>
                            </div>

                            <div class="payment-method-item">
                                <div class="payment-label">Pay By E-Wallet</div>
                                <div class="payment-option" data-method="gcash" data-title="Pay By E-Wallet">
                                    <input type="radio" name="payment_method" value="gcash" id="gcash" style="display: none;">
                                </div>
                            </div>

                            <div class="payment-method-item">
                                <div class="payment-label">Pay By Card</div>
                                <div class="payment-option" data-method="bank" data-title="Pay By Card">
                                    <input type="radio" name="payment_method" value="bank" id="bank" style="display: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Order Summary (Exact copy from extras page) -->
                    <div class="order-summary">
                        <h2 class="order-title">Your Order</h2>
                        <div class="movie-poster-small">
                            <?php if ($showtime['poster_image']): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($showtime['poster_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($showtime['title']); ?>">
                            <?php else: ?>
                                <div class="placeholder-poster">🎬</div>
                            <?php endif; ?>
                        </div>
                        <div class="movie-title"><?php echo htmlspecialchars($showtime['title']); ?></div>
                        <div class="movie-meta">
                            <div class="date-line"><?php echo strtoupper(date('D, j - M Y', strtotime($showtime['show_date']))); ?></div>
                            <div class="time-line"><?php echo date('h:iA', strtotime($showtime['show_time'])); ?> - <?php echo date('h:iA', strtotime($showtime['show_time'] . ' +2 hours')); ?></div>
                        </div>
                        
                        <div class="seat-info" id="seatInfo">
                            <div class="seat-main">Ticket x0 - ₱0.00</div>
                            <div class="seat-sub">(Seats: None)</div>
                        </div>
                        
                        <div class="extras-summary" id="extrasSummary">
                            <!-- Dynamic extras will be added here -->
                        </div>
                        
                        <div class="total-row">
                            <span>Total</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                    </div>

                </div>
            </main>
        </div>
        
        <!-- Bottom Buttons -->
        <div class="bottom-buttons">
            <button onclick="goBack()" class="btn">BACK</button>
            <button onclick="cancelOrder()" class="btn">CANCEL</button>
            <button onclick="processPayment()" class="btn" id="payBtn" disabled>NEXT</button>
        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <script>
        window.extrasData = <?php echo json_encode($extras); ?>;
        window.showtimeData = {
            id: <?php echo $showtime_id; ?>,
            price: <?php echo $showtime['price']; ?>,
            title: <?php echo json_encode($showtime['title']); ?>,
            date: <?php echo json_encode(date('M j, Y', strtotime($showtime['show_date']))); ?>,
            time: <?php echo json_encode(date('g:i A', strtotime($showtime['show_time']))); ?>
        };
    </script>
    
    <!-- JavaScript -->
    <script src="assets/js/checkout.js"></script>
    <script>
        // Add payment method selection functionality
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    // Enable the NEXT button
                    document.getElementById('payBtn').disabled = false;
                }
            });
        });
        
        function cancelOrder() {
            if (confirm('Are you sure you want to cancel this order?')) {
                window.location.href = 'movies.php';
            }
        }
        
        function goBack() {
            window.history.back();
        }
        
        // Set default title on page load
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTitle = document.querySelector('.payment-title');
            if (paymentTitle) {
                paymentTitle.textContent = 'Select Payment Method';
            }
        });
    </script>
</body>
</html>