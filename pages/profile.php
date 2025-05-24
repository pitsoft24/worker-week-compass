<?php
// Profil Seite
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id'])) {
    echo '<p>Du bist nicht eingeloggt.</p>';
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT username, email, created_at, team FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Passwort ändern
$pw_success = null;
$pw_error = null;
if (isset($_POST['pw1'], $_POST['pw2'])) {
    $pw1 = $_POST['pw1'];
    $pw2 = $_POST['pw2'];
    if (strlen($pw1) < 6) {
        $pw_error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
    } elseif ($pw1 !== $pw2) {
        $pw_error = 'Die Passwörter stimmen nicht überein.';
    } else {
        $hash = password_hash($pw1, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        if ($update->execute([$hash, $user_id])) {
            $pw_success = 'Passwort erfolgreich geändert!';
        } else {
            $pw_error = 'Fehler beim Ändern des Passworts.';
        }
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
?>
<h2>Profil</h2>
<?php if ($user): ?>
    <table>
        <tr><th>Benutzername:</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
        <tr><th>E-Mail:</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><th>Team:</th><td>
            <form method="post" style="display:inline;">
                <select name="team_select" onchange="this.form.submit()">
                    <option value="BK" <?= $user['team']==='BK'?'selected':'' ?>>BK</option>
                    <option value="PDN" <?= $user['team']==='PDN'?'selected':'' ?>>PDN</option>
                </select>
            </form>
            <?php if ($team_success): ?><span style="color:green;"> <?= $team_success ?></span><?php endif; ?>
        </td></tr>
        <tr><th>Registriert seit:</th><td><?= htmlspecialchars($user['created_at']) ?></td></tr>
    </table>
<?php else: ?>
    <p>Benutzerprofil nicht gefunden.</p>
<?php endif; ?>

<hr>
<h3>Passwort ändern</h3>
<?php if ($pw_success): ?>
    <div style="color:green;"> <?= $pw_success ?> </div>
<?php elseif ($pw_error): ?>
    <div style="color:red;"> <?= $pw_error ?> </div>
<?php endif; ?>
<form method="post" autocomplete="off">
    <label>Neues Passwort:<br>
        <input type="password" name="pw1" required minlength="6">
    </label><br>
    <label>Neues Passwort wiederholen:<br>
        <input type="password" name="pw2" required minlength="6">
    </label><br>
    <button type="submit">Passwort ändern</button>
</form>

<?php if ($admin): ?>
<hr>
<h3>Alle Benutzer</h3>
<?php if ($delete_success): ?>
    <div style="color:green;"> <?= htmlspecialchars($delete_success) ?> </div>
<?php endif; ?>
<?php if ($edit_success): ?>
    <div style="color:green;"> <?= htmlspecialchars($edit_success) ?> </div>
<?php elseif ($edit_error): ?>
    <div style="color:red;"> <?= htmlspecialchars($edit_error) ?> </div>
<?php endif; ?>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Benutzername</th>
        <th>E-Mail</th>
        <th>Team</th>
        <th>Registriert seit</th>
        <th>Aktionen</th>
    </tr>
    <?php
    $all = $pdo->query('SELECT id, username, email, team, created_at FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['team']) ?></td>
            <td><?= htmlspecialchars($u['created_at']) ?></td>
            <td>
                <?php if ($u['id'] !== $user_id): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                    <button type="submit" onclick="return confirm('Benutzer wirklich löschen?');">Löschen</button>
                </form>
                <?php endif; ?>
                <form method="get" action="index.php" style="display:inline;">
                    <input type="hidden" name="page" value="profile">
                    <input type="hidden" name="edit_user_id" value="<?= $u['id'] ?>">
                    <button type="submit">Bearbeiten</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if ($edit_user): ?>
<hr>
<h3>Benutzer bearbeiten (ID <?= $edit_user['id'] ?>)</h3>
<form method="post">
    <input type="hidden" name="edit_user_id_save" value="<?= $edit_user['id'] ?>">
    <label>Benutzername:<br>
        <input type="text" name="edit_username" value="<?= htmlspecialchars($edit_user['username']) ?>" required minlength="3">
    </label><br>
    <label>E-Mail:<br>
        <input type="email" name="edit_email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
    </label><br>
    <label>Team:<br>
        <select name="edit_team">
            <option value="BK" <?= $edit_user['team']==='BK'?'selected':'' ?>>BK</option>
            <option value="PDN" <?= $edit_user['team']==='PDN'?'selected':'' ?>>PDN</option>
        </select>
    </label><br>
    <button type="submit">Speichern</button>
</form>
<?php endif; ?>
<?php endif; ?>
