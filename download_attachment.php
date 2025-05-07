<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Fetch attachment details
$stmt = $pdo->prepare("
    SELECT ta.*, t.user_id 
    FROM ticket_attachments ta 
    JOIN tickets t ON ta.ticket_id = t.ticket_id 
    WHERE ta.id = ?
");
$stmt->execute([$_GET['id']]);
$attachment = $stmt->fetch();

// Check if attachment exists and user has permission
if (!$attachment || ($_SESSION['user_type'] != 'admin' && $attachment['user_id'] != $_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$file_path = $attachment['file_path'];

if (file_exists($file_path)) {
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($attachment['file_name']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Output file
    readfile($file_path);
    exit();
} else {
    echo "File not found.";
}