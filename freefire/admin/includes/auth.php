<?php
/**
 * FREE FIRE ESPORTS - Admin Authentication Guard Middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['ff_admin_id']) || empty($_SESSION['ff_admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
