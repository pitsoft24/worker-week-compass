<?php
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id'])) {
    echo '<p>Du bist nicht eingeloggt.</p>';
    exit;
}
$user_id = $_SESSION['user_id'];

// Status speichern
$status_success = null;
if (isset($_POST['status_date'], $_POST['status_select'])) {
    $date = $_POST['status_date'];
    $status = $_POST['status_select'];
    // Prüfen, ob schon ein Status für diesen Tag existiert
    $check = $pdo->prepare('SELECT id FROM tasks WHERE user_id = ? AND due_date = ? AND title IN ("Büro", "mobiles Arbeiten", "nicht Anwesend")');
    $check->execute([$user_id, $date]);
    if ($row = $check->fetch()) {
        // Update
        $update = $pdo->prepare('UPDATE tasks SET title = ? WHERE id = ?');
        $update->execute([$status, $row['id']]);
    } else {
        // Insert
        $insert = $pdo->prepare('INSERT INTO tasks (title, due_date, user_id) VALUES (?, ?, ?)');
        $insert->execute([$status, $date, $user_id]);
    }
    $status_success = 'Status gespeichert!';
}

// Kalender Seite
if (!isset($_SESSION['events'])) {
    $_SESSION['events'] = [];
}
// Termin hinzufügen
if (isset($_POST['event_date'], $_POST['event_text']) && trim($_POST['event_text']) !== '') {
    $date = $_POST['event_date'];
    $text = htmlspecialchars($_POST['event_text']);
    if (!isset($_SESSION['events'][$date])) {
        $_SESSION['events'][$date] = [];
    }
    $_SESSION['events'][$date][] = $text;
}
$monat = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$jahr = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($monat < 1) {
    $monat = 12;
    $jahr--;
}
if ($monat > 12) {
    $monat = 1;
    $jahr++;
}
$tage_im_monat = cal_days_in_month(CAL_GREGORIAN, $monat, $jahr);
$erster_tag = date('N', strtotime("$jahr-$monat-01"));
setlocale(LC_TIME, 'de_DE.UTF-8');
$monatsname = strftime('%B', strtotime("$jahr-$monat-01"));
$tage = ['Mo', 'Di', 'Mi', 'Do', 'Fr']; // Nur Montag bis Freitag
$prev_month = $monat - 1;
$prev_year = $jahr;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $monat + 1;
$next_year = $jahr;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
$heute = ($monat == date('n') && $jahr == date('Y')) ? date('j') : null;

// Status für alle Tage des Monats laden
$status_stmt = $pdo->prepare('SELECT due_date, title FROM tasks WHERE user_id = ? AND due_date BETWEEN ? AND ? AND title IN ("Büro", "mobiles Arbeiten", "nicht Anwesend")');
$start_date = sprintf('%04d-%02d-01', $jahr, $monat);
$end_date = sprintf('%04d-%02d-%02d', $jahr, $monat, $tage_im_monat);
$status_stmt->execute([$user_id, $start_date, $end_date]);
$status_map = [];
foreach ($status_stmt as $row) {
    $status_map[$row['due_date']] = $row['title'];
}

// Für das Inline-Formular: Welcher Tag ist gerade "offen"?
$open_day = isset($_POST['open_day']) ? $_POST['open_day'] : null;

$pageParam = isset($_GET['page']) ? '&page=' . urlencode($_GET['page']) : '&page=calendar';
?>
<h2>Kalender: <?= ucfirst($monatsname) ?> <?= $jahr ?></h2>
<div style="margin-bottom:1em;">
    <a href="?page=calendar&month=<?= $prev_month ?>&year=<?= $prev_year ?>">&laquo; Vorheriger Monat</a>
    |
    <a href="?page=calendar&month=<?= $next_month ?>&year=<?= $next_year ?>">Nächster Monat &raquo;</a>
</div>
<?php if ($status_success): ?>
    <div style="color:green;">Status gespeichert!</div>
<?php endif; ?>
<style>
    .kalender-tabelle td {
        min-width: 110px;
        height: 90px;
        vertical-align: top;
        background: #fff;
        cursor: pointer;
        position: relative;
        border: 2px solid #bdbdbd; /* Gut sichtbarer Rahmen */
        box-sizing: border-box;
    }
    .kalender-tabelle th {
        border: 2px solid #bdbdbd;
        background: #f5f5f5;
        box-sizing: border-box;
    }
    .kalender-tabelle .heute { background: #ffe082 !important; font-weight: bold; }
    .kalender-tabelle ul { margin: 0.3em 0 0 0; padding-left: 1em; font-size: 0.95em; }
    .kalender-tabelle li { color: #1565c0; margin-bottom: 2px; word-break: break-word; }
    .kalender-tabelle .tagzahl { font-size: 1.1em; }
    .status-form { background:#f5f5f5; border:1px solid #bbb; padding:5px; position:absolute; top:20px; left:5px; z-index:10; }
    .status-label { display:inline-block; margin-right:8px; }
    .status-anzeige { font-size:0.95em; margin-top:0.3em; color:#388e3c; }
</style>
<form method="post" id="openDayForm" style="display:none;"><input type="hidden" name="open_day" id="openDayInput"></form>
<table class="kalender-tabelle" border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <?php foreach ($tage as $t): ?><th><?= $t ?></th><?php endforeach; ?>
    </tr>
    <?php
    $tage_zaehler = 0;
    $started = false;
    for ($tag = 1; $tag <= $tage_im_monat; $tag++) {
        $wochentag = date('N', strtotime("$jahr-$monat-$tag"));
        if ($wochentag >= 1 && $wochentag <= 5) {
            if (!$started) {
                echo '<tr>';
                // Leere Zellen, falls der Monat nicht an einem Montag beginnt
                for ($i = 1; $i < $wochentag; $i++) {
                    echo '<td></td>';
                    $tage_zaehler++;
                }
                $started = true;
            }
            $date_str = sprintf('%04d-%02d-%02d', $jahr, $monat, $tag);
            $is_today = ($heute !== null && $tag == $heute);
            echo '<td class="'.($is_today ? 'heute' : '').'" onclick="openStatusForm(\''.$date_str.'\')">';
            echo '<div class="tagzahl">'.$tag.'</div>';
            // Status anzeigen
            if (isset($status_map[$date_str])) {
                echo '<div class="status-anzeige">'.htmlspecialchars($status_map[$date_str]).'</div>';
            }
            // Inline-Formular für diesen Tag
            if ($open_day === $date_str) {
                echo '<form method="post" class="status-form" onClick="event.stopPropagation();">
                    <input type="hidden" name="status_date" value="'.$date_str.'">
                    <label class="status-label"><input type="radio" name="status_select" value="Büro" required> Büro</label>
                    <label class="status-label"><input type="radio" name="status_select" value="mobiles Arbeiten"> Mobiles Arbeiten</label>
                    <label class="status-label"><input type="radio" name="status_select" value="nicht Anwesend"> Nicht Anwesend</label>
                    <button type="submit">Speichern</button>
                </form>';
            }
            echo '</td>';
            $tage_zaehler++;
            if ($tage_zaehler % 5 == 0) {
                echo '</tr>';
                $started = false;
            }
        }
    }
    // Leere Zellen am Ende auffüllen
    if ($tage_zaehler % 5 != 0) {
        for ($i = $tage_zaehler % 5; $i < 5; $i++) {
            echo '<td></td>';
        }
        echo '</tr>';
    }
    ?>
</table>
<script>
function openStatusForm(date) {
    document.getElementById('openDayInput').value = date;
    document.getElementById('openDayForm').submit();
}
</script>
<div style="margin-top:2em;">
    <h3>Termin eintragen</h3>
    <form method="post">
        <label>Datum:
            <input type="date" name="event_date" value="<?= sprintf('%04d-%02d-01', $jahr, $monat) ?>" required>
        </label>
        <label>Termin:
            <input type="text" name="event_text" required>
        </label>
        <button type="submit">Speichern</button>
    </form>
</div>
