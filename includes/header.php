<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$loggedIn = !empty($_SESSION['user_id']);
$username = htmlspecialchars($_SESSION['username'] ?? '');

?>
<header>
    <h1>Rock Music</h1>

    <label for="nav-toggle" class="hamburger" aria-label="Open navigation">☰</label>
    <input type="checkbox" id="nav-toggle" hidden>

    <ul class="nav-menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="graphs.php">Graphs</a></li>
        <li><a href="gallery.php">Gallery</a></li>
        <?php if ($loggedIn): ?>
            <li><a href="playlist.php">My Playlist</a></li>
            <li class="nav-spacer"></li>
            <li class="nav-user">
                <?= $username ?>
                <a href="logout.php" class="nav-logout">Logout</a>
            </li>
        <?php else: ?>
            <li class="nav-spacer"></li>
            <li class="nav-auth"><a href="login.php">Login</a></li>
            <li class="nav-auth"><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</header>