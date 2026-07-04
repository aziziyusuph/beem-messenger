<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";
require_once "includes/sms.php";

$result = null;
$error = "";
$phone = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    // Validate phone number
    if (empty($phone)) {
        $error = "Phone number is required.";
    } elseif (!preg_match('/^255[67][0-9]{8}$/', $phone)) {
        $error = "Invalid phone number. Use format like 255712345678 or 255655123456.";
    } elseif (empty($message)) {
        $error = "Message is required.";
    } elseif (strlen($message) > 160) {
        $error = "Message is too long. Please keep it under 160 characters for this demo.";
    } else {
        // Send SMS using Beem API function
        $result = sendSMS($phone, $message);

        // Save SMS result in database
        $status = $result['success'] ? 'sent' : 'failed';
        $api_response = json_encode($result);

        $stmt = $conn->prepare("INSERT INTO sms_messages (user_id, recipient, message, status, api_response) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $_SESSION['user_id'], $phone, $message, $status, $api_response);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send SMS</title>
	<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

<h2>Send SMS</h2>

<p><a href="dashboard.php">Back to Dashboard</a></p>
<p><a href="sms_history.php">View SMS History</a></p>

<?php if (!empty($error)): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($result): ?>
    <?php if ($result['success']): ?>
        <p class="success">SMS sent successfully!</p>
    <?php else: ?>
        <p class="error">Failed to send SMS.</p>
        <pre><?php print_r($result); ?></pre>
    <?php endif; ?>
<?php endif; ?>

<form method="POST">
    <label>Phone Number</label><br>
    <input 
        type="text" 
        name="phone" 
        placeholder="2557XXXXXXXX" 
        value="<?php echo htmlspecialchars($phone); ?>" 
        required
    >

    <br><br>

    <label>Message</label><br>
    <textarea 
        name="message" 
        rows="5" 
        cols="40" 
        maxlength="160" 
        required
    ><?php echo htmlspecialchars($message); ?></textarea>

    <br>
    <small>Maximum 160 characters.</small>

    <br><br>

    <button type="submit">Send SMS</button>
</form>
</div>
</body>
</html>