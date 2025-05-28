<?php
// Profil Seite
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Benutzerdaten laden
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Dark Mode Update
if (isset($_POST['dark_mode_select'])) {
    $dark_mode = $_POST['dark_mode_select'] == '1' ? 1 : 0;
    $update = $pdo->prepare('UPDATE users SET dark_mode = ? WHERE id = ?');
    $update->execute([$dark_mode, $user_id]);
    $message = 'Dark Mode Einstellung gespeichert!';
    $user['dark_mode'] = $dark_mode;
}

// Passwort ändern
if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([$hashed_password, $user_id]);
            $message = 'Passwort erfolgreich geändert!';
        } else {
            $message = 'Die neuen Passwörter stimmen nicht überein.';
        }
    } else {
        $message = 'Das aktuelle Passwort ist falsch.';
    }
}

// Team ändern (User)
$team_success = null;
if (isset($_POST['team_select'])) {
    $new_team = $_POST['team_select'] === 'PDN' ? 'PDN' : 'BK';
    $upd = $pdo->prepare('UPDATE users SET team = ? WHERE id = ?');
    if ($upd->execute([$new_team, $user_id])) {
        $team_success = 'Team erfolgreich geändert!';
        $user['team'] = $new_team;
    }
}

// Benutzer löschen (nur für admin, nicht sich selbst)
$admin = ($user && $user['username'] === 'admin');
$delete_success = null;
if ($admin && isset($_POST['delete_user_id'])) {
    $del_id = (int)$_POST['delete_user_id'];
    if ($del_id !== $user_id) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$del_id]);
        $delete_success = 'Benutzer gelöscht.';
    } else {
        $delete_success = 'Du kannst dich nicht selbst löschen!';
    }
}

// Benutzer bearbeiten (nur für admin)
$edit_success = null;
$edit_error = null;
if ($admin && isset($_POST['edit_user_id_save'])) {
    $edit_id = (int)$_POST['edit_user_id_save'];
    $edit_username = trim($_POST['edit_username']);
    $edit_email = trim($_POST['edit_email']);
    $edit_team = ($_POST['edit_team'] === 'PDN') ? 'PDN' : 'BK';
    if (strlen($edit_username) < 3) {
        $edit_error = 'Benutzername muss mindestens 3 Zeichen lang sein.';
    } elseif (!filter_var($edit_email, FILTER_VALIDATE_EMAIL)) {
        $edit_error = 'Ungültige E-Mail-Adresse.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
        $check->execute([$edit_username, $edit_email, $edit_id]);
        if ($check->fetch()) {
            $edit_error = 'Benutzername oder E-Mail bereits vergeben.';
        } else {
            $upd = $pdo->prepare('UPDATE users SET username = ?, email = ?, team = ? WHERE id = ?');
            if ($upd->execute([$edit_username, $edit_email, $edit_team, $edit_id])) {
                $edit_success = 'Benutzerdaten erfolgreich geändert!';
            } else {
                $edit_error = 'Fehler beim Speichern.';
            }
        }
    }
}

// Zu bearbeitenden User laden, falls edit_user_id gesetzt ist
$edit_user = null;
if ($admin && isset($_GET['edit_user_id'])) {
    $edit_id = (int)$_GET['edit_user_id'];
    $stmt = $pdo->prepare('SELECT id, username, email, team FROM users WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Admin: Alle Benutzer laden
$all_users = [];
if ($user['username'] === 'admin') {
    $stmt = $pdo->query('SELECT id, username, email, team, dark_mode FROM users');
    $all_users = $stmt->fetchAll();
}
?>

<div class="container">
    <h2 class="text-center mb-4">Profil</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Benutzerinformationen</h3>
        <p><strong>Benutzername:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>E-Mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <?php if ($user['team']): ?>
            <p><strong>Team:</strong> <?= htmlspecialchars($user['team']) ?></p>
        <?php endif; ?>
    </div>

    <div class="card mt-4">
        <h3>Dark Mode Einstellung</h3>
        <form method="post" class="form-group">
            <div class="form-group">
                <label for="dark_mode_select">Dark Mode:</label>
                <select name="dark_mode_select" id="dark_mode_select" class="form-control" onchange="this.form.submit()">
                    <option value="1" <?= $user['dark_mode'] ? 'selected' : '' ?>>Aktiviert</option>
                    <option value="0" <?= !$user['dark_mode'] ? 'selected' : '' ?>>Deaktiviert</option>
                </select>
            </div>
        </form>
    </div>

    <div class="card mt-4">
        <h3>Passwort ändern</h3>
        <form method="post" class="form-group">
            <div class="form-group">
                <label>Aktuelles Passwort:
                    <input type="password" name="current_password" class="form-control" required>
                </label>
            </div>
            <div class="form-group">
                <label>Neues Passwort:
                    <input type="password" name="new_password" class="form-control" required>
                </label>
            </div>
            <div class="form-group">
                <label>Neues Passwort bestätigen:
                    <input type="password" name="confirm_password" class="form-control" required>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Passwort ändern</button>
        </form>
    </div>

    <?php if ($admin): ?>
        <div class="card mt-4">
            <h3>Benutzerverwaltung</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Benutzername</th>
                        <th>E-Mail</th>
                        <th>Team</th>
                        <th>Dark Mode</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['team'] ?? '') ?></td>
                            <td><?= $u['dark_mode'] ? 'Aktiviert' : 'Deaktiviert' ?></td>
                            <td>
                                <a href="?page=profile&edit=<?= $u['id'] ?>" class="btn btn-secondary">Bearbeiten</a>
                                <?php if ($u['id'] !== $user_id): ?>
                                    <a href="?page=profile&delete=<?= $u['id'] ?>" class="btn btn-danger" 
                                       onclick="return confirm('Wirklich löschen?')">Löschen</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
