<?php
function createNotification($pdo, $user_id, $ticket_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, ticket_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $ticket_id, $message]);
        return true;
    } catch(PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotifications($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, t.subject as ticket_subject 
            FROM notifications n
            JOIN tickets t ON n.ticket_id = t.ticket_id
            WHERE n.user_id = ? AND n.is_read = FALSE
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

function markNotificationAsRead($pdo, $notification_id) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        $stmt->execute([$notification_id]);
        return true;
    } catch(PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}
?>