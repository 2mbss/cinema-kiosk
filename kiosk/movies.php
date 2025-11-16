<?php
/**
 * MOVIE SELECTION PAGE
 * Displays all active movies from the database
 */

// Include database configuration
require_once '../db/config.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // SQL query to fetch active movies with their lowest price
    $sql = "SELECT m.*, MIN(s.price) as min_price 
            FROM movies m 
            LEFT JOIN showtimes s ON m.id = s.movie_id 
            WHERE m.status = 'active' 
            GROUP BY m.id 
            ORDER BY m.title";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $movies = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Handle database errors gracefully
    $error_message = "Unable to load movies. Please try again later.";
    $movies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select a Movie - Cinema Kiosk</title>
    <link rel="stylesheet" href="assets/css/movie_selection.css">
    <script src="assets/js/idle-timeout.js"></script> 
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <h1 class="page-title">SELECT A MOVIE</h1>
        </div>
    </header>

    <!-- Movies Grid Section -->
    <main class="main-content">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <!-- Error Message Display -->
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <button onclick="location.reload()" class="retry-btn">Try Again</button>
                </div>
            <?php elseif (empty($movies)): ?>
                <!-- No Movies Available -->
                <div class="no-movies">
                    <h2>No Movies Available</h2>
                    <p>Please check back later for new releases!</p>
                </div>
            <?php else: ?>
                <!-- Movies Grid -->
                <div class="movies-grid" id="moviesGrid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card" 
                             data-movie-id="<?php echo $movie['id']; ?>"
                             data-title="<?php echo htmlspecialchars($movie['title']); ?>"
                             data-rating="<?php echo htmlspecialchars($movie['rating']); ?>">
                            
                            <!-- Movie Poster -->
                            <div class="movie-poster">
                                <?php if ($movie['poster_image']): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($movie['poster_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                         onerror="this.src='assets/images/placeholder-movie.jpg'">
                                <?php else: ?>
                                    <div class="placeholder-poster">
                                        <span>🎬</span>
                                        <p>No Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Movie Info -->
                            <div class="movie-info">
                                <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                
                                <div class="movie-details">
                                    <span class="rating"><?php echo htmlspecialchars($movie['rating']); ?></span>
                                    <span class="genre"><?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></span>
                                    <span class="language"><?php echo htmlspecialchars($movie['language'] ?? 'N/A'); ?></span>
                                    <span class="duration"><?php echo $movie['duration']; ?> min</span>
                                </div>
                                
                                <div class="movie-price">
                                    <?php if ($movie['min_price']): ?>
                                        <span class="price">₱<?php echo number_format($movie['min_price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="price">Price TBA</span>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="select-btn" onclick="selectMovie(<?php echo $movie['id']; ?>)">
                                    Select Movie
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- JavaScript -->
    <script src="assets/js/movie_selection.js"></script>
</body>
</html>