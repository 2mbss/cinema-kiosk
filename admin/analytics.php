<?php
/**
 * Analytics Page - All data from database with proper error handling
 * Every metric comes directly from your cinema_kiosk database tables
 */

require_once 'includes/auth.php';
requireAuth();

$pdo = getDBConnection();

// Get filter period (default to monthly)
$period = $_GET['period'] ?? 'monthly';

// Set date range based on period - this determines what data we show
switch ($period) {
    case 'daily':
        $dateFormat = '%Y-%m-%d';
        $dateRange = "DATE(sale_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $groupBy = "DATE(sale_date)";
        break;
    case 'weekly':
        $dateFormat = '%Y Week %u';
        $dateRange = "sale_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)";
        $groupBy = "YEARWEEK(sale_date)";
        break;
    case 'monthly':
    default:
        $dateFormat = '%Y-%m';
        $dateRange = "sale_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        $groupBy = "DATE_FORMAT(sale_date, '%Y-%m')";
        break;
}

// Initialize all variables to prevent "undefined variable" errors
$revenueData = [];
$movieTickets = [];
$topMoviesByRevenue = [];
$concessionsByCategory = [];
$peakHours = [];
$dailyTrend = [];
$monthlyStats = [];
$totalSalesCount = 0;
$totalRevenue = 0;
$growthRate = 0;

try {
    // 1. REVENUE OVER TIME - Shows how sales change over selected period
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(sale_date, ?) as period, 
               SUM(total_amount) as revenue,
               COUNT(*) as transactions
        FROM sales 
        WHERE $dateRange
        GROUP BY $groupBy
        ORDER BY period
    ");
    $stmt->execute([$dateFormat]);
    $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 2. TICKETS SOLD PER MOVIE - Count actual tickets from sales table
    $stmt = $pdo->query("
        SELECT m.title, 
               COALESCE(SUM(s.seats_booked), 0) as tickets_sold,
               COALESCE(SUM(s.total_amount), 0) as revenue,
               COUNT(s.id) as total_sales
        FROM movies m
        LEFT JOIN showtimes st ON m.id = st.movie_id
        LEFT JOIN sales s ON st.id = s.showtime_id
        WHERE m.status = 'active'
        GROUP BY m.id, m.title
        ORDER BY tickets_sold DESC
        LIMIT 10
    ");
    $movieTickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 3. TOP MOVIES BY REVENUE - Different from tickets, shows money earned
    $stmt = $pdo->query("
        SELECT m.title, 
               COALESCE(SUM(s.total_amount), 0) as revenue,
               COALESCE(SUM(s.seats_booked), 0) as tickets_sold
        FROM movies m
        LEFT JOIN showtimes st ON m.id = st.movie_id
        LEFT JOIN sales s ON st.id = s.showtime_id
        WHERE m.status = 'active'
        GROUP BY m.id, m.title
        HAVING revenue > 0
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $topMoviesByRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 4. CONCESSIONS ANALYTICS - Real data from extras and sales_extras tables
    $stmt = $pdo->query("
        SELECT 
            e.category,
            e.name,
            COALESCE(SUM(se.quantity), 0) as quantity_sold,
            COALESCE(SUM(se.quantity * e.price), 0) as revenue,
            COUNT(DISTINCT se.sale_id) as orders_with_item
        FROM extras e
        LEFT JOIN sales_extras se ON e.id = se.extra_id
        LEFT JOIN sales s ON se.sale_id = s.id 
        WHERE e.status = 'active'
        GROUP BY e.id, e.category, e.name
        ORDER BY revenue DESC
    ");
    $concessionsByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 5. PEAK HOURS ANALYSIS - When do most sales happen?
    $stmt = $pdo->query("
        SELECT HOUR(sale_date) as hour,
               COUNT(*) as transactions,
               SUM(total_amount) as revenue
        FROM sales
        WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY HOUR(sale_date)
        ORDER BY transactions DESC
    ");
    $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 6. DAILY SALES TREND - Which days are busiest?
    $stmt = $pdo->query("
        SELECT DAYNAME(sale_date) as day_name,
               DAYOFWEEK(sale_date) as day_num,
               COUNT(*) as transactions,
               SUM(total_amount) as revenue
        FROM sales
        WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DAYOFWEEK(sale_date), DAYNAME(sale_date)
        ORDER BY transactions DESC
    ");
    $dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 7. MONTHLY STATISTICS - Current vs previous month comparison
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) 
                THEN total_amount ELSE 0 END) as current_month_revenue,
            SUM(CASE WHEN MONTH(sale_date) = MONTH(CURDATE()) - 1 AND YEAR(sale_date) = YEAR(CURDATE()) 
                THEN total_amount ELSE 0 END) as last_month_revenue,
            COUNT(CASE WHEN MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) 
                THEN 1 END) as current_month_sales,
            COUNT(CASE WHEN MONTH(sale_date) = MONTH(CURDATE()) - 1 AND YEAR(sale_date) = YEAR(CURDATE()) 
                THEN 1 END) as last_month_sales
        FROM sales
        WHERE YEAR(sale_date) = YEAR(CURDATE())
    ");
    $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Calculate growth rate safely
    if (!empty($monthlyStats) && $monthlyStats['last_month_revenue'] > 0) {
        $growthRate = (($monthlyStats['current_month_revenue'] - $monthlyStats['last_month_revenue']) 
                      / $monthlyStats['last_month_revenue']) * 100;
    }

    // 8. TOTAL SALES STATISTICS - For calculating attach rates
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_sales, SUM(total_amount) as total_revenue 
        FROM sales 
        WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())
    ");
    $totalStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSalesCount = $totalStats['total_sales'] ?? 0;
    $totalRevenue = $totalStats['total_revenue'] ?? 0;

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Helper function to calculate attach rate (what % of customers buy extras)
function calculateAttachRate($ordersWithItem, $totalOrders) {
    return $totalOrders > 0 ? round(($ordersWithItem / $totalOrders) * 100, 1) : 0;
}

// Helper function to safely get array value
function safeArrayValue($array, $key, $default = 'No data') {
    return !empty($array) && isset($array[$key]) ? $array[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Cinema Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .period-filter {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 14px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .metric-section h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .metric-item:last-child {
            border-bottom: none;
        }
        
        .metric-value {
            font-weight: bold;
            color: #27ae60;
        }
        
        .no-data {
            color: #7f8c8d;
            font-style: italic;
        }
        
        .growth-positive { color: #27ae60; }
        .growth-negative { color: #e74c3c; }
        
        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
            .analytics-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="analytics-header">
                <h1>📈 Analytics Dashboard</h1>
                <select class="period-filter" onchange="window.location.href='?period='+this.value">
                    <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily (7 days)</option>
                    <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly (8 weeks)</option>
                    <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly (12 months)</option>
                </select>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Main Charts Section -->
            <div class="charts-grid">
                <!-- Revenue Over Time Chart -->
                <div class="chart-container">
                    <h3>💰 Sales and Revenue Over Time</h3>
                    <?php if (!empty($revenueData)): ?>
                        <canvas id="revenueChart" height="100"></canvas>
                    <?php else: ?>
                        <div class="no-data">No sales data available for the selected period</div>
                    <?php endif; ?>
                </div>
                
                <!-- Top Movies Chart -->
                <div class="chart-container">
                    <h3>🏆 Top Movies by Revenue</h3>
                    <?php if (!empty($topMoviesByRevenue)): ?>
                        <canvas id="topMoviesChart" height="200"></canvas>
                    <?php else: ?>
                        <div class="no-data">No movie revenue data available</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tickets Sold Chart -->
            <div class="chart-container" style="margin-bottom: 30px;">
                <h3>🎟️ Tickets Sold Per Movie</h3>
                <?php if (!empty($movieTickets)): ?>
                    <canvas id="ticketsChart" height="80"></canvas>
                <?php else: ?>
                    <div class="no-data">No ticket sales data available</div>
                <?php endif; ?>
            </div>
            
            <!-- Detailed Analytics Sections -->
            <div class="metrics-grid">
                <!-- Financial Metrics -->
                <div class="metric-section">
                    <h3>💸 Financial Metrics</h3>
                    <div class="metric-item">
                        <span>Total Revenue (This Month):</span>
                        <div class="metric-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                    </div>
                    <div class="metric-item">
                        <span>Total Sales (This Month):</span>
                        <div class="metric-value"><?php echo $totalSalesCount; ?> transactions</div>
                    </div>
                    <div class="metric-item">
                        <span>Average Sale Amount:</span>
                        <div class="metric-value">
                            ₱<?php echo $totalSalesCount > 0 ? number_format($totalRevenue / $totalSalesCount, 2) : '0.00'; ?>
                        </div>
                    </div>
                    <div class="metric-item">
                        <span>Monthly Growth:</span>
                        <div class="metric-value <?php echo $growthRate >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                            <?php echo $growthRate >= 0 ? '+' : ''; ?><?php echo number_format($growthRate, 1); ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Concessions & Add-ons -->
                <div class="metric-section">
                    <h3>🍦 Concessions & Add-ons</h3>
                    <?php if (!empty($concessionsByCategory)): ?>
                        <?php 
                        // Group by category for better display
                        $snackRevenue = 0;
                        $drinkRevenue = 0;
                        $snackOrders = 0;
                        $drinkOrders = 0;
                        
                        foreach ($concessionsByCategory as $item) {
                            if ($item['category'] === 'snack') {
                                $snackRevenue += $item['revenue'];
                                $snackOrders += $item['orders_with_item'];
                            } else {
                                $drinkRevenue += $item['revenue'];
                                $drinkOrders += $item['orders_with_item'];
                            }
                        }
                        ?>
                        <div class="metric-item">
                            <span>Snacks Revenue:</span>
                            <div>
                                <div class="metric-value">₱<?php echo number_format($snackRevenue, 2); ?></div>
                                <small><?php echo calculateAttachRate($snackOrders, $totalSalesCount); ?>% attach rate</small>
                            </div>
                        </div>
                        <div class="metric-item">
                            <span>Drinks Revenue:</span>
                            <div>
                                <div class="metric-value">₱<?php echo number_format($drinkRevenue, 2); ?></div>
                                <small><?php echo calculateAttachRate($drinkOrders, $totalSalesCount); ?>% attach rate</small>
                            </div>
                        </div>
                        <div class="metric-item">
                            <span>Total Concessions:</span>
                            <div class="metric-value">₱<?php echo number_format($snackRevenue + $drinkRevenue, 2); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No concessions data available</div>
                    <?php endif; ?>
                </div>
                
                <!-- Customer Analytics & Operations -->
                <div class="metric-section">
                    <h3>👥 Customer Analytics & Operations</h3>
                    <div class="metric-item">
                        <span>Peak Hours:</span>
                        <div class="metric-value">
                            <?php 
                            if (!empty($peakHours)) {
                                $peakHour = $peakHours[0]['hour'];
                                echo $peakHour . ':00 - ' . ($peakHour + 2) . ':00';
                            } else {
                                echo '<span class="no-data">No data</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="metric-item">
                        <span>Busiest Day:</span>
                        <div class="metric-value">
                            <?php echo !empty($dailyTrend) ? $dailyTrend[0]['day_name'] : '<span class="no-data">No data</span>'; ?>
                        </div>
                    </div>
                    <div class="metric-item">
                        <span>Current Month Sales:</span>
                        <div class="metric-value">
                            <?php echo !empty($monthlyStats) ? $monthlyStats['current_month_sales'] : 0; ?> transactions
                        </div>
                    </div>
                    <div class="metric-item">
                        <span>Booking Channel:</span>
                        <div class="metric-value">100% Kiosk System</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Only create charts if we have data
        <?php if (!empty($revenueData)): ?>
        // Revenue Over Time Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueData, 'period')); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode(array_column($revenueData, 'revenue')); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
        <?php endif; ?>

        <?php if (!empty($topMoviesByRevenue)): ?>
        // Top Movies Chart
        const topMoviesCtx = document.getElementById('topMoviesChart').getContext('2d');
        new Chart(topMoviesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topMoviesByRevenue, 'title')); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode(array_column($topMoviesByRevenue, 'revenue')); ?>,
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
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
        <?php endif; ?>

        <?php if (!empty($movieTickets)): ?>
        // Tickets Sold Chart
        const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
        new Chart(ticketsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($movieTickets, 'title')); ?>,
                datasets: [{
                    label: 'Tickets Sold',
                    data: <?php echo json_encode(array_column($movieTickets, 'tickets_sold')); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.8)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>