<?php
/**
 * GOLAÇO - Entry Router
 */

// If database is not configured, redirect to installer
if (!file_exists(__DIR__ . '/config/db.php')) {
    header('Location: install.php');
    exit;
}

session_start();

// If user is logged in, redirect to dashboard. Otherwise, redirect to login page.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
