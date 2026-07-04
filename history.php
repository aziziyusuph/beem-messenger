<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'history.php';
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? ($_SESSION['username'] ?? 'User');
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'whatsapp') ? 'whatsapp' : 'sms';
$sms_messages = [];
$whatsapp_messages = [];
$page_error = '';

/**
 * Return the first non-empty value found in a database row.
 */
function first_value(array $row, array $keys, string $default = '—'): string
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
            return (string) $row[$key];
        }
    }

    return $default;
}

/**
 * Safely format a database date.
 */
function format_message_date(array $row): string
{
    $date = first_value($row, ['created_at', 'sent_at', 'updated_at'], '');

    if ($date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d M Y, H:i', $timestamp) : $date;
}

/**
 * Return a Bootstrap badge class based on message status.
 */
function status_badge_class(string $status): string
{
    return match (strtolower($status)) {
        'sent', 'delivered', 'success', 'successful' => 'bg-success',
        'pending', 'queued', 'processing' => 'bg-warning text-dark',
        'failed', 'error', 'undelivered' => 'bg-danger',
        default => 'bg-secondary',
    };
}

try {
    $sms_stmt = $conn->prepare('SELECT * FROM sms_messages WHERE user_id = ? ORDER BY id DESC');
    $sms_stmt->bind_param('i', $user_id);
    $sms_stmt->execute();
    $sms_result = $sms_stmt->get_result();
    $sms_messages = $sms_result->fetch_all(MYSQLI_ASSOC);
    $sms_stmt->close();

    $whatsapp_stmt = $conn->prepare('SELECT * FROM whatsapp_messages WHERE user_id = ? ORDER BY id DESC');
    $whatsapp_stmt->bind_param('i', $user_id);
    $whatsapp_stmt->execute();
    $whatsapp_result = $whatsapp_stmt->get_result();
    $whatsapp_messages = $whatsapp_result->fetch_all(MYSQLI_ASSOC);
    $whatsapp_stmt->close();
} catch (Throwable $exception) {
    $page_error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message History - Beem Messenger</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
            min-height: 100vh;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .history-card {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
        }

        .table thead th {
            white-space: nowrap;
        }

        .message-text {
            min-width: 230px;
            max-width: 420px;
            white-space: normal;
            word-break: break-word;
        }

        .phone-number {
            white-space: nowrap;
        }

        .empty-state {
            padding: 45px 20px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">📨 Beem Messenger</a>

        <div class="d-flex align-items-center gap-2">
            <span class="text-white d-none d-md-inline">
                <?php echo htmlspecialchars($user_name); ?>
            </span>
            <a href="dashboard.php" class="btn btn-light btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Message History</h1>
            <p class="text-muted mb-0">View SMS and WhatsApp messages sent from your account.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="send_sms.php" class="btn btn-success">Send SMS</a>
            <a href="send_whatsapp.php" class="btn btn-success">Send WhatsApp</a>
        </div>
    </div>

    <?php if ($page_error !== ''): ?>
        <div class="alert alert-danger shadow-sm">
            <strong>Database error:</strong>
            <?php echo htmlspecialchars($page_error); ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="historyTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link <?php echo $active_tab === 'sms' ? 'active' : ''; ?>"
                id="sms-tab"
                data-bs-toggle="tab"
                data-bs-target="#sms-history"
                type="button"
                role="tab"
            >
                SMS
                <span class="badge bg-primary ms-1"><?php echo count($sms_messages); ?></span>
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button
                class="nav-link <?php echo $active_tab === 'whatsapp' ? 'active' : ''; ?>"
                id="whatsapp-tab"
                data-bs-toggle="tab"
                data-bs-target="#whatsapp-history"
                type="button"
                role="tab"
            >
                WhatsApp
                <span class="badge bg-success ms-1"><?php echo count($whatsapp_messages); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <section class="tab-pane fade <?php echo $active_tab === 'sms' ? 'show active' : ''; ?>" id="sms-history" role="tabpanel">
            <div class="card history-card shadow-sm mt-3">
                <?php if (empty($sms_messages)): ?>
                    <div class="empty-state">
                        <h3 class="h5">No SMS history yet</h3>
                        <p class="mb-3">Messages you send through the SMS page will appear here.</p>
                        <a href="send_sms.php" class="btn btn-primary">Send your first SMS</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Recipient</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sms_messages as $index => $message): ?>
                                    <?php
                                    $phone = first_value($message, ['phone_number', 'recipient', 'destination_addr']);
                                    $content = first_value($message, ['message', 'message_content', 'body', 'content']);
                                    $status = first_value($message, ['status'], 'unknown');
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td class="phone-number"><?php echo htmlspecialchars($phone); ?></td>
                                        <td class="message-text"><?php echo nl2br(htmlspecialchars($content)); ?></td>
                                        <td>
                                            <span class="badge <?php echo status_badge_class($status); ?>">
                                                <?php echo htmlspecialchars(ucfirst($status)); ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars(format_message_date($message)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="tab-pane fade <?php echo $active_tab === 'whatsapp' ? 'show active' : ''; ?>" id="whatsapp-history" role="tabpanel">
            <div class="card history-card shadow-sm mt-3">
                <?php if (empty($whatsapp_messages)): ?>
                    <div class="empty-state">
                        <h3 class="h5">No WhatsApp history yet</h3>
                        <p class="mb-3">WhatsApp template messages you send will appear here.</p>
                        <a href="send_whatsapp.php" class="btn btn-success">Send your first WhatsApp message</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Recipient</th>
                                    <th>Template</th>
                                    <th>Content</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($whatsapp_messages as $index => $message): ?>
                                    <?php
                                    $phone = first_value($message, ['phone_number', 'recipient', 'destination_addr']);
                                    $template = first_value($message, ['template_name', 'template_id'], '—');
                                    $content = first_value($message, ['message_content', 'template_variables', 'message', 'body']);
                                    $status = first_value($message, ['status'], 'unknown');
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td class="phone-number"><?php echo htmlspecialchars($phone); ?></td>
                                        <td><?php echo htmlspecialchars($template); ?></td>
                                        <td class="message-text"><?php echo nl2br(htmlspecialchars($content)); ?></td>
                                        <td>
                                            <span class="badge <?php echo status_badge_class($status); ?>">
                                                <?php echo htmlspecialchars(ucfirst($status)); ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars(format_message_date($message)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
