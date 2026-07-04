<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? $_SESSION['name'] ?? 'User';

/**
 * Count a user's messages by table and optional status.
 */
function getMessageCount(mysqli $conn, string $table, int $user_id, ?string $status = null): int
{
    $allowed_tables = ['sms_messages', 'whatsapp_messages'];

    if (!in_array($table, $allowed_tables, true)) {
        return 0;
    }

    if ($status === null) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE user_id = ? AND status = ?");
        $stmt->bind_param("is", $user_id, $status);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($result['total'] ?? 0);
}

// SMS statistics
$total_sms = getMessageCount($conn, 'sms_messages', $user_id);
$sent_sms = getMessageCount($conn, 'sms_messages', $user_id, 'sent');
$failed_sms = getMessageCount($conn, 'sms_messages', $user_id, 'failed');

// WhatsApp statistics
$total_whatsapp = getMessageCount($conn, 'whatsapp_messages', $user_id);
$sent_whatsapp = getMessageCount($conn, 'whatsapp_messages', $user_id, 'sent');
$failed_whatsapp = getMessageCount($conn, 'whatsapp_messages', $user_id, 'failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beem Messenger Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: #f4f7fb;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: #ffffff;
            border-radius: 18px;
        }

        .stat-card,
        .action-card {
            border: 0;
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover,
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .action-icon {
            font-size: 2.25rem;
        }

        .btn {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container py-2">
        <a class="navbar-brand text-primary" href="index.php">📨 Beem Messenger</a>

        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Home</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="dashboard-header p-4 p-md-5 mb-5 shadow-sm">
        <h1 class="h2 fw-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="mb-0">Send and manage your SMS and WhatsApp messages from one dashboard.</p>
    </section>

    <section class="mb-5">
        <h2 class="h4 section-title">SMS Overview</h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Total SMS Attempts</p>
                        <p class="stat-number text-primary"><?php echo $total_sms; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Sent SMS</p>
                        <p class="stat-number text-success"><?php echo $sent_sms; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Failed SMS</p>
                        <p class="stat-number text-danger"><?php echo $failed_sms; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="h4 section-title">WhatsApp Overview</h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Total WhatsApp Attempts</p>
                        <p class="stat-number text-primary"><?php echo $total_whatsapp; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Sent WhatsApp</p>
                        <p class="stat-number text-success"><?php echo $sent_whatsapp; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">Failed WhatsApp</p>
                        <p class="stat-number text-danger"><?php echo $failed_whatsapp; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 section-title">Quick Actions</h2>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card action-card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="action-icon mb-3">📱</div>
                        <h3 class="h5">Send SMS</h3>
                        <p class="text-muted">Send a new SMS message using the Beem SMS API.</p>
                        <a href="send_sms.php" class="btn btn-primary mt-auto">Send SMS</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card action-card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="action-icon mb-3">💬</div>
                        <h3 class="h5">Send WhatsApp</h3>
                        <p class="text-muted">Send an approved WhatsApp template through Beem.</p>
                        <a href="send_whatsapp.php" class="btn btn-success mt-auto">Send WhatsApp</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card action-card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="action-icon mb-3">📊</div>
                        <h3 class="h5">Message History</h3>
                        <p class="text-muted">View your SMS and WhatsApp message records.</p>
                        <a href="history.php" class="btn btn-outline-secondary mt-auto">View History</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card action-card h-100 shadow-sm text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="action-icon mb-3">⚙️</div>
                        <h3 class="h5">Profile & Settings</h3>
                        <p class="text-muted">Manage your account and profile settings.</p>
                        <a href="profile.php" class="btn btn-outline-primary mt-auto">Open Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
