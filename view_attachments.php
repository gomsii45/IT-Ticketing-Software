<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin' || !isset($_GET['ticket_id'])) {
    header("Location: index.php");
    exit();
}

$ticket_id = $_GET['ticket_id'];

// Fetch ticket details
$stmt = $pdo->prepare("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.ticket_id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

// Fetch attachments
$stmt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);
$attachments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attachments - Ticket #<?php echo $ticket_id; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="dashboard">
    <div class="dashboard-container">
        <header>
            <h1>Attachments for Ticket #<?php echo $ticket_id; ?></h1>
            <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
        </header>

        <div class="ticket-details">
            <div class="ticket-info">
                <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <p class="meta">
                    Created by: <?php echo htmlspecialchars($ticket['username']); ?><br>
                    Created: <?php echo $ticket['created_at']; ?>
                </p>
            </div>

            <div class="attachments-grid">
                <?php foreach ($attachments as $attachment): 
                    $ext = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                ?>
                <div class="attachment-card">
                    <?php if ($is_image): ?>
                        <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                    <?php else: ?>
                        <div class="file-icon">
                            <i class="fas fa-file"></i>
                            <span>.<?php echo $ext; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="attachment-info">
                        <p><?php echo htmlspecialchars($attachment['file_name']); ?></p>
                        <div class="attachment-actions">
                            <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="btn view-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="download_attachment.php?id=<?php echo $attachment['id']; ?>" class="btn download-btn">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>