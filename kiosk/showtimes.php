<?php
/**
 * TIME SELECTION PAGE
 * Displays available showtimes for selected movie
 */

// Include database configuration
require_once '../db/config.php';

// Get movie ID from URL parameter
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

if ($movie_id <= 0) {
    header('Location: movies.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get movie details
    $sql = "SELECT * FROM movies WHERE id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch();
    
    if (!$movie) {
        header('Location: movies.php');
        exit;
    }
    
    // Get showtimes for this movie (today and future dates)
    $sql = "SELECT s.*, 
            (s.total_seats - s.available_seats) as booked_seats
            FROM showtimes s 
            WHERE s.movie_id = ? 
            AND s.show_date >= CURDATE()
            ORDER BY s.show_date, s.show_time";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movie_id]);
    $showtimes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Unable to load showtimes. Please try again.";
    $showtimes = [];
}

// Group showtimes by date
function groupShowtimesByDate($showtimes) {
    $grouped = [];
    foreach ($showtimes as $showtime) {
        $date = $showtime['show_date'];
        $grouped[$date][] = $showtime;
    }
    return $grouped;
}

$grouped_showtimes = groupShowtimesByDate($showtimes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Showtime - <?php echo htmlspecialchars($movie['title']); ?></title>
    <link rel="stylesheet" href="assets/css/time_selection.css">
    <script src="assets/js/idle-timeout.js"></script>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-content">
                <h1>⚠️ Error Loading Showtimes</h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goBack()" class="back-btn">← Go Back</button>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Content -->
        <div class="showtime-container">
            
            <!-- Header Section -->
            <header class="header">
                <div class="container">
                    <div class="movie-info">
                        <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    </div>
                </div>
            </header>

            <!-- Showtimes Section -->
            <main class="showtimes-section">
                <div class="container">
                    <?php if (empty($grouped_showtimes)): ?>
                        <!-- No Showtimes Available -->
                        <div class="no-showtimes">
                            <h2>🎬 No Showtimes Available</h2>
                            <p>There are currently no scheduled showtimes for this movie.</p>
                            <p>Please check back later or select a different movie.</p>
                        </div>
                    <?php else: ?>
                        <!-- Two-Column Layout -->
                        <div class="showtime-layout">
                            <!-- Left Column: Movie Poster -->
                            <div class="poster-column">
                                <?php if ($movie['poster_image']): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($movie['poster_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                         class="movie-poster">
                                <?php else: ?>
                                    <div class="placeholder-poster">
                                        <span>🎬</span>
                                        <p>No Poster</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="movie-info-left">
                                    <h2 class="movie-title-left"><?php echo htmlspecialchars($movie['title']); ?></h2>
                                    <div class="movie-meta-left">
                                        <?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?>, <?php echo $movie['duration']; ?> minutes, <?php echo htmlspecialchars($movie['language'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="movie-rating-left"><?php echo htmlspecialchars($movie['rating']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Time Selection -->
                            <div class="times-column">
                                <h2 class="select-title">Select a Time</h2>
                                <p class="select-subtitle">Choose your preferred showtime</p>
                                
                                <div class="time-slots">
                                    <?php foreach ($grouped_showtimes as $date => $date_showtimes): ?>
                                        <div class="date-group">
                                            <h3 class="date-label"><?php echo date('l, M j', strtotime($date)); ?></h3>
                                            <?php foreach ($date_showtimes as $showtime): ?>
                                                <button class="time-slot-btn" 
                                                        data-showtime-id="<?php echo $showtime['id']; ?>"
                                                        onclick="selectShowtime(<?php echo $showtime['id']; ?>)"
                                                        <?php echo $showtime['available_seats'] == 0 ? 'disabled' : ''; ?>>
                                                    <span class="time"><?php echo date('g:i A', strtotime($showtime['show_time'])); ?></span>
                                                    <span class="price">₱<?php echo number_format($showtime['price'], 2); ?></span>
                                                    <?php if ($showtime['available_seats'] == 0): ?>
                                                        <span class="sold-out-label">SOLD OUT</span>
                                                    <?php elseif ($showtime['available_seats'] <= 5): ?>
                                                        <span class="few-seats"><?php echo $showtime['available_seats']; ?> left</span>
                                                    <?php endif; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Navigation Section -->
            <footer class="navigation-section">
                <div class="container">
                    <div class="nav-buttons">
                        <button onclick="goToMovieDetails()" class="nav-btn back-btn">
                            ← Back
                        </button>
                        <button onclick="goBack()" class="nav-btn cancel-btn">
                            Cancel
                        </button>
                        <button onclick="proceedToSeats()" class="nav-btn next-btn" id="nextBtn" disabled>
                            Next
                        </button>
                    </div>
                </div>
            </footer>

        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <script>
        window.movieData = {
            id: <?php echo $movie_id; ?>,
            title: <?php echo json_encode($movie['title']); ?>
        };
    </script>
    
    <!-- JavaScript -->
    <script src="assets/js/time_selection.js"></script>
</body>
</html>