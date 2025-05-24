<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Week Compass</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link id="darkmode-css" rel="stylesheet" href="css/darkmode.css" disabled>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php
    require_once 'config/database.php';
    require_once 'includes/header.php';
    ?>

    <main class="container mx-auto px-4 py-8">
        <?php
        $page = isset($_GET['page']) ? $_GET['page'] : 'home';
        $allowed_pages = ['home', 'tasks', 'calendar', 'profile'];
        
        if (in_array($page, $allowed_pages)) {
            include "pages/{$page}.php";
        } else {
            include "pages/404.php";
        }
        ?>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html> 