<?php
/**
 * BPgranny Data Export
 * Downloads all session + trial data as CSV
 */

require(dirname(__FILE__) . "/config.php");

// Simple password gate — set EXPORT_PASSWORD in Railway Variables
$export_password = getenv('EXPORT_PASSWORD') ?: 'changeme';
$provided = $_GET['password'] ?? '';

if (!hash_equals($export_password, $provided)) {
    http_response_code(401);
    echo "Unauthorized. Add ?password=YOUR_PASSWORD to the URL.";
    exit(1);
}

$db = @mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$db) { die("DB connection failed"); }
mysqli_set_charset($db, "utf8mb4");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bpgranny_sessions_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, [
    'session_id','gameid','playername','level','score','mistakes',
    'total_shots_fired','play_time_minutes','play_time_seconds','saved_at',
    'trial_playername','trial_index','stimulus','reaction_time','hit',
    'enemy_number','stage','target_type','click_type'
]);

$result = mysqli_query($db,
    "SELECT s.id, s.gameid, s.playername, s.level, s.score, s.mistakes,
            s.total_shots_fired, s.play_time_minutes, s.play_time_seconds, s.saved_at,
            t.playername AS trial_playername, t.trial_index, t.stimulus, t.reaction_time, t.hit,
            t.enemy_number, t.stage, t.target_type, t.click_type
     FROM game_sessions s
     LEFT JOIN game_trials t ON t.session_id = s.id
     ORDER BY s.id ASC, t.trial_index ASC"
);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, $row);
}

fclose($out);
mysqli_close($db);
exit(0);
?>
