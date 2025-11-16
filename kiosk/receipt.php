<?php
/**
 * RECEIPT PAGE
 * Displays digital receipt with QR code
 */

// Include database configuration
require_once '../db/config.php';

// Get sale ID from URL parameter
$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if ($sale_id <= 0) {
    header('Location: movies.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get sale details with movie and showtime info
    $sql = "SELECT s.*, st.show_date, st.show_time, st.price as ticket_price, 
                   m.title, m.poster_image, m.rating, m.duration
            FROM sales s
            JOIN showtimes st ON s.showtime_id = st.id
            JOIN movies m ON st.movie_id = m.id
            WHERE s.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        header('Location: movies.php');
        exit;
    }
    
    // Get booked seats for this sale
    $sql = "SELECT seat_number FROM seats 
            WHERE showtime_id = ? AND is_booked = 1 
            ORDER BY seat_number";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sale['showtime_id']]);
    $seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get purchased extras
    $sql = "SELECT se.quantity, e.name, e.price, e.category
            FROM sales_extras se
            JOIN extras e ON se.extra_id = e.id
            WHERE se.sale_id = ?
            ORDER BY e.category, e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sale_id]);
    $extras = $stmt->fetchAll();
    
    // Calculate totals
    $tickets_total = $sale['seats_booked'] * $sale['ticket_price'];
    $extras_total = 0;
    foreach ($extras as $extra) {
        $extras_total += $extra['quantity'] * $extra['price'];
    }
    
    // Generate receipt URL for QR code
    $receipt_url = "http://" . $_SERVER['HTTP_HOST'] . "/cinema-kiosk/kiosk/receipt.php?sale_id=" . $sale_id;
    
} catch (PDOException $e) {
    $error_message = "Unable to load receipt information.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $sale_id; ?> - Cinema Kiosk</title>
    <link rel="stylesheet" href="assets/css/receipt.css">
    <script src="assets/js/idle-timeout.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-content">
                <h1>Receipt Not Found</h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goHome()" class="home-btn">← Back to Home</button>
            </div>
        </div>
    <?php else: ?>
        <!-- Receipt Content -->
        <div class="receipt-container">
            
            <!-- Receipt Header -->
            <header class="receipt-header">
                <h1 class="cinema-name">CINEMA KIOSK</h1>
                <p class="cinema-tagline">OFFICIAL RECEIPT</p>
                <p class="cinema-address">STI Dasmarinas, Cavite</p>
                <p class="cinema-contact">BSIT 3.1A JepJad Film</p>
            </header>

            <!-- Receipt Body -->
            <main class="receipt-body">
                
                <!-- Transaction Info -->
                <section class="transaction-info">
                    <div class="receipt-number">
                        <strong>Receipt #<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="transaction-date">
                        <?php echo date('F j, Y g:i A', strtotime($sale['sale_date'])); ?>
                    </div>
                </section>

                <!-- Movie Details -->
                <section class="movie-section">
                    <div class="section-header">
                        <h2>MOVIE DETAILS</h2>
                    </div>
                    <div class="movie-info">
                        <div class="movie-poster">
                            <?php if ($sale['poster_image']): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($sale['poster_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($sale['title']); ?>">
                            <?php else: ?>
                                <div class="placeholder-poster">NO IMAGE</div>
                            <?php endif; ?>
                        </div>
                        <div class="movie-details">
                            <h3 class="movie-title"><?php echo htmlspecialchars($sale['title']); ?></h3>
                            <div class="movie-meta">
                                <span class="rating"><?php echo htmlspecialchars($sale['rating']); ?></span>
                                <span class="duration"><?php echo $sale['duration']; ?> min</span>
                            </div>
                            <div class="showtime">
                                <strong>Showtime:</strong><br>
                                <?php echo date('l, F j, Y', strtotime($sale['show_date'])); ?><br>
                                <?php echo date('g:i A', strtotime($sale['show_time'])); ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tickets Section -->
                <section class="tickets-section">
                    <div class="section-header">
                        <h2>TICKETS</h2>
                    </div>
                    <div class="tickets-details">
                        <div class="seats-info">
                            <strong>Seats:</strong> <?php echo implode(', ', array_slice($seats, 0, $sale['seats_booked'])); ?>
                        </div>
                        <div class="tickets-breakdown">
                            <div class="line-item">
                                <span><?php echo $sale['seats_booked']; ?> ticket(s) × PHP <?php echo number_format($sale['ticket_price'], 2); ?></span>
                                <span>PHP <?php echo number_format($tickets_total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Add-ons Section -->
                <?php if (!empty($extras)): ?>
                <section class="addons-section">
                    <div class="section-header">
                        <h2>CONCESSIONS</h2>
                    </div>
                    <div class="addons-details">
                        <?php foreach ($extras as $extra): ?>
                            <div class="line-item">
                                <span><?php echo htmlspecialchars($extra['name']); ?> × <?php echo $extra['quantity']; ?></span>
                                <span>PHP <?php echo number_format($extra['quantity'] * $extra['price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Total Section -->
                <section class="total-section">
                    <div class="subtotals">
                        <div class="line-item">
                            <span>Tickets Subtotal:</span>
                            <span>PHP <?php echo number_format($tickets_total, 2); ?></span>
                        </div>
                        <?php if ($extras_total > 0): ?>
                        <div class="line-item">
                            <span>Concessions Subtotal:</span>
                            <span>PHP <?php echo number_format($extras_total, 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="grand-total">
                        <div class="total-line">
                            <span>TOTAL:</span>
                            <span>PHP <?php echo number_format($sale['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </section>

                <!-- QR Code Section -->
                <section class="qr-section">
                    <div class="section-header">
                        <h2>DIGITAL RECEIPT</h2>
                    </div>
                    <div class="qr-container">
                        <canvas id="qrcode"></canvas>
                        <p class="qr-text">Scan to view this receipt online</p>
                    </div>
                </section>

                <!-- Thank You Message -->
                <section class="thank-you-section">
                    <div class="thank-you-message">
                        <h2>THANK YOU FOR YOUR PURCHASE</h2>
                        <p>Enjoy your movie experience!</p>
                        <p class="reminder">Please arrive 15 minutes before showtime</p>
                        <p class="policy">No refunds or exchanges. Valid for date and time shown only.</p>
                    </div>
                </section>

            </main>

            <!-- Receipt Footer -->
            <footer class="receipt-footer">
                <div class="footer-info">
                    <p>Cinema Kiosk • STI Dasmarinas, Cavite • BSIT 3.1A JepJad Film</p>
                    <p>Visit us at www.jepjadfilm.ph</p>
                </div>
                <div class="action-buttons">
                    <button onclick="printReceipt()" class="btn print-btn">PRINT RECEIPT</button>
                    <button onclick="goHome()" class="btn home-btn">NEW ORDER</button>
                </div>
            </footer>

        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <script>
        window.receiptData = {
            saleId: <?php echo $sale_id; ?>,
            receiptUrl: <?php echo json_encode($receipt_url); ?>,
            movieTitle: <?php echo json_encode($sale['title']); ?>,
            showDate: <?php echo json_encode(date('M j, Y', strtotime($sale['show_date']))); ?>,
            showTime: <?php echo json_encode(date('g:i A', strtotime($sale['show_time']))); ?>,
            seats: <?php echo json_encode(array_slice($seats, 0, $sale['seats_booked'])); ?>,
            total: <?php echo $sale['total_amount']; ?>
        };
    </script>
    
    <!-- JavaScript -->
    <script src="assets/js/receipt.js"></script>
</body>
</html>