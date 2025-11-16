<?php
/**
 * ADD-ONS PAGE
 * Displays snacks and drinks with order summary
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
    
    // Get available extras (snacks and drinks)
    $sql = "SELECT * FROM extras WHERE status = 'active' ORDER BY category, name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $extras = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Unable to load add-ons.";
    $extras = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Extras - <?php echo htmlspecialchars($showtime['title']); ?></title>
    <link rel="stylesheet" href="assets/css/extras.css">
    <script src="assets/js/idle-timeout.js"></script> 
</head>
<body>
    <!-- Top Title Section -->
    <div class="top-title">
        <h1>SELECT ADD-ON F&B</h1>
    </div>

    <!-- Main Content Layout -->
    <div class="main-layout">
        <!-- Left Side: Extras Items -->
        <div class="extras-list">
            <div class="category-tabs">
            <button class="tab-btn active" onclick="filterCategory('all')">All</button>
            <button class="tab-btn" onclick="filterCategory('drink')">Beverages</button>
            <button class="tab-btn" onclick="filterCategory('snack')">Snacks</button>
        </div>
            <?php foreach ($extras as $extra): ?>
                <div class="item-card" data-category="<?php echo $extra['category']; ?>" data-id="<?php echo $extra['id']; ?>">
                    <div class="item-image">
                        <?php if ($extra['image']): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($extra['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($extra['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder">🍿</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h3 class="item-title"><?php echo htmlspecialchars($extra['name']); ?></h3>
                        <p class="item-subtitle"><?php echo htmlspecialchars($extra['description']); ?></p>
                    </div>
                    <div class="item-controls">
                        <div class="item-price">PHP <?php echo number_format($extra['price'], 2); ?></div>
                        <div class="quantity-selector">
                            <button class="qty-btn minus" onclick="changeQuantity(<?php echo $extra['id']; ?>, -1)">-</button>
                            <span class="qty-display" id="qty-<?php echo $extra['id']; ?>">0</span>
                            <button class="qty-btn plus" onclick="changeQuantity(<?php echo $extra['id']; ?>, 1)">+</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Right Side: Order Summary -->
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

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <button onclick="goBack()" class="nav-btn back-btn">BACK</button>
        <button onclick="goBack()" class="nav-btn cancel-btn">CANCEL</button>
        <button onclick="proceedToCheckout()" class="nav-btn next-btn" id="nextBtn">NEXT</button>
    </div>

    <!-- Hidden data for JavaScript -->
    <script>
        window.showtimeData = {
            id: <?php echo $showtime_id; ?>,
            price: <?php echo $showtime['price']; ?>,
            title: <?php echo json_encode($showtime['title']); ?>
        };
        
        window.extrasData = <?php echo json_encode($extras); ?>;
    </script>
    
    <script src="assets/js/extras.js"></script>
</body>
</html>