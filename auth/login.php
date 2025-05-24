<?php
require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Ungültige Anmeldedaten';
        }
    } catch (PDOException $e) {
        $error = 'Datenbankfehler';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmelden - Worker Week Compass</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-center mb-6">Anmelden</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Benutzername</label>
                    <input type="text" id="username" name="username" required
                           class="form-input mt-1">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Passwort</label>
                    <input type="password" id="password" name="password" required
                           class="form-input mt-1">
                </div>
                
                <button type="submit" class="btn-primary w-full">
                    Anmelden
                </button>
            </form>
            
            <p class="mt-4 text-center text-sm text-gray-600">
                Noch kein Konto? <a href="register.php" class="text-blue-500 hover:text-blue-700">Registrieren</a>
            </p>
        </div>
    </div>
</body>
</html> 