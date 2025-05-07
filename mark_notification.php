<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    header("Location: index.php");
    exit();
}

markNotificationAsRead($pdo, $_POST['notification_id']);
header("Location: " . ($_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
exit();
?>