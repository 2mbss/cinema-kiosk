<?php
/**
 * SEAT SELECTION PAGE
 * Interactive seat selection with real-time updates
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
    
    // Get showtime details with movie information
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
    
    // Get booked seats for this showtime
    $sql = "SELECT seat_number FROM seats WHERE showtime_id = ? AND is_booked = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$showtime_id]);
    $booked_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error_message = "Unable to load seat information.";
    $booked_seats = [];
}

// Generate seat grid (8 rows A-H, 12 columns 1-12)
function generateSeatGrid($booked_seats) {
    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $seats = [];
    
    foreach ($rows as $row) {
        for ($col = 1; $col <= 12; $col++) {
            $seat_number = $row . $col;
            $seats[$row][] = [
                'number' => $seat_number,
                'status' => in_array($seat_number, $booked_seats) ? 'booked' : 'available'
            ];
        }
    }
    
    return $seats;
}

$seat_grid = generateSeatGrid($booked_seats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?php echo htmlspecialchars($showtime['title']); ?></title>
    <link rel="stylesheet" href="assets/css/seat_selection.css">
    <script src="assets/js/idle-timeout.js"></script>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-content">
                <h1>⚠️ Error Loading Seats</h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goBack()" class="back-btn">← Go Back</button>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Content -->
        <div class="seat-selection-container">
            
            <!-- Top Title Section -->
            <div class="top-title">
                <h1>SELECT SEATS & TICKETS</h1>
            </div>

            <!-- Main Seat Selection Container -->
            <div class="main-container">
                <!-- Header Row Inside Container -->
                <div class="container-header">
                    <div class="movie-info">
                        <span><?php echo htmlspecialchars($showtime['title']); ?> (PG-13)</span>
                    </div>
                    <div class="showtime-info">
                        <span><?php echo date('d-M', strtotime($showtime['show_date'])); ?> <?php echo date('g.iA', strtotime($showtime['show_time'])); ?></span>
                    </div>
                </div>

                <!-- Screen Indicator -->
                <div class="screen-indicator">
                    <div class="screen-bar">Screen</div>
                </div>

                <!-- Seat Grid -->
                <div class="seat-grid" id="seatGrid">
                    <?php foreach ($seat_grid as $row_letter => $row_seats): ?>
                        <div class="seat-row">
                            <div class="row-label"><?php echo $row_letter; ?></div>
                            <div class="seats">
                                <?php foreach ($row_seats as $seat): ?>
                                    <button 
                                        class="seat <?php echo $seat['status']; ?>"
                                        data-seat="<?php echo $seat['number']; ?>"
                                        <?php echo $seat['status'] === 'booked' ? 'disabled' : ''; ?>>
                                        <?php if ($seat['status'] === 'booked'): ?>
                                            ×
                                        <?php else: ?>
                                            <?php echo $seat['number']; ?>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="row-label"><?php echo $row_letter; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Seat Legend -->
                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-seat available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat booked"></div>
                        <span>Sold</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected"></div>
                        <span>Selected</span>
                    </div>
                </div>
            </div>

            <!-- Order & Summary Section -->
            <div class="summary-panels">
                <!-- Left: Seat Count + Price -->
                <div class="price-panel">
                    <h3>You Have Selected <span id="seatCount">0</span> Seat(s)</h3>
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>CLASSIC</span>
                            <span id="classicCount">0</span>
                            <span>₱<?php echo number_format($showtime['price'], 2); ?></span>
                        </div>
                        <div class="price-total">
                            <span>TOTAL</span>
                            <span id="totalSeats">0</span>
                            <span id="totalPrice">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Your Orders -->
                <div class="order-panel">
                    <h3>Your Orders</h3>
                    <div class="order-details">
                        <div class="movie-line"><?php echo htmlspecialchars($showtime['title']); ?> (PG-13)</div>
                        <div class="date-line"><?php echo strtoupper(date('D, j - M Y', strtotime($showtime['show_date']))); ?></div>
                        <div class="time-line"><?php echo date('h:iA', strtotime($showtime['show_time'])); ?> - <?php echo date('h:iA', strtotime($showtime['show_time'] . ' +2 hours')); ?></div>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <button onclick="goBack()" class="nav-btn back-btn">BACK</button>
                <button onclick="goBack()" class="nav-btn cancel-btn">CANCEL</button>
                <button onclick="proceedToExtras()" class="nav-btn next-btn" id="nextBtn" disabled>NEXT</button>
            </div>

        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <script>
        // Pass PHP data to JavaScript
        window.showtimeData = {
            id: <?php echo $showtime_id; ?>,
            price: <?php echo $showtime['price']; ?>,
            title: <?php echo json_encode($showtime['title']); ?>,
            date: <?php echo json_encode(date('M j, Y', strtotime($showtime['show_date']))); ?>,
            time: <?php echo json_encode(date('g:i A', strtotime($showtime['show_time']))); ?>
        };
    </script>
    
    <!-- JavaScript -->
    <script src="assets/js/seat_selection.js"></script>
</body>
</html>