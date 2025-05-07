<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';  // Add this line at the top

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$ticket_id = $_GET['id'];

// Fetch ticket details
$stmt = $pdo->prepare("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.ticket_id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

// Check if user has permission to view this ticket
if (!$ticket || ($_SESSION['user_type'] != 'admin' && $ticket['user_id'] != $_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}

// Handle status updates (admin only)
if ($_SESSION['user_type'] == 'admin' && isset($_POST['status'])) {
    // Get ticket info before update
    $stmt = $pdo->prepare("SELECT user_id, subject FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticketInfo = $stmt->fetch();
    
    // Update ticket status
    $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ?");
    $stmt->execute([$_POST['status'], $ticket_id]);
    
    // Create notification with more detailed message
    $message = "Your ticket '" . $ticketInfo['subject'] . "' (ID: " . $ticket_id . ") has been " . 
               ($_POST['status'] == 'closed' ? 'resolved' : 'updated to status: ' . $_POST['status']);
    
               createNotification($pdo, $user_id, $ticket_id, "Your message here");
    
    header("Location: view_ticket.php?id=" . $ticket_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard">
    <div class="dashboard-container">
        <header>
            <h1>Ticket #<?php echo $ticket['ticket_id']; ?></h1>
            <a href="<?php echo $_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn">Back to Dashboard</a>
        </header>
        
        <div class="ticket-details">
            <div class="ticket-info">
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <p class="meta">
                    Created by: <?php echo htmlspecialchars($ticket['username']); ?><br>
                    Status: <?php echo $ticket['status']; ?><br>
                    Priority: <?php echo $ticket['priority']; ?><br>
                    Created: <?php echo $ticket['created_at']; ?>
                </p>
                
                <div class="description">
                    <h3>Description:</h3>
                    <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                </div>
                
                <!-- Add this after the description div and before admin controls -->
                <div class="attachments">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
                    $stmt->execute([$ticket_id]);
                    $attachments = $stmt->fetchAll();
                    
                    if (!empty($attachments)): ?>
                        <h3>Attachments:</h3>
                        <ul class="attachment-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <li>
                                    <a href="download_attachment.php?id=<?php echo $attachment['id']; ?>" class="attachment-link">
                                        <i class="fas fa-download"></i>
                                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <?php if ($_SESSION['user_type'] == 'admin'): ?>
                <div class="admin-controls">
                    <h3>Update Status:</h3>
                    <form method="POST" action="">
                        <select name="status" onchange="this.form.submit()">
                            <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>