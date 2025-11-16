<?php
/**
 * Admin Sidebar Navigation
 * Reusable sidebar component for all admin pages
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <h3>🎬 Cinema Admin</h3>
    <ul>
        <li>
            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
        </li>
        <li>
            <a href="movies.php" class="<?php echo $currentPage === 'movies.php' ? 'active' : ''; ?>">
                🎥 Movies
            </a>
        </li>
        <li>
            <a href="showtimes.php" class="<?php echo $currentPage === 'showtimes.php' ? 'active' : ''; ?>">
                🕐 Showtimes
            </a>
        </li>
        <li>
            <a href="extras.php" class="<?php echo $currentPage === 'extras.php' ? 'active' : ''; ?>">
                🍿 Extras
            </a>
        </li>
        <li>
            <a href="analytics.php" class="<?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
                📈 Analytics
            </a>
        </li>
        <li>
            <a href="?logout=1" style="margin-top: 20px; border-top: 1px solid #34495e; padding-top: 20px;">
                🚪 Logout
            </a>
        </li>
    </ul>
</div>