<?php
/**
 * Authentication Helper Functions
 * Contains functions for checking admin authentication and logout
 */

require_once '../db/config.php';

/**
 * Check if admin is logged in, redirect to login if not
 */
function requireAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Logout admin and destroy session
 */
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Get current admin info
 * @return array|null Admin data or null if not logged in
 */
function getCurrentAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// Handle logout request
if (isset($_GET['logout'])) {
    logout();
}
?>