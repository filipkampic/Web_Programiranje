<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: /");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ??  '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Username must be 3–30 characters long.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'The password must be at least 6 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Username or email already exists';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $ins->bind_param('sss', $username, $email, $hash);
            if ($ins->execute()) {
                $success = 'Registration successful! <a href="/login.php">Sign in</a>.';
            } else {
                $errors[] = 'Registration failed. Try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/auth.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main>
        <section class="auth-card">
            <h2>Registration</h2>

            <?php if ($success): ?>
                <p class="auth-success"><?= $success ?></p>
            <?php endif; ?>

            <?php if ($errors): ?>
                <ul class="auth-errors">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="register.php?" novalidate>
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required minlength="3" maxlength="30">

                <label for="email">E-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">

                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required>

                <button type="submit">Register</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a>.</p>
        </section>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>