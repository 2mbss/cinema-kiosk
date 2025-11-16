<?php
/**
 * Admin Dashboard
 * Main dashboard with analytics and overview
 */

require_once 'includes/auth.php';
requireAuth();

$pdo = getDBConnection();

// Get analytics data
try {
    // Total sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM sales");
    $totalSales = $stmt->fetch()['total_sales'];
    
    // Total movies
    $stmt = $pdo->query("SELECT COUNT(*) as total_movies FROM movies WHERE status = 'active'");
    $totalMovies = $stmt->fetch()['total_movies'];
    
    // Total extras
    $stmt = $pdo->query("SELECT COUNT(*) as total_extras FROM extras WHERE status = 'active'");
    $totalExtras = $stmt->fetch()['total_extras'];
    
    // Today's sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM sales WHERE DATE(sale_date) = CURDATE()");
    $todaySales = $stmt->fetch()['today_sales'];
    
    // Top movies (by sales)
    $stmt = $pdo->query("
        SELECT m.title, COALESCE(SUM(s.total_amount), 0) as revenue
        FROM movies m
        LEFT JOIN showtimes st ON m.id = st.movie_id
        LEFT JOIN sales s ON st.id = s.showtime_id
        WHERE m.status = 'active'
        GROUP BY m.id, m.title
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $topMovies = $stmt->fetchAll();
    
    // Top extras
    $stmt = $pdo->query("
        SELECT e.name, COALESCE(SUM(se.quantity), 0) as total_sold
        FROM extras e
        LEFT JOIN sales_extras se ON e.id = se.extra_id
        WHERE e.status = 'active'
        GROUP BY e.id, e.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topExtras = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/admin.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>📊 Dashboard</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Analytics Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Sales</h3>
                    <div class="number">₱<?php echo number_format($totalSales, 2); ?></div>
                    <div class="label">All Time</div>
                </div>
                
                <div class="card">
                    <h3>Today's Sales</h3>
                    <div class="number">₱<?php echo number_format($todaySales, 2); ?></div>
                    <div class="label">Today</div>
                </div>
                
                <div class="card">
                    <h3>Active Movies</h3>
                    <div class="number"><?php echo $totalMovies; ?></div>
                    <div class="label">Currently Showing</div>
                </div>
                
                <div class="card">
                    <h3>Available Extras</h3>
                    <div class="number"><?php echo $totalExtras; ?></div>
                    <div class="label">Snacks & Drinks</div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- Top Movies Chart -->
                <div class="chart-container">
                    <h3>🎬 Top Movies by Revenue</h3>
                    <canvas id="moviesChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Top Extras Chart -->
                <div class="chart-container">
                    <h3>🍿 Most Popular Extras</h3>
                    <canvas id="extrasChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="table-container">
                <h3 style="padding: 20px; margin: 0; background: #34495e; color: white;">📈 Recent Sales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Movie</th>
                            <th>Seats</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT s.sale_date, m.title, s.seats_booked, s.total_amount
                                FROM sales s
                                JOIN showtimes st ON s.showtime_id = st.id
                                JOIN movies m ON st.movie_id = m.id
                                ORDER BY s.sale_date DESC
                                LIMIT 10
                            ");
                            $recentSales = $stmt->fetchAll();
                            
                            foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($sale['title']); ?></td>
                                    <td><?php echo $sale['seats_booked']; ?></td>
                                    <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach;
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='4'>Error loading recent sales</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Top Movies Chart
        const moviesCtx = document.getElementById('moviesChart').getContext('2d');
        const moviesChart = new Chart(moviesCtx, {
            type: 'line', 
            data: {
                labels: <?php echo json_encode(array_column($topMovies, 'title')); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode(array_column($topMovies, 'revenue')); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)', // lighter for line area
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    fill: true, // fills the area under the line
                    tension: 0.3 // adds smooth curve
        }]
    },
        options: {
            responsive: true,
            scales: {
                    y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                        return '₱' + value;
                    }
                }
            }
        }
    }
});

        // Top Extras Chart
        const extrasCtx = document.getElementById('extrasChart').getContext('2d');
        const extrasChart = new Chart(extrasCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($topExtras, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($topExtras, 'total_sold')); ?>,
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(52, 152, 219, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>