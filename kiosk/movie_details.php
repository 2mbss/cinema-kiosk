<?php
/**
 * MOVIE DETAILS PAGE
 * Shows detailed information about a selected movie
 */

// Include database configuration
require_once '../db/config.php';

// Get movie ID from URL parameter
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movie_id <= 0) {
    // Invalid movie ID - redirect back to movie selection
    header('Location: movies.php');
    exit;
}

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Fetch movie details with price information
    $sql = "SELECT m.*, MIN(s.price) as min_price, MAX(s.price) as max_price
            FROM movies m 
            LEFT JOIN showtimes s ON m.id = s.movie_id 
            WHERE m.id = ? AND m.status = 'active'
            GROUP BY m.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch();
    
    if (!$movie) {
        // Movie not found - redirect back
        header('Location: movies.php');
        exit;
    }
    
} catch (PDOException $e) {
    $error_message = "Unable to load movie details. Please try again.";
}

/**
 * Convert YouTube URL to embed format
 * @param string $url YouTube URL
 * @return string Embed URL
 */
function getYouTubeEmbedUrl($url) {
    if (empty($url)) return '';
    
    // Extract video ID from various YouTube URL formats
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches);
    
    if (isset($matches[1])) {
        return "https://www.youtube.com/embed/" . $matches[1] . "?autoplay=0&rel=0&showinfo=0";
    }
    
    return '';
}

$embed_url = getYouTubeEmbedUrl($movie['trailer_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Cinema Kiosk</title>
    <link rel="stylesheet" href="assets/css/movie_details.css">
    <script src="assets/js/idle-timeout.js"></script>
</head>
<body>
    <?php if (isset($error_message)): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-content">
                <h1>⚠️ Error Loading Movie</h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goBack()" class="back-btn">← Back to Movies</button>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Content -->
        <div class="movie-details-container">
            
            <!-- Header Section -->
            <header class="movie-header">
                <div class="container">
                    <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                </div>
            </header>

            <!-- Trailer Section -->
            <section class="trailer-section">
                <div class="container">
                    <div class="trailer-container">
                        <?php if ($embed_url): ?>
                            <iframe 
                                class="trailer-video"
                                src="<?php echo htmlspecialchars($embed_url); ?>"
                                frameborder="0"
                                allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                            </iframe>
                        <?php else: ?>
                            <div class="no-trailer">
                                <div class="no-trailer-content">
                                    <span class="no-trailer-icon">🎥</span>
                                    <h3>Trailer Not Available</h3>
                                    <p>Check back later for the official trailer</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="description-column">
                            <h2 class="section-title">About This Movie</h2>
                            <div class="description-content">
                                <?php if ($movie['description']): ?>
                                    <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                                <?php else: ?>
                                    <p class="no-description">Description coming soon...</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Movie Stats -->
                            <div class="movie-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Rating:</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($movie['rating']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Genre:</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Language:</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($movie['language'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Duration:</span>
                                    <span class="stat-value"><?php echo $movie['duration']; ?> min</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Subtitles:</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($movie['subtitles'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Ticket Price:</span>
                                    <span class="stat-value price-highlight">
                                        <?php if ($movie['min_price']): ?>
                                            <?php if ($movie['min_price'] == $movie['max_price']): ?>
                                                ₱<?php echo number_format($movie['min_price'], 2); ?>
                                            <?php else: ?>
                                                ₱<?php echo number_format($movie['min_price'], 2); ?> - ₱<?php echo number_format($movie['max_price'], 2); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Price TBA
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                </div>
            </section>

            <!-- Navigation Section -->
            <section class="navigation-section">
                <div class="container">
                    <div class="nav-buttons">
                        <button onclick="goBack()" class="nav-btn back-btn">
                            ← Back to Movies
                        </button>
                        <button onclick="selectShowtime(<?php echo $movie['id']; ?>)" class="nav-btn next-btn">
                            Select Showtime →
                        </button>
                    </div>
                </div>
            </section>

        </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="assets/js/movie_details.js"></script>
</body>
</html>