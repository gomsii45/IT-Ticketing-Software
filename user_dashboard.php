<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: index.php");
    exit();
}

// Fetch user's tickets with attachment count
try {
    $stmt = $pdo->prepare("
        SELECT t.*, COUNT(ta.id) as attachment_count
        FROM tickets t 
        LEFT JOIN ticket_attachments ta ON t.ticket_id = ta.ticket_id
        WHERE t.user_id = ?
        GROUP BY t.ticket_id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $tickets = [];
    error_log("Error fetching tickets: " . $e->getMessage());
}

// Fetch unread notifications
$notifications = getUnreadNotifications($pdo, $_SESSION['user_id']);
?>

<!-- After the dashboard-header div, add this: -->
<div class="notifications-area">
    <?php if (!empty($notifications)): ?>
        <div class="notifications-container">
            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            <?php foreach($notifications as $notification): ?>
                <div class="notification-item">
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small>
                            Ticket: <?php echo htmlspecialchars($notification['ticket_subject']); ?>
                            <span class="notification-time">
                                <?php echo date('M d, H:i', strtotime($notification['created_at'])); ?>
                            </span>
                        </small>
                    </div>
                    <form method="POST" action="mark_notification_read.php">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <button type="submit" class="mark-read-btn" title="Mark as read">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Quality Austria</title>
    <link rel="stylesheet" href="css/user-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="user-dashboard">
        <div class="dashboard-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="images/logo.jpg" alt="Quality Austria" class="site-logo">
                    <div class="header-text">
                        <h1>User Dashboard</h1>
                        <p class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="create_ticket.php" class="action-btn create-btn">
                        <i class="fas fa-plus-circle"></i> New Ticket
                    </a>
                    <a href="logout.php" class="action-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="tickets-overview">
                <h2><i class="fas fa-ticket-alt"></i> My Tickets</h2>
                <div class="ticket-stats">
                    <div class="stat-card">
                        <i class="fas fa-clock fa-2x"></i>
                        <div class="stat-info">
                            <h3>Open</h3>
                            <span class="stat-number"><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'open'; })); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-spinner fa-2x"></i>
                        <div class="stat-info">
                            <h3>In Progress</h3>
                            <span class="stat-number"><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'in_progress'; })); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle fa-2x"></i>
                        <div class="stat-info">
                            <h3>Closed</h3>
                            <span class="stat-number"><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'closed'; })); ?></span>
                        </div>
                    </div>
                </div>

                <div class="tickets-table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-heading"></i> Subject</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-exclamation-circle"></i> Priority</th>
                                <th><i class="fas fa-paperclip"></i> Attachments</th>
                                <th><i class="fas fa-calendar-alt"></i> Created</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['ticket_id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($ticket['status']); ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?php echo strtolower($ticket['priority']); ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ticket['attachment_count'] > 0): ?>
                                        <a href="view_attachments.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="attachment-link">
                                            <i class="fas fa-paperclip"></i> <?php echo $ticket['attachment_count']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="no-attachments">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>