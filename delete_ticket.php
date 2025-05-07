<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin' || !isset($_POST['ticket_id'])) {
    header("Location: index.php");
    exit();
}

$ticket_id = $_POST['ticket_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete attachments from storage
    $stmt = $pdo->prepare("SELECT file_path FROM ticket_attachments WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $attachments = $stmt->fetchAll();

    foreach ($attachments as $attachment) {
        if (file_exists($attachment['file_path'])) {
            unlink($attachment['file_path']);
        }
    }

    // Delete attachments from database
    $stmt = $pdo->prepare("DELETE FROM ticket_attachments WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);

    // Delete notifications related to this ticket
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE ?");
    $stmt->execute(['%ticket #' . $ticket_id . '%']);

    // Delete the ticket
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);

    // Commit transaction
    $pdo->commit();

    header("Location: admin_dashboard.php?success=1");
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    header("Location: admin_dashboard.php?error=1");
}
exit();
?>