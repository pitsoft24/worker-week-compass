<?php
$page_title = "Startseite";
require_once __DIR__ . '/../config/database.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="order-2 md:order-1">
            <?php
            // Team-Filter
            $team = isset($_GET['team']) && $_GET['team'] === 'PDN' ? 'PDN' : 'BK';
            // Kalenderdaten vorbereiten
            $monat = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
            $jahr = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $tage_im_monat = cal_days_in_month(CAL_GREGORIAN, $monat, $jahr);
            $erster_tag = date('N', strtotime("$jahr-$monat-01"));
            $tage = ['Mo', 'Di', 'Mi', 'Do', 'Fr'];
            // Monatsnavigation
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
            setlocale(LC_TIME, 'de_DE.UTF-8');
            $monatsname = strftime('%B', strtotime("$jahr-$monat-01"));
            // Alle User laden (nur aus gewähltem Team)
            $user_stmt = $pdo->prepare('SELECT id, username FROM users WHERE team = ?');
            $user_stmt->execute([$team]);
            $user_map = [];
            $user_ids = [];
            foreach ($user_stmt as $u) {
                $user_map[$u['id']] = $u['username'];
                $user_ids[] = $u['id'];
            }
            // Status-Einträge für den Monat laden (nur für User im Team)
            $start_date = sprintf('%04d-%02d-01', $jahr, $monat);
            $end_date = sprintf('%04d-%02d-%02d', $jahr, $monat, $tage_im_monat);
            $status_map = [];
            if ($user_ids) {
                $in = implode(',', array_fill(0, count($user_ids), '?'));
                $status_stmt = $pdo->prepare('SELECT user_id, due_date, title FROM tasks WHERE due_date BETWEEN ? AND ? AND user_id IN (' . $in . ') AND title IN ("Büro", "mobiles Arbeiten", "nicht Anwesend")');
                $params = array_merge([$start_date, $end_date], $user_ids);
                $status_stmt->execute($params);
                foreach ($status_stmt as $row) {
                    $status_map[$row['due_date']][] = [
                        'username' => $user_map[$row['user_id']] ?? 'Unbekannt',
                        'status' => $row['title']
                    ];
                }
            }
            ?>
            <style>
                .teamkalender-table {
                    margin-left: 0;
                }
                .teamkalender-table td, .teamkalender-table th {
                    min-width: 205px;
                    height: 90px;
                    vertical-align: top;
                    background: #fff;
                    border: 2px solid #bdbdbd;
                    box-sizing: border-box;
                    transition: background 0.2s;
                }
                .teamkalender-table th {
                    background: #f5f5f5;
                }
                .teamkalender-table .tagzahl { font-size: 1.1em; font-weight: bold; }
                .teamkalender-table ul { margin: 0.3em 0 0 0; padding-left: 1em; font-size: 0.95em; }
                .teamkalender-table li { margin-bottom: 2px; word-break: break-word; }
                .buero-tag { background: #e6f9e6 !important; }
                .kein-buero-tag { background: #ffeaea !important; }
            </style>
            <table class="teamkalender-table" border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <th colspan="5" style="background:#fff; text-align:left; padding: 1em 1em 0.5em 1em; font-size:1.2em; border-bottom:none;">
                        <form method="get" style="display:inline;">
                            <input type="hidden" name="month" value="<?= $monat ?>">
                            <input type="hidden" name="year" value="<?= $jahr ?>">
                            <label for="team_select" style="font-weight:bold;">Team-Kalender:</label>
                            <select name="team" id="team_select" onchange="this.form.submit()" style="font-weight:bold; font-size:1em; margin-left:0.5em;">
                                <option value="BK" <?= $team==='BK'?'selected':'' ?>>BK</option>
                                <option value="PDN" <?= $team==='PDN'?'selected':'' ?>>PDN</option>
                            </select>
                        </form>
                        <div style="margin-top:0.5em; font-size:1em;">
                            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>&team=<?= $team ?>" class="btn btn-secondary">&laquo; Vorheriger Monat</a>
                            |
                            <span style="font-weight:bold; font-size:1.1em; padding:0 10px;"> <?= ucfirst($monatsname) ?> <?= $jahr ?> </span>
                            |
                            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>&team=<?= $team ?>" class="btn btn-secondary">Nächster Monat &raquo;</a>
                        </div>
                    </th>
                </tr>
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
                            for ($i = 1; $i < $wochentag; $i++) {
                                echo '<td></td>';
                                $tage_zaehler++;
                            }
                            $started = true;
                        }
                        $date_str = sprintf('%04d-%02d-%02d', $jahr, $monat, $tag);
                        // Prüfen, ob jemand im Büro ist
                        $has_buero = false;
                        if (isset($status_map[$date_str])) {
                            foreach ($status_map[$date_str] as $entry) {
                                if ($entry['status'] === 'Büro') {
                                    $has_buero = true;
                                    break;
                                }
                            }
                        }
                        $cell_class = $has_buero ? 'buero-tag' : 'kein-buero-tag';
                        echo '<td class="'.$cell_class.'">';
                        echo '<div class="tagzahl">'.$tag.'</div>';
                        if (isset($status_map[$date_str])) {
                            echo '<ul>';
                            foreach ($status_map[$date_str] as $entry) {
                                echo '<li><b>'.htmlspecialchars($entry['username']).'</b>: '.htmlspecialchars($entry['status']).'</li>';
                            }
                            echo '</ul>';
                        }
                        echo '</td>';
                        $tage_zaehler++;
                        if ($tage_zaehler % 5 == 0) {
                            echo '</tr>';
                            $started = false;
                        }
                    }
                }
                if ($tage_zaehler % 5 != 0) {
                    for ($i = $tage_zaehler % 5; $i < 5; $i++) {
                        echo '<td></td>';
                    }
                    echo '</tr>';
                }
                ?>
            </table>
        </div>
    </div>
</div> 