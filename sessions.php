<?php
/**
 * BPgranny Participant Sessions Lookup
 * Returns all sessions + trials for a given Prolific ID
 * Access: /sessions.php?prolific_id=XXX&password=YOUR_EXPORT_PASSWORD
 */

header('Content-Type: application/json; charset=utf-8');

require(dirname(__FILE__) . "/config.php");

// Reuse the same export password to protect participant data
$export_password = getenv('EXPORT_PASSWORD') ?: 'changeme';
$provided = $_GET['password'] ?? '';

if (!hash_equals($export_password, $provided)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Add &password=YOUR_PASSWORD to the URL.']);
    exit(1);
}

$prolific_id = trim($_GET['prolific_id'] ?? '');

if (!preg_match('/^[a-zA-Z0-9]{24}$/', $prolific_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing prolific_id']);
    exit(1);
}

$db = @mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit(1);
}
mysqli_set_charset($db, "utf8mb4");

// Get all sessions for this participant
$stmt = mysqli_prepare($db,
    "SELECT id, gameid, level, score, mistakes, total_shots_fired,
            play_time_minutes, play_time_seconds, saved_at
     FROM game_sessions
     WHERE playername = ?
     ORDER BY saved_at ASC"
);
mysqli_stmt_bind_param($stmt, 's', $prolific_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sessions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sessions[] = $row;
}
mysqli_stmt_close($stmt);

if (empty($sessions)) {
    echo json_encode(['prolific_id' => $prolific_id, 'session_count' => 0, 'sessions' => []]);
    mysqli_close($db);
    exit(0);
}

// Fetch trials for each session
$trial_stmt = mysqli_prepare($db,
    "SELECT trial_index, stimulus, reaction_time, hit
     FROM game_trials
     WHERE session_id = ?
     ORDER BY trial_index ASC"
);

foreach ($sessions as &$session) {
    mysqli_stmt_bind_param($trial_stmt, 'i', $session['id']);
    mysqli_stmt_execute($trial_stmt);
    $trial_result = mysqli_stmt_get_result($trial_stmt);
    $session['trials'] = [];
    while ($trial = mysqli_fetch_assoc($trial_result)) {
        $session['trials'][] = $trial;
    }
}
unset($session);
mysqli_stmt_close($trial_stmt);
mysqli_close($db);

echo json_encode([
    'prolific_id'   => $prolific_id,
    'session_count' => count($sessions),
    'sessions'      => $sessions
], JSON_PRETTY_PRINT);
exit(0);
?>
