<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";
require_once "config/beem.php";

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Load user profile
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");

if (!$stmt) {
    die("Unable to prepare the user profile query: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/
function getConfigValue(array $constantNames, $fallback = "")
{
    foreach ($constantNames as $constantName) {
        if (defined($constantName)) {
            return constant($constantName);
        }
    }

    return $fallback;
}

function isConfigured($value)
{
    if ($value === null || trim((string) $value) === "") {
        return false;
    }

    $value = strtoupper(trim((string) $value));

    $placeholders = [
        "YOUR_",
        "PASTE_",
        "CHANGE_",
        "ADD_",
        "ENTER_"
    ];

    foreach ($placeholders as $placeholder) {
        if (strpos($value, $placeholder) === 0) {
            return false;
        }
    }

    return true;
}

function maskValue($value, $visibleCharacters = 4)
{
    if (!isConfigured($value)) {
        return "Not configured";
    }

    $value = (string) $value;
    $length = strlen($value);

    if ($length <= $visibleCharacters) {
        return str_repeat("*", $length);
    }

    return substr($value, 0, $visibleCharacters) . str_repeat("*", 8);
}

function displayValue($value)
{
    return isConfigured($value) ? (string) $value : "Not configured";
}

function statusClass($configured)
{
    return $configured ? "status-ready" : "status-missing";
}

function statusText($configured)
{
    return $configured ? "Configured" : "Not configured";
}

/*
|--------------------------------------------------------------------------
| SMS API configuration
|--------------------------------------------------------------------------
*/
$sms_api_key = getConfigValue(["BEEM_API_KEY"]);
$sms_secret_key = getConfigValue(["BEEM_SECRET_KEY"]);
$sms_sender_id = getConfigValue(["BEEM_SENDER_ID"]);
$sms_api_url = getConfigValue(["BEEM_SMS_API_URL", "BEEM_API_URL"]);

$sms_ready = (
    isConfigured($sms_api_key) &&
    isConfigured($sms_secret_key) &&
    isConfigured($sms_sender_id)
);

/*
|--------------------------------------------------------------------------
| WhatsApp API configuration
|--------------------------------------------------------------------------
| These values can use WhatsApp-specific constants in config/beem.php.
| When separate WhatsApp credentials are not defined, the shared Beem
| API key and secret are displayed instead.
*/
$whatsapp_api_key = getConfigValue(
    ["BEEM_WHATSAPP_API_KEY"],
    $sms_api_key
);

$whatsapp_secret_key = getConfigValue(
    ["BEEM_WHATSAPP_SECRET_KEY"],
    $sms_secret_key
);

$whatsapp_from_addr = getConfigValue([
    "BEEM_WHATSAPP_FROM_ADDR",
    "BEEM_WHATSAPP_NUMBER",
    "BEEM_FROM_ADDR"
]);

$whatsapp_api_url = getConfigValue([
    "BEEM_WHATSAPP_API_URL",
    "BEEM_WHATSAPP_URL"
]);

$whatsapp_ready = (
    isConfigured($whatsapp_api_key) &&
    isConfigured($whatsapp_secret_key) &&
    isConfigured($whatsapp_from_addr) &&
    isConfigured($whatsapp_api_url)
);

$username = $user["username"] ?? ($_SESSION["username"] ?? "User");
$email = $user["email"] ?? "Not available";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile & Settings - Beem Messenger</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --page-bg: #f4f7fb;
            --card-border: #e8edf4;
            --primary-dark: #123b73;
            --muted-text: #6c757d;
        }

        body {
            min-height: 100vh;
            background: var(--page-bg);
            color: #1f2937;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .page-header {
            background: linear-gradient(135deg, #0d6efd, #123b73);
            color: #ffffff;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 14px 35px rgba(13, 110, 253, 0.18);
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 0;
        }

        .content-card {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(31, 41, 55, 0.06);
        }

        .content-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 20px 22px;
            border-radius: 16px 16px 0 0;
        }

        .content-card .card-body {
            padding: 22px;
        }

        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding: 14px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .setting-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: #374151;
        }

        .setting-value {
            color: var(--muted-text);
            text-align: right;
            word-break: break-word;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .status-ready {
            color: #146c43;
            background: #d1e7dd;
        }

        .status-missing {
            color: #842029;
            background: #f8d7da;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .action-card {
            border: 0;
            border-radius: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(31, 41, 55, 0.09);
        }

        .security-note {
            border: 0;
            border-left: 5px solid #ffc107;
            border-radius: 12px;
        }

        .nav-link {
            font-weight: 500;
        }

        .nav-link.active {
            color: #0d6efd !important;
        }

        @media (max-width: 767.98px) {
            .page-header {
                padding: 24px;
            }

            .setting-row {
                display: block;
            }

            .setting-value {
                margin-top: 6px;
                text-align: left;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand text-primary" href="index.php">
            📨 Beem Messenger
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavigation"
            aria-controls="mainNavigation"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="send_sms.php">Send SMS</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="send_whatsapp.php">Send WhatsApp</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="history.php?tab=sms">SMS History</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="history.php?tab=whatsapp">WhatsApp History</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="profile.php">Profile</a>
                </li>

                <li class="nav-item ms-lg-2">
                    <a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">

    <section class="page-header mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <p class="text-uppercase small fw-semibold mb-2">Account settings</p>
                <h1 class="h2 fw-bold mb-2">Profile & API Configuration</h1>
                <p>
                    Review your account details and the current Beem SMS and WhatsApp setup.
                </p>
            </div>

            <div>
                <span class="badge bg-light text-primary px-3 py-2">
                    Signed in as <?php echo htmlspecialchars($username); ?>
                </span>
            </div>
        </div>
    </section>

    <div class="row g-4">

        <div class="col-lg-5">
            <section class="card content-card h-100">
                <div class="card-header">
                    <h2 class="h5 mb-1">User Profile</h2>
                    <p class="text-muted small mb-0">Your Beem Messenger account information.</p>
                </div>

                <div class="card-body">
                    <div class="setting-row">
                        <span class="setting-label">User ID</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars((string) $user["id"]); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">Username</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars($username); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">Email address</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars($email); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">Account status</span>
                        <span class="status-pill status-ready">
                            <span class="status-dot"></span>
                            Active
                        </span>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="card content-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">Beem SMS Configuration</h2>
                        <p class="text-muted small mb-0">Settings used when sending SMS messages.</p>
                    </div>

                    <span class="status-pill <?php echo statusClass($sms_ready); ?>">
                        <span class="status-dot"></span>
                        <?php echo statusText($sms_ready); ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="setting-row">
                        <span class="setting-label">API key</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars(maskValue($sms_api_key)); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">Secret key</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars(maskValue($sms_secret_key)); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">Sender ID</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars(displayValue($sms_sender_id)); ?>
                        </span>
                    </div>

                    <div class="setting-row">
                        <span class="setting-label">API endpoint</span>
                        <span class="setting-value">
                            <?php echo htmlspecialchars(displayValue($sms_api_url)); ?>
                        </span>
                    </div>

                    <a href="send_sms.php" class="btn btn-primary w-100 mt-4">
                        Send an SMS
                    </a>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="card content-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">Beem WhatsApp Configuration</h2>
                        <p class="text-muted small mb-0">
                            Settings used for approved WhatsApp template messages.
                        </p>
                    </div>

                    <span class="status-pill <?php echo statusClass($whatsapp_ready); ?>">
                        <span class="status-dot"></span>
                        <?php echo statusText($whatsapp_ready); ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="row g-0">
                        <div class="col-md-6 pe-md-4">
                            <div class="setting-row">
                                <span class="setting-label">WhatsApp API key</span>
                                <span class="setting-value">
                                    <?php echo htmlspecialchars(maskValue($whatsapp_api_key)); ?>
                                </span>
                            </div>

                            <div class="setting-row">
                                <span class="setting-label">WhatsApp secret key</span>
                                <span class="setting-value">
                                    <?php echo htmlspecialchars(maskValue($whatsapp_secret_key)); ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6 ps-md-4">
                            <div class="setting-row">
                                <span class="setting-label">From address</span>
                                <span class="setting-value">
                                    <?php echo htmlspecialchars(displayValue($whatsapp_from_addr)); ?>
                                </span>
                            </div>

                            <div class="setting-row">
                                <span class="setting-label">API endpoint</span>
                                <span class="setting-value">
                                    <?php echo htmlspecialchars(displayValue($whatsapp_api_url)); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid d-md-flex gap-2 mt-4">
                        <a href="send_whatsapp.php" class="btn btn-success flex-fill">
                            Send a WhatsApp Message
                        </a>

                        <a href="history.php?tab=whatsapp" class="btn btn-outline-success flex-fill">
                            View WhatsApp History
                        </a>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12">
            <div class="alert alert-warning security-note shadow-sm mb-0">
                <h2 class="h6 fw-bold">Security reminder</h2>
                <p class="mb-0">
                    Never display complete API keys or secret keys in the browser.
                    Keep all credentials inside <strong>config/beem.php</strong> and
                    do not publish that file in a public GitHub repository.
                </p>
            </div>
        </div>

        <div class="col-12">
            <section>
                <h2 class="h5 mb-3">Quick Actions</h2>

                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <a href="send_sms.php" class="text-decoration-none">
                            <div class="card action-card shadow-sm h-100">
                                <div class="card-body">
                                    <h3 class="h6 text-dark">Send SMS</h3>
                                    <p class="text-muted small mb-0">Create and send a new SMS message.</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a href="send_whatsapp.php" class="text-decoration-none">
                            <div class="card action-card shadow-sm h-100">
                                <div class="card-body">
                                    <h3 class="h6 text-dark">Send WhatsApp</h3>
                                    <p class="text-muted small mb-0">Send an approved template message.</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a href="history.php?tab=sms" class="text-decoration-none">
                            <div class="card action-card shadow-sm h-100">
                                <div class="card-body">
                                    <h3 class="h6 text-dark">SMS History</h3>
                                    <p class="text-muted small mb-0">Review your SMS delivery records.</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a href="history.php?tab=whatsapp" class="text-decoration-none">
                            <div class="card action-card shadow-sm h-100">
                                <div class="card-body">
                                    <h3 class="h6 text-dark">WhatsApp History</h3>
                                    <p class="text-muted small mb-0">Review your WhatsApp message records.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        </div>

    </div>
</main>

<footer class="py-4 text-center text-muted small">
    Beem Messenger &copy; <?php echo date("Y"); ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
