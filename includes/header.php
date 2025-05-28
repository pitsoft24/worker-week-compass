<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Week Compass</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        $stmt = $pdo->prepare('SELECT dark_mode FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && $user['dark_mode']): ?>
            <link rel="stylesheet" href="css/darkmode.css">
        <?php endif; ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav style="display: flex; justify-content: center; align-items: center;">
            <div class="logo" style="margin-right: auto;">Worker Week Compass</div>
            <div style="flex:1; display: flex; justify-content: center;">
                <ul style="display: flex; list-style: none; gap: 1.5rem; margin: 0; padding: 0; align-items: center;">
                    <li><a href="?page=home">Home</a></li>
                    <li><a href="?page=calendar">Kalender</a></li>
                    <li><a href="?page=profile">Profil</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php" class="btn btn-primary">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('.theme-icon');
    const themeText = themeToggle.querySelector('.theme-text');
    
    // Prüfe gespeicherten Theme-Status
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        themeIcon.textContent = '☀️';
        themeText.textContent = 'Light Mode';
    }

    // Theme Toggle Handler
    themeToggle.addEventListener('click', function() {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        // Update Icon und Text
        themeIcon.textContent = isDark ? '☀️' : '🌙';
        themeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    });
});
</script> 