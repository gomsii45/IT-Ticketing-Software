<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all tickets with user information and attachment count
try {
    $stmt = $pdo->query("
        SELECT t.*, u.username, COUNT(ta.id) as attachment_count
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN ticket_attachments ta ON t.ticket_id = ta.ticket_id
        GROUP BY t.ticket_id
        ORDER BY t.created_at DESC
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $tickets = [];
    error_log("Error fetching tickets: " . $e->getMessage());
}

// Fetch unread notifications
$notifications = getUnreadNotifications($pdo, $_SESSION['user_id']);
?>

<!-- After the admin-header div, add this: -->
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="dashboard">
    <div class="admin-dashboard">
        <div class="admin-header">
            <div class="admin-header-content">
                <div class="logo-container">
                    <img src="images/logo.jpg" alt="Quality Austria" class="site-logo">
                    <div class="header-text">
                        <h1 class="header-title">Admin Dashboard</h1>
                        <p class="welcome-text">Welcome, Administrator</p>
                    </div>
                </div>
                <div class="admin-actions">
                    <a href="manage_users.php" class="admin-btn manage-btn">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="create_ticket.php" class="admin-btn create-btn">
                        <i class="fas fa-plus-circle"></i> New Ticket
                    </a>
                    <a href="logout.php" class="admin-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="tickets-header">
                <h2><i class="fas fa-ticket-alt"></i> All Tickets</h2>
                <!-- Update the stats section -->
                <div class="ticket-stats">
                    <span class="stat-item">
                        <i class="fas fa-clock fa-2x" style="color: #3498db;"></i>
                        <div>
                            <h3>Open Tickets</h3>
                            <strong><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'open'; })); ?></strong>
                        </div>
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-spinner fa-2x" style="color: #f39c12;"></i>
                        <div>
                            <h3>In Progress</h3>
                            <strong><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'in_progress'; })); ?></strong>
                        </div>
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-check-circle fa-2x" style="color: #2ecc71;"></i>
                        <div>
                            <h3>Closed Tickets</h3>
                            <strong><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'closed'; })); ?></strong>
                        </div>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> User</th>
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
                            <td><?php echo $ticket['ticket_id']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td><?php echo $ticket['status']; ?></td>
                            <td><?php echo $ticket['priority']; ?></td>
                            <!-- Modify the attachments column in the table -->
                            <td>
                                <?php if ($ticket['attachment_count'] > 0): ?>
                                    <a href="view_attachments.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="attachment-link">
                                        <i class="fas fa-eye"></i>
                                        <?php echo $ticket['attachment_count']; ?> file(s)
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $ticket['created_at']; ?></td>
                            <!-- Modify the Action column in the table -->
                            <td>
                                <div class="action-buttons">
                                    <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn">View</a>
                                    <form method="POST" action="delete_ticket.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>