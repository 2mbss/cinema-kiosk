<?php
/**
 * Movie Management System
 * Add, edit, delete, and view movies
 */

require_once 'includes/auth.php';
requireAuth();

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $subtitles = !empty($_POST['subtitles']) ? $_POST['subtitles'] : 'N/A';
            $stmt = $pdo->prepare("
                INSERT INTO movies (title, description, trailer_url, poster_image, duration, rating, genre, language, subtitles, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['trailer_url'],
                $_POST['poster_image'],
                $_POST['duration'],
                $_POST['rating'],
                $_POST['genre'],
                $_POST['language'],
                $subtitles,
                $_POST['status']
            ]);
            $message = 'Movie added successfully!';
            
        } elseif ($action === 'edit') {
            $subtitles = !empty($_POST['subtitles']) ? $_POST['subtitles'] : 'N/A';
            $stmt = $pdo->prepare("
                UPDATE movies 
                SET title = ?, description = ?, trailer_url = ?, poster_image = ?, 
                    duration = ?, rating = ?, genre = ?, language = ?, subtitles = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['trailer_url'],
                $_POST['poster_image'],
                $_POST['duration'],
                $_POST['rating'],
                $_POST['genre'],
                $_POST['language'],
                $subtitles,
                $_POST['status'],
                $_POST['movie_id']
            ]);
            $message = 'Movie updated successfully!';
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$_POST['movie_id']]);
            $message = 'Movie deleted successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get movie for editing
$editMovie = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editMovie = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error loading movie data';
    }
}

// Get all movies
try {
    $stmt = $pdo->query("SELECT * FROM movies ORDER BY created_at DESC");
    $movies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading movies';
    $movies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>🎥 Movie Management</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Add/Edit Movie Form -->
            <div class="form-container">
                <h3><?php echo $editMovie ? 'Edit Movie' : 'Add New Movie'; ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editMovie ? 'edit' : 'add'; ?>">
                    <?php if ($editMovie): ?>
                        <input type="hidden" name="movie_id" value="<?php echo $editMovie['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="title">Movie Title:</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo htmlspecialchars($editMovie['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (minutes):</label>
                            <input type="number" id="duration" name="duration" required 
                                   value="<?php echo htmlspecialchars($editMovie['duration'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($editMovie['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="genre">Genre:</label>
                            <input type="text" id="genre" name="genre" required 
                                   value="<?php echo htmlspecialchars($editMovie['genre'] ?? ''); ?>"
                                   placeholder="e.g., Action/Adventure">
                        </div>
                        
                        <div class="form-group">
                            <label for="language">Language:</label>
                            <input type="text" id="language" name="language" required 
                                   value="<?php echo htmlspecialchars($editMovie['language'] ?? ''); ?>"
                                   placeholder="e.g., English">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="trailer_url">Trailer URL:</label>
                            <input type="url" id="trailer_url" name="trailer_url" 
                                   value="<?php echo htmlspecialchars($editMovie['trailer_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="poster_image">Poster Image:</label>
                            <input type="text" id="poster_image" name="poster_image" 
                                   value="<?php echo htmlspecialchars($editMovie['poster_image'] ?? ''); ?>"
                                   placeholder="image.jpg">
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating:</label>
                            <select id="rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <?php 
                                $ratings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                                foreach ($ratings as $rating): ?>
                                    <option value="<?php echo $rating; ?>" 
                                            <?php echo ($editMovie['rating'] ?? '') === $rating ? 'selected' : ''; ?>>
                                        <?php echo $rating; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subtitles">Subtitles (optional):</label>
                        <input type="text" id="subtitles" name="subtitles" 
                               value="<?php echo htmlspecialchars($editMovie['subtitles'] ?? ''); ?>"
                               placeholder="e.g., English, Spanish (leave empty for N/A)">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo ($editMovie['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($editMovie['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editMovie ? 'Update Movie' : 'Add Movie'; ?>
                    </button>
                    
                    <?php if ($editMovie): ?>
                        <a href="movies.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Movies List -->
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #34495e; color: white;">📋 All Movies</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($movie['title']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($movie['language'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></td>
                                <td><?php echo $movie['duration']; ?> min</td>
                                <td><?php echo htmlspecialchars($movie['rating']); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; 
                                                 background: <?php echo $movie['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>; 
                                                 color: white;">
                                        <?php echo ucfirst($movie['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="?edit=<?php echo $movie['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this movie?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($movies)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                    No movies found. Add your first movie above!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>