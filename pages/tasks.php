<?php
// Aufgaben Seite
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [];
}
// Aufgabe hinzufügen
if (isset($_POST['new_task']) && trim($_POST['new_task']) !== '') {
    $_SESSION['tasks'][] = htmlspecialchars($_POST['new_task']);
}
// Aufgabe löschen
if (isset($_GET['delete'])) {
    $delete = (int)$_GET['delete'];
    if (isset($_SESSION['tasks'][$delete])) {
        array_splice($_SESSION['tasks'], $delete, 1);
    }
}
?>
<h2>Aufgaben</h2>
<form method="post" style="margin-bottom: 1em;">
    <input type="text" name="new_task" placeholder="Neue Aufgabe" required>
    <button type="submit">Hinzufügen</button>
</form>
<ul>
<?php foreach ($_SESSION['tasks'] as $i => $task): ?>
    <li><?= $task ?> <a href="?delete=<?= $i ?>" onclick="return confirm('Aufgabe löschen?');">Löschen</a></li>
<?php endforeach; ?>
</ul>
<?php if (empty($_SESSION['tasks'])): ?>
<p>Keine Aufgaben vorhanden.</p>
<?php endif; ?>
