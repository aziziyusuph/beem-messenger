<?php
session_start();
require_once __DIR__ . "/config/database.php";

$message = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: dashboard.php");
            exit();

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "Email not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Beem Messenger</title>
	<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

<h2>Login</h2>

<?php
if (!empty($message)) {
    echo "<p style='color:red;'>$message</p>";
}
?>

<form method="POST">

    <input
        type="email"
        name="email"
        placeholder="Email"
        required
    ><br><br>

    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    ><br><br>

    <button type="submit" name="login">
        Login
    </button>

</form>
</div>
</body>
</html>