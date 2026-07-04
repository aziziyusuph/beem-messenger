<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beem Messenger</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #eef5ff, #f8f9fa);
            min-height: 100vh;
        }

        .hero-section {
            padding: 80px 0;
        }

        .hero-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }

        .brand-badge {
            background: #0d6efd;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 30px;
            display: inline-block;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .feature-card {
            border: none;
            border-radius: 14px;
            transition: 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .btn {
            border-radius: 10px;
        }

        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary" href="index.php">
            📨 Beem Messenger
        </a>

        <div class="d-flex gap-2">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">Dashboard</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-primary btn-sm">Login</a>
                <a href="register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="hero-section">
    <div class="container">

        <div class="card hero-card shadow-lg">
            <div class="card-body p-5 text-center">

                <span class="brand-badge">Beem API Integration Project</span>

                <h1 class="display-5 fw-bold mb-3">
                    Welcome to Beem Messenger
                </h1>

                <?php if ($is_logged_in): ?>
                    <p class="lead text-muted mb-4">
                        Hello, <?php echo htmlspecialchars($user_name); ?>. Send SMS and WhatsApp messages using Beem APIs.
                    </p>
                <?php else: ?>
                    <p class="lead text-muted mb-4">
                        A simple messaging dashboard for sending SMS and WhatsApp messages using Beem APIs.
                    </p>
                <?php endif; ?>

                <div class="d-flex justify-content-center gap-3 flex-wrap mb-5">
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg px-4">
                            Go to Dashboard
                        </a>

                        <a href="send_sms.php" class="btn btn-success btn-lg px-4">
                            Send SMS
                        </a>

                        <a href="send_whatsapp.php" class="btn btn-success btn-lg px-4">
                            Send WhatsApp
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-lg px-4">
                            Login
                        </a>

                        <a href="register.php" class="btn btn-outline-primary btn-lg px-4">
                            Create Account
                        </a>
                    <?php endif; ?>
                </div>

                <div class="row g-4 text-start">

                    <div class="col-md-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body">
                                <h4>📱 Send SMS</h4>
                                <p class="text-muted">
                                    Send SMS messages through the Beem SMS API from your dashboard.
                                </p>

                                <?php if ($is_logged_in): ?>
                                    <a href="send_sms.php" class="btn btn-outline-success w-100">
                                        Open SMS
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-success w-100">
                                        Login to Send SMS
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body">
                                <h4>💬 Send WhatsApp</h4>
                                <p class="text-muted">
                                    Send approved WhatsApp template messages using Beem WhatsApp API.
                                </p>

                                <?php if ($is_logged_in): ?>
                                    <a href="send_whatsapp.php" class="btn btn-outline-success w-100">
                                        Open WhatsApp
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-success w-100">
                                        Login to Send WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body">
                                <h4>📊 Message History</h4>
                                <p class="text-muted">
                                    View sent, pending, and failed messages from your Beem Messenger account.
                                </p>

                                <?php if ($is_logged_in): ?>
                                    <a href="history.php" class="btn btn-outline-secondary w-100">
                                        View History
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-secondary w-100">
                                        Login to View History
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <p class="text-center text-muted mt-4">
            Built with PHP, MySQL, Bootstrap, and Beem APIs.
        </p>

    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>