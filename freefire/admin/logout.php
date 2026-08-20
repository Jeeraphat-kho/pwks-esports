<?php
/**
 * FREE FIRE ESPORTS - Admin Logout
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['ff_admin_id']);
unset($_SESSION['ff_admin_username']);
unset($_SESSION['ff_admin_name']);
unset($_SESSION['ff_admin_role']);
unset($_SESSION['ff_admin_logged_in']);

header('Location: login.php');
exit;
