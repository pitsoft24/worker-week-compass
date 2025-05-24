<?php
require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (strlen($username) < 3) {
        $errors[] = 'Benutzername muss mindestens 3 Zeichen lang sein';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwörter stimmen nicht überein';
    }
    
    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Benutzername oder E-Mail-Adresse bereits vergeben';
            } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                $_SESSION['user_id'] = $pdo->lastInsertId();
                header('Location: ../index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Datenbankfehler';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrieren - Worker Week Compass</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-center mb-6">Registrieren</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Benutzername</label>
                    <input type="text" id="username" name="username" required
                           class="form-input mt-1">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-Mail-Adresse</label>
                    <input type="email" id="email" name="email" required
                           class="form-input mt-1">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Passwort</label>
                    <input type="password" id="password" name="password" required
                           class="form-input mt-1">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Passwort bestätigen</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="form-input mt-1">
                </div>
                
                <button type="submit" class="btn-primary w-full">
                    Registrieren
                </button>
            </form>
            
            <p class="mt-4 text-center text-sm text-gray-600">
                Bereits ein Konto? <a href="login.php" class="text-blue-500 hover:text-blue-700">Anmelden</a>
            </p>
        </div>
    </div>
</body>
</html> 