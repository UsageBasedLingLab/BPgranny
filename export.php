<?php
/**
 * BPgranny Data Export
 * Downloads session and trial data as CSV
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
$sessions_only = ($_GET['table'] ?? '') === 'game_sessions';
$filename = $sessions_only ? 'bpgranny_game_sessions_' : 'bpgranny_sessions_';
header('Content-Disposition: attachment; filename="' . $filename . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');

if ($sessions_only) {
    fputcsv($out, [
        'session_id','gameid','playername','level','stage','score','mistakes',
        'total_shots_fired','play_time_minutes','play_time_seconds','saved_at'
    ]);
    $result = mysqli_query($db,
        "SELECT id, gameid, playername, level, stage, score, mistakes,
                total_shots_fired, play_time_minutes, play_time_seconds, saved_at
         FROM game_sessions
         ORDER BY id ASC"
    );
} else {
    fputcsv($out, [
        'session_id','gameid','playername','level','score','mistakes',
        'total_shots_fired','play_time_minutes','play_time_seconds','saved_at',
        'trial_playername','trial_index','stimulus','reaction_time','hit',
        'enemy_number','stage','target_type','audio_selection'
    ]);
    $result = mysqli_query($db,
        "SELECT s.id, s.gameid, s.playername, s.level, s.score, s.mistakes,
                s.total_shots_fired, s.play_time_minutes, s.play_time_seconds, s.saved_at,
                t.playername AS trial_playername, t.trial_index, t.stimulus, t.reaction_time, t.hit,
                t.enemy_number, t.stage, t.target_type, t.audio_selection
         FROM game_sessions s
         LEFT JOIN game_trials t ON t.session_id = s.id
         ORDER BY s.id ASC, t.trial_index ASC"
    );
}

if (!$result) {
    http_response_code(500);
    fclose($out);
    mysqli_close($db);
    echo "Export query failed";
    exit(1);
}

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, $row);
}

fclose($out);
mysqli_close($db);
exit(0);
?>
