<?php
/**
 * Seat Management for Specific Showtime
 * View and manage individual seat bookings
 */

require_once 'includes/auth.php';
requireAuth();

$pdo = getDBConnection();
$message = '';
$error = '';

// Get showtime ID
$showtimeId = $_GET['showtime'] ?? null;
if (!$showtimeId) {
    header('Location: showtimes.php');
    exit;
}

// Handle seat toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_seat'])) {
    try {
        $seatId = $_POST['seat_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = $currentStatus ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE seats SET is_booked = ? WHERE id = ?");
        $stmt->execute([$newStatus, $seatId]);
        
        // Update available seats count
        $stmt = $pdo->prepare("
            UPDATE showtimes 
            SET available_seats = (
                SELECT COUNT(*) FROM seats 
                WHERE showtime_id = ? AND is_booked = 0
            ) 
            WHERE id = ?
        ");
        $stmt->execute([$showtimeId, $showtimeId]);
        
        $message = 'Seat status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating seat: ' . $e->getMessage();
    }
}

// Get showtime details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, m.title as movie_title 
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$showtimeId]);
    $showtime = $stmt->fetch();
    
    if (!$showtime) {
        header('Location: showtimes.php');
        exit;
    }
} catch (PDOException $e) {
    $error = 'Error loading showtime data';
}

// Get all seats for this showtime
try {
    $stmt = $pdo->prepare("SELECT * FROM seats WHERE showtime_id = ? ORDER BY seat_number");
    $stmt->execute([$showtimeId]);
    $seats = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading seats';
    $seats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Management - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .seat-map {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .screen {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .seats-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .seat {
            width: 40px;
            height: 40px;
            border: 2px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .seat.available {
            background: #27ae60;
            color: white;
            border-color: #229954;
        }
        
        .seat.booked {
            background: #e74c3c;
            color: white;
            border-color: #c0392b;
        }
        
        .seat:hover {
            transform: scale(1.1);
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legend-seat {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .seats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
            
            .seat {
                width: 35px;
                height: 35px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>🪑 Seat Management</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Showtime Info -->
            <div class="form-container">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; text-align: center;">
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 5px;">Movie</h4>
                        <p style="font-weight: bold;"><?php echo htmlspecialchars($showtime['movie_title']); ?></p>
                    </div>
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 5px;">Date & Time</h4>
                        <p style="font-weight: bold;">
                            <?php echo date('M j, Y', strtotime($showtime['show_date'])); ?><br>
                            <?php echo date('g:i A', strtotime($showtime['show_time'])); ?>
                        </p>
                    </div>
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 5px;">Price</h4>
                        <p style="font-weight: bold;">$<?php echo number_format($showtime['price'], 2); ?></p>
                    </div>
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 5px;">Availability</h4>
                        <p style="font-weight: bold;">
                            <?php echo $showtime['available_seats']; ?>/<?php echo $showtime['total_seats']; ?> Available
                        </p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="showtimes.php" class="btn btn-secondary">← Back to Showtimes</a>
                </div>
            </div>
            
            <!-- Seat Map -->
            <div class="seat-map">
                <div class="screen">🎬 SCREEN</div>
                
                <div class="seats-grid">
                    <?php foreach ($seats as $seat): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="toggle_seat" value="1">
                            <input type="hidden" name="seat_id" value="<?php echo $seat['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $seat['is_booked']; ?>">
                            
                            <button type="submit" 
                                    class="seat <?php echo $seat['is_booked'] ? 'booked' : 'available'; ?>"
                                    onclick="return confirm('Toggle seat <?php echo htmlspecialchars($seat['seat_number']); ?> status?');">
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-seat" style="background: #27ae60;"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat" style="background: #e74c3c;"></div>
                        <span>Booked</span>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                    Click on any seat to toggle its booking status
                </div>
            </div>
            
            <!-- Seat Statistics -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Seats</h3>
                    <div class="number"><?php echo $showtime['total_seats']; ?></div>
                    <div class="label">Capacity</div>
                </div>
                
                <div class="card">
                    <h3>Available</h3>
                    <div class="number" style="color: #27ae60;"><?php echo $showtime['available_seats']; ?></div>
                    <div class="label">Open Seats</div>
                </div>
                
                <div class="card">
                    <h3>Booked</h3>
                    <div class="number" style="color: #e74c3c;"><?php echo $showtime['total_seats'] - $showtime['available_seats']; ?></div>
                    <div class="label">Reserved</div>
                </div>
                
                <div class="card">
                    <h3>Occupancy</h3>
                    <div class="number" style="color: #3498db;">
                        <?php echo round((($showtime['total_seats'] - $showtime['available_seats']) / $showtime['total_seats']) * 100); ?>%
                    </div>
                    <div class="label">Filled</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>