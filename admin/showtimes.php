<?php
/**
 * Showtime & Seat Management System
 * Manage movie showtimes and seat availability
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
            // Add new showtime
            $stmt = $pdo->prepare("
                INSERT INTO showtimes (movie_id, show_date, show_time, total_seats, available_seats, price) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $totalSeats = $_POST['total_seats'];
            $stmt->execute([
                $_POST['movie_id'],
                $_POST['show_date'],
                $_POST['show_time'],
                $totalSeats,
                $totalSeats, // Initially all seats are available
                $_POST['price']
            ]);
            
            $showtimeId = $pdo->lastInsertId();
            
            // Generate seats for this showtime
            $seatStmt = $pdo->prepare("INSERT INTO seats (showtime_id, seat_number) VALUES (?, ?)");
            for ($i = 1; $i <= $totalSeats; $i++) {
                $seatNumber = chr(65 + floor(($i - 1) / 10)) . (($i - 1) % 10 + 1); // A1, A2, ..., B1, B2, etc.
                $seatStmt->execute([$showtimeId, $seatNumber]);
            }
            
            $message = 'Showtime added successfully with ' . $totalSeats . ' seats!';
            
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE showtimes 
                SET movie_id = ?, show_date = ?, show_time = ?, price = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['movie_id'],
                $_POST['show_date'],
                $_POST['show_time'],
                $_POST['price'],
                $_POST['showtime_id']
            ]);
            $message = 'Showtime updated successfully!';
            
        } elseif ($action === 'delete') {
            // Check if showtime has sales records
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE showtime_id = ?");
            $checkStmt->execute([$_POST['showtime_id']]);
            $salesCount = $checkStmt->fetchColumn();
            
            if ($salesCount > 0) {
                $error = 'Cannot delete showtime: It has ' . $salesCount . ' sales record(s). You can only delete showtimes with no ticket sales.';
            } else {
                // Safe to delete - no sales records
                $pdo->beginTransaction();
                
                // Delete seats first (child records)
                $seatStmt = $pdo->prepare("DELETE FROM seats WHERE showtime_id = ?");
                $seatStmt->execute([$_POST['showtime_id']]);
                
                // Then delete showtime
                $showtimeStmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                $showtimeStmt->execute([$_POST['showtime_id']]);
                
                $pdo->commit();
                $message = 'Showtime deleted successfully!';
            }
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get showtime for editing
$editShowtime = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM showtimes WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editShowtime = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error loading showtime data';
    }   
}

// Get all movies for dropdown
try {
    $stmt = $pdo->query("SELECT id, title FROM movies WHERE status = 'active' ORDER BY title");
    $movies = $stmt->fetchAll();
} catch (PDOException $e) {
    $movies = [];
}

// Get all showtimes with movie details
try {
    $stmt = $pdo->query("
        SELECT s.*, m.title as movie_title 
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        ORDER BY s.show_date DESC, s.show_time DESC
    ");
    $showtimes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading showtimes';
    $showtimes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showtimes - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>🕐 Showtime Management</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Add/Edit Showtime Form -->
            <div class="form-container">
                <h3><?php echo $editShowtime ? 'Edit Showtime' : 'Add New Showtime'; ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editShowtime ? 'edit' : 'add'; ?>">
                    <?php if ($editShowtime): ?>
                        <input type="hidden" name="showtime_id" value="<?php echo $editShowtime['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="movie_id">Movie:</label>
                            <select id="movie_id" name="movie_id" required>
                                <option value="">Select Movie</option>
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>" 
                                            <?php echo ($editShowtime['movie_id'] ?? '') == $movie['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Ticket Price (₱):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo htmlspecialchars($editShowtime['price'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr <?php echo $editShowtime ? '' : '1fr'; ?>; gap: 20px;">
                        <div class="form-group">
                            <label for="show_date">Show Date:</label>
                            <input type="date" id="show_date" name="show_date" required 
                                   value="<?php echo htmlspecialchars($editShowtime['show_date'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="show_time">Show Time:</label>
                            <input type="time" id="show_time" name="show_time" required 
                                   value="<?php echo htmlspecialchars($editShowtime['show_time'] ?? ''); ?>">
                        </div>
                        
                        <?php if (!$editShowtime): ?>
                            <div class="form-group">
                                <label for="total_seats">Total Seats:</label>
                                <input type="number" id="total_seats" name="total_seats" min="10" max="100" value="50" required>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editShowtime ? 'Update Showtime' : 'Add Showtime'; ?>
                    </button>
                    
                    <?php if ($editShowtime): ?>
                        <a href="showtimes.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Showtimes List -->
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #34495e; color: white;">📋 All Showtimes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Seats</th>
                            <th>Availability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($showtimes as $showtime): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($showtime['movie_title']); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($showtime['show_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($showtime['show_time'])); ?></td>
                                <td>₱<?php echo number_format($showtime['price'], 2); ?></td>
                                <td><?php echo $showtime['total_seats']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 100px; height: 8px; background: #ddd; border-radius: 4px; overflow: hidden;">
                                            <?php 
                                            $percentage = ($showtime['available_seats'] / $showtime['total_seats']) * 100;
                                            $color = $percentage > 50 ? '#27ae60' : ($percentage > 20 ? '#f39c12' : '#e74c3c');
                                            ?>
                                            <div style="width: <?php echo $percentage; ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
                                        </div>
                                        <span style="font-size: 12px; color: #666;">
                                            <?php echo $showtime['available_seats']; ?>/<?php echo $showtime['total_seats']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $showtime['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                    
                                    <a href="seats.php?showtime=<?php echo $showtime['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Seats</a>
                                    
                                    <?php
                                    // Check if showtime has sales
                                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE showtime_id = ?");
                                    $checkStmt->execute([$showtime['id']]);
                                    $hasSales = $checkStmt->fetchColumn() > 0;
                                    ?>
                                    
                                    <?php if ($hasSales): ?>
                                        <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; opacity: 0.5; cursor: not-allowed;" 
                                                disabled title="Cannot delete: Has ticket sales">
                                            Delete
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this showtime? This will also delete all associated seats.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="showtime_id" value="<?php echo $showtime['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($showtimes)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                    No showtimes found. Add your first showtime above!
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