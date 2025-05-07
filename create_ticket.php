<?php
session_start();
require_once 'config.php';
require_once 'includes/notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    
    // Insert ticket
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, description, priority) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $subject, $description, $priority]);
    $ticket_id = $pdo->lastInsertId();
    
    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['attachment']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->execute([$ticket_id, $filename, $upload_path]);
            }
        }
    }
    
    // Notify admins
    $stmt = $pdo->query("SELECT id FROM users WHERE user_type = 'admin'");
    $admins = $stmt->fetchAll();
    
    foreach ($admins as $admin) {
        createNotification($pdo, $user_id, $ticket_id, "Your message here");
    }
    
    header("Location: " . ($_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Ticket</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard">
    <div class="dashboard-container">
        <header>
            <h1>Create New Ticket</h1>
            <a href="<?php echo $_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn">Back to Dashboard</a>
        </header>
        
        <div class="ticket-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Attachment (PDF, DOC, DOCX, JPG, JPEG, PNG):</label>
                    <input type="file" id="attachment" name="attachment" class="file-input">
                </div>
                
                <button type="submit" class="btn">Create Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>