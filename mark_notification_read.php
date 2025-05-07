<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    markNotificationAsRead($pdo, $_POST['notification_id']);
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>