<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM sms_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SMS History</title>
	<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

<h2>SMS History</h2>

<p><a href="dashboard.php">Back to Dashboard</a></p>
<p><a href="send_sms.php">Send New SMS</a></p>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Recipient</th>
        <th>Message</th>
        <th>Status</th>
        <th>Date</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['recipient']); ?></td>
                <td><?php echo htmlspecialchars($row['message']); ?></td>
                <td>
                    <?php if ($row['status'] === 'sent'): ?>
                        <span class="status-sent">Sent</span>
                    <?php else: ?>
                        <span class="status-failed">Failed</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5">No SMS history found.</td>
        </tr>
    <?php endif; ?>
</table>
</div>
</body>
</html>