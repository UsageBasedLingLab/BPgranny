<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require(dirname(__FILE__) . "/config.php");

if ($debug_save_session) {
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Fatal PHP error',
                'details' => $error['message'],
                'file' => basename($error['file']),
                'line' => $error['line']
            ]);
        }
    });
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

// Required fields
foreach (['gameid', 'playername', 'score', 'code'] as $field) {
    if (empty($input[$field]) && $input[$field] !== '0') {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit(1);
    }
}

$gameid     = (int)$input['gameid'];
$playername = trim($input['playername']);
$score      = (int)$input['score'];
$level      = (int)($input['level'] ?? 0);
$stage      = (int)($input['stage'] ?? 0);
$mistakes   = (int)($input['mistakes'] ?? 0);
$shots      = (int)($input['shots'] ?? $input['total_shots_fired'] ?? 0);
$minutes    = (int)($input['minutes'] ?? $input['play_time_minutes'] ?? 0);
$seconds    = (int)($input['seconds'] ?? $input['play_time_seconds'] ?? 0);
$code       = trim($input['code']);

// Debug mode is controlled by the server, never by a client query parameter.
if ($debug_save_session && isset($input['debug'])) {
    $expected_debug = md5($gameid . $playername . $score . $secret_key);
    echo json_encode([
        'GET'           => $_GET,
        'POST'          => $_POST,
        'received_code' => $code,
        'expected_code' => $expected_debug,
        'hash_matches' => hash_equals($expected_debug, $code)
    ]);
    exit;
}

// Validate Prolific ID format
if (!preg_match('/^[a-zA-Z0-9]{24}$/', $playername)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Prolific ID: must be exactly 24 alphanumeric characters']);
    exit(1);
}

// Verify security hash
$expected = md5($gameid . $playername . $score . $secret_key);
if (!hash_equals($expected, $code)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security code']);
    exit(1);
}

// Parse trials — supports Clickteam's "stimulus~reaction_time~hit~enemy_number~stage|..." format,
// plus legacy JSON 'trials' array and t{i}_s/t{i}_r/t{i}_h fallback.
$trials = [];

if (!empty($input['trialdata'])) {
    $raw_td = rtrim(trim($input['trialdata']), '|');
    if ($raw_td !== '') {
        $rows = explode('|', $raw_td);
        foreach ($rows as $i => $row) {
            $parts = explode('~', $row);
            $trials[$i] = [
                'stimulus'      => $parts[0] ?? '',
                'reaction_time' => $parts[1] ?? 0,
                'hit'           => $parts[2] ?? 0,
                'enemy_number'  => $parts[3] ?? 0,
                'stage'         => $parts[4] ?? 0,
                'target_type'   => $parts[5] ?? 0,
                'click_type'    => $parts[6] ?? 0,
            ];
        }
    }
} elseif (!empty($input['trials'])) {
    $decoded = is_array($input['trials']) ? $input['trials'] : json_decode($input['trials'], true);
    if (is_array($decoded)) {
        $trials = $decoded;
    }
} else {
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

// Connect to DB
$db = @mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit(1);
}
mysqli_set_charset($db, "utf8mb4");

// Create tables if needed
mysqli_query($db, "CREATE TABLE IF NOT EXISTS `game_sessions` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `gameid`              VARCHAR(255) NOT NULL,
    `playername`          VARCHAR(255) NOT NULL,
    `level`               INT NOT NULL DEFAULT 0,
    `stage`               INT NOT NULL DEFAULT 0,
    `score`               INT NOT NULL DEFAULT 0,
    `mistakes`            INT NOT NULL DEFAULT 0,
    `total_shots_fired`   INT NOT NULL DEFAULT 0,
    `play_time_minutes`   INT NOT NULL DEFAULT 0,
    `play_time_seconds`   INT NOT NULL DEFAULT 0,
    `saved_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (`gameid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($db, "CREATE TABLE IF NOT EXISTS `game_trials` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `session_id`      INT NOT NULL,
    `trial_index`     INT NOT NULL,
    `stimulus`        VARCHAR(255) NOT NULL DEFAULT '',
    `reaction_time`   FLOAT NOT NULL DEFAULT 0,
    `hit`             TINYINT(1) NOT NULL DEFAULT 0,
    `enemy_number`    INT NOT NULL DEFAULT 0,
    `stage`           INT NOT NULL DEFAULT 0,
    `target_type`     TINYINT(1) NOT NULL DEFAULT 0,
    `click_type`      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX (`session_id`),
    FOREIGN KEY (`session_id`) REFERENCES `game_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// If the session table already existed from an older version, add new columns.
$session_columns = [
    'level' => 'INT NOT NULL DEFAULT 0',
    'stage' => 'INT NOT NULL DEFAULT 0',
    'mistakes' => 'INT NOT NULL DEFAULT 0',
    'total_shots_fired' => 'INT NOT NULL DEFAULT 0',
    'play_time_minutes' => 'INT NOT NULL DEFAULT 0',
    'play_time_seconds' => 'INT NOT NULL DEFAULT 0',
];
foreach ($session_columns as $column => $definition) {
    $column_check = mysqli_query($db, "SHOW COLUMNS FROM `game_sessions` LIKE '" . mysqli_real_escape_string($db, $column) . "'");
    if ($column_check && mysqli_num_rows($column_check) === 0) {
        mysqli_query($db, "ALTER TABLE `game_sessions` ADD COLUMN `$column` $definition");
    }
}

// If the table already existed from before (without these columns), add them if missing.
$col_check = mysqli_query($db, "SHOW COLUMNS FROM `game_trials` LIKE 'enemy_number'");
if ($col_check && mysqli_num_rows($col_check) === 0) {
    mysqli_query($db, "ALTER TABLE `game_trials` ADD COLUMN `enemy_number` INT NOT NULL DEFAULT 0");
}
$col_check2 = mysqli_query($db, "SHOW COLUMNS FROM `game_trials` LIKE 'stage'");
if ($col_check2 && mysqli_num_rows($col_check2) === 0) {
    mysqli_query($db, "ALTER TABLE `game_trials` ADD COLUMN `stage` INT NOT NULL DEFAULT 0");
}
$col_check3 = mysqli_query($db, "SHOW COLUMNS FROM `game_trials` LIKE 'target_type'");
if ($col_check3 && mysqli_num_rows($col_check3) === 0) {
    mysqli_query($db, "ALTER TABLE `game_trials` ADD COLUMN `target_type` TINYINT(1) NOT NULL DEFAULT 0");
}
$col_check4 = mysqli_query($db, "SHOW COLUMNS FROM `game_trials` LIKE 'click_type'");
if ($col_check4 && mysqli_num_rows($col_check4) === 0) {
    mysqli_query($db, "ALTER TABLE `game_trials` ADD COLUMN `click_type` TINYINT(1) NOT NULL DEFAULT 0");
}

// Insert session
$stmt = mysqli_prepare($db,
    "INSERT INTO game_sessions (gameid, playername, level, stage, score, mistakes, total_shots_fired, play_time_minutes, play_time_seconds)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log('Session prepare failed: ' . mysqli_error($db));
    http_response_code(500);
    $response = ['error' => 'Failed to prepare session save'];
    if ($debug_save_session) {
        $response['details'] = mysqli_error($db);
    }
    echo json_encode($response);
    mysqli_close($db);
    exit(1);
}
mysqli_stmt_bind_param($stmt, 'isiiiiiii', $gameid, $playername, $level, $stage, $score, $mistakes, $shots, $minutes, $seconds);

if (!mysqli_stmt_execute($stmt)) {
    $db_error = mysqli_stmt_error($stmt);
    error_log('Session insert failed: ' . $db_error);
    http_response_code(500);
    $response = ['error' => 'Failed to save session'];
    if ($debug_save_session) {
        $response['details'] = $db_error;
    }
    echo json_encode($response);
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    exit(1);
}

$session_id = mysqli_insert_id($db);
mysqli_stmt_close($stmt);

// Insert trials
if (!empty($trials)) {
    $tstmt = mysqli_prepare($db,
        "INSERT INTO game_trials (session_id, trial_index, stimulus, reaction_time, hit, enemy_number, stage, target_type, click_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$tstmt) {
        error_log('Trial prepare failed: ' . mysqli_error($db));
        http_response_code(500);
        $response = ['error' => 'Failed to prepare trial save'];
        if ($debug_save_session) {
            $response['details'] = mysqli_error($db);
        }
        echo json_encode($response);
        mysqli_close($db);
        exit(1);
    }
    foreach ($trials as $i => $trial) {
        $stimulus = substr(trim($trial['stimulus'] ?? ''), 0, 255);
        $rt       = (float)($trial['reaction_time'] ?? 0);
        $hit      = (int)($trial['hit'] ?? 0);
        $enemy    = (int)($trial['enemy_number'] ?? 0);
        $stg      = (int)($trial['stage'] ?? 0);
        $target   = (int)($trial['target_type'] ?? 0);
        $click    = (int)($trial['click_type'] ?? 0);
        mysqli_stmt_bind_param($tstmt, 'iisdiiiii', $session_id, $i, $stimulus, $rt, $hit, $enemy, $stg, $target, $click);
        if (!mysqli_stmt_execute($tstmt)) {
            $db_error = mysqli_stmt_error($tstmt);
            error_log("Trial insert failed at index {$i}: " . $db_error);
            http_response_code(500);
            $response = ['error' => 'Failed to save trial'];
            if ($debug_save_session) {
                $response['details'] = "Index {$i}: {$db_error}";
            }
            echo json_encode($response);
            mysqli_stmt_close($tstmt);
            mysqli_close($db);
            exit(1);
        }
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
