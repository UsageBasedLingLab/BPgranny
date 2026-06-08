<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// ← ADD DEBUG BLOCK HERE, before anything else
if (isset($_GET['debug'])) {
    echo json_encode([
        'GET'    => $_GET,
        'POST'   => $_POST,
        'method' => $_SERVER['REQUEST_METHOD'],
        'raw'    => substr(file_get_contents('php://input'), 0, 500)
    ]);
    exit(0);
}

require(dirname(__FILE__) . "/config.php");

/**
 * BPgranny Session Save API
 * Stores full game session + per-trial data to the database
 */

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require(dirname(__FILE__) . "/config.php");

function get_db($host, $user, $pass, $dbname, $port) {
    $db = @mysqli_connect($host, $user, $pass, $dbname, $port);
    if (!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit(1);
    }
    mysqli_set_charset($db, "utf8mb4");
    return $db;
}

function init_tables($db) {
    mysqli_query($db, "CREATE TABLE IF NOT EXISTS `game_sessions` (
        `id`                    INT AUTO_INCREMENT PRIMARY KEY,
        `gameid`                VARCHAR(255) NOT NULL,
        `playername`            VARCHAR(255) NOT NULL,
        `level`                 INT NOT NULL DEFAULT 0,
        `score`                 INT NOT NULL DEFAULT 0,
        `mistakes`              INT NOT NULL DEFAULT 0,
        `total_shots_fired`     INT NOT NULL DEFAULT 0,
        `play_time_minutes`     INT NOT NULL DEFAULT 0,
        `play_time_seconds`     INT NOT NULL DEFAULT 0,
        `saved_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (`gameid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($db, "CREATE TABLE IF NOT EXISTS `game_trials` (
        `id`                    INT AUTO_INCREMENT PRIMARY KEY,
        `session_id`            INT NOT NULL,
        `trial_index`           INT NOT NULL,
        `stimulus`              VARCHAR(255) NOT NULL DEFAULT '',
        `reaction_time`         FLOAT NOT NULL DEFAULT 0,
        `hit`                   TINYINT(1) NOT NULL DEFAULT 0,
        INDEX (`session_id`),
        FOREIGN KEY (`session_id`) REFERENCES `game_sessions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Accept parameters from GET, POST, or JSON body
$input = array_merge($_GET, $_POST);
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
}

// Read input — support both form POST and JSON body
$input = [];
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $input = json_decode($raw, true) ?? [];
}
if (empty($input)) {
    $input = $_POST;
}

// Required fields
$required = ['gameid', 'playername', 'score', 'code'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit(1);
    }
}

$gameid     = (int)$input['gameid'];
$playername = substr(trim($input['playername']), 0, 255);
$score      = (int)$input['score'];
$level      = (int)($input['level'] ?? 0);
$mistakes   = (int)($input['mistakes'] ?? 0);
$shots      = (int)($input['total_shots_fired'] ?? 0);
$minutes    = (int)($input['play_time_minutes'] ?? 0);
$seconds    = (int)($input['play_time_seconds'] ?? 0);
$code       = trim($input['code']);

// Verify security hash
$expected = md5($gameid . $playername . $score . $secret_key);
if (!hash_equals($expected, $code)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security code']);
    exit(1);
}

// Parse trials — expect JSON array: [{"stimulus":"...","reaction_time":0.1,"hit":1}, ...]
// Accept either JSON trials array OR individual t0_s / t0_r / t0_h params
$trials = [];
if (!empty($input['trials'])) {
    $decoded = is_array($input['trials']) ? $input['trials'] : json_decode($input['trials'], true);
    if (is_array($decoded)) {
        $trials = $decoded;
    }
} else {
    // Build trials from individual URL params: t0_s, t0_r, t0_h, t1_s, t1_r, t1_h ...
    $i = 0;
    while (isset($input["t{$i}_s"]) || isset($input["t{$i}_r"]) || isset($input["t{$i}_h"])) {
        $trials[] = [
            'stimulus'      => $input["t{$i}_s"] ?? '',
            'reaction_time' => $input["t{$i}_r"] ?? 0,
            'hit'           => $input["t{$i}_h"] ?? 0
        ];
        $i++;
    }
}

$db = get_db($host, $user, $pass, $dbname, $port);
init_tables($db);

// Insert session
$stmt = mysqli_prepare($db,
    "INSERT INTO game_sessions (gameid, playername, level, score, mistakes, total_shots_fired, play_time_minutes, play_time_seconds)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'isiiiii i', $gameid, $playername, $level, $score, $mistakes, $shots, $minutes, $seconds);
// fix bind — 8 params
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($db,
    "INSERT INTO game_sessions (gameid, playername, level, score, mistakes, total_shots_fired, play_time_minutes, play_time_seconds)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'isiiiiiii', $gameid, $playername, $level, $score, $mistakes, $shots, $minutes, $seconds);

// fix: 8 ints/strings
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($db,
    "INSERT INTO game_sessions (gameid, playername, level, score, mistakes, total_shots_fired, play_time_minutes, play_time_seconds)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'isiiiiii', $gameid, $playername, $level, $score, $mistakes, $shots, $minutes, $seconds);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save session']);
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    exit(1);
}

$session_id = mysqli_insert_id($db);
mysqli_stmt_close($stmt);

// Insert trials
if (!empty($trials)) {
    $tstmt = mysqli_prepare($db,
        "INSERT INTO game_trials (session_id, trial_index, stimulus, reaction_time, hit) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($trials as $i => $trial) {
        $stimulus = substr(trim($trial['stimulus'] ?? ''), 0, 255);
        $rt       = (float)($trial['reaction_time'] ?? 0);
        $hit      = (int)($trial['hit'] ?? 0);
        mysqli_stmt_bind_param($tstmt, 'iisdi', $session_id, $i, $stimulus, $rt, $hit);
        mysqli_stmt_execute($tstmt);
    }
    mysqli_stmt_close($tstmt);
}

mysqli_close($db);

echo json_encode([
    'status'     => 'saved',
    'session_id' => $session_id,
    'trials'     => count($trials)
]);
exit(0);
?>
