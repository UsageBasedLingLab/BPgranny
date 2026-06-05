<?php
/**
 * BPgranny Score API
 * Supports both JSON (new) and pipe-delimited (legacy Clickteam Fusion) formats
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(dirname(__FILE__) . "/config.php");

// Determine response format (default to pipe-delimited for Clickteam Fusion compatibility)
$format = isset($_GET["format"]) ? strtolower($_GET["format"]) : "pipe";
if ($format === "json") {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: text/plain; charset=utf-8');
}

/**
 * Create database connection with error handling
 */
function get_db_connection($host, $user, $pass, $dbname, $port) {
    $db = @mysqli_connect($host, $user, $pass, $dbname, $port);
    if (!$db) {
        http_response_code(500);
        echo "ERROR: Connection failed - " . mysqli_connect_error();
        exit(1);
    }
    mysqli_set_charset($db, "utf8mb4");
    return $db;
}

/**
 * Create scores table if it doesn't exist
 */
function initialize_table($db, $tname) {
    $tname_escaped = mysqli_real_escape_string($db, $tname);
    $check_table = mysqli_query($db, "SHOW TABLES LIKE '$tname_escaped'");
    
    if (!$check_table || mysqli_num_rows($check_table) === 0) {
        $create_query = "CREATE TABLE IF NOT EXISTS `$tname_escaped` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `gameid` VARCHAR(255) NOT NULL,
            `playername` VARCHAR(255) NOT NULL,
            `score` INT NOT NULL,
            `scoredate` VARCHAR(255) NOT NULL,
            `md5` VARCHAR(32) NOT NULL,
            INDEX `gameid_idx` (`gameid`),
            INDEX `score_idx` (`score`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($db, $create_query)) {
            http_response_code(500);
            echo "ERROR: Table creation failed";
            exit(1);
        }
    }
}

// Initialize database connection
$db = get_db_connection($host, $user, $pass, $dbname, $port);
initialize_table($db, $tname);

// Handle status check endpoint
if (isset($_GET["status"])) {
    if ($format === "json") {
        echo json_encode(['status' => 'online']);
    } else {
        echo "online";
    }
    mysqli_close($db);
    exit(0);
}

// Validate and sanitize gameid
if (!isset($_GET["gameid"])) {
    http_response_code(400);
    if ($format === "json") {
        echo json_encode(['error' => 'Missing required parameter: gameid']);
    } else {
        echo "ERROR: Missing gameid";
    }
    mysqli_close($db);
    exit(1);
}

$gameid = trim($_GET["gameid"]);
if (!is_numeric($gameid) || (int)$gameid <= 0) {
    http_response_code(400);
    if ($format === "json") {
        echo json_encode(['error' => 'Invalid gameid: must be a positive integer']);
    } else {
        echo "ERROR: Invalid gameid";
    }
    mysqli_close($db);
    exit(1);
}

$gameid = (int)$gameid;

/**
 * Handle score submission (GET with all required params)
 */
if (isset($_GET["playername"]) && isset($_GET["score"]) && isset($_GET["code"])) {
    // Validate playername
    $playername = isset($_GET["playername"]) ? trim($_GET["playername"]) : '';
    if (empty($playername)) {
        http_response_code(400);
        if ($format === "json") {
            echo json_encode(['error' => 'Invalid playername: cannot be empty']);
        } else {
            echo "ERROR: Invalid playername";
        }
        mysqli_close($db);
        exit(1);
    }
    
    if (strlen($playername) > 255) {
        $playername = substr($playername, 0, 255);
    }
    
    // Validate score
    $score = isset($_GET["score"]) ? trim($_GET["score"]) : '';
    if (!is_numeric($score) || (int)$score < 0) {
        http_response_code(400);
        if ($format === "json") {
            echo json_encode(['error' => 'Invalid score: must be a non-negative integer']);
        } else {
            echo "ERROR: Invalid score";
        }
        mysqli_close($db);
        exit(1);
    }
    
    $score = (int)$score;
    $code = isset($_GET["code"]) ? trim($_GET["code"]) : '';
    
    // Verify MD5 security hash
    $expected_hash = md5($gameid . $playername . $score . $secret_key);
    if (!hash_equals($expected_hash, $code)) {
        http_response_code(403);
        if ($format === "json") {
            echo json_encode(['error' => 'Security validation failed: invalid code']);
        } else {
            echo "ERROR: Invalid security code";
        }
        mysqli_close($db);
        exit(1);
    }
    
    // Clean playername - remove pipe character (important for pipe-delimited format)
    $playername = str_replace("|", "_", $playername);
    
    // Use prepared statement for secure insertion
    $tname_escaped = mysqli_real_escape_string($db, $tname);
    $insert_query = "INSERT INTO `$tname_escaped` (gameid, playername, score, scoredate, md5) 
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($db, $insert_query);
    if (!$stmt) {
        http_response_code(500);
        if ($format === "json") {
            echo json_encode(['error' => 'Prepared statement creation failed']);
        } else {
            echo "ERROR: Database error";
        }
        mysqli_close($db);
        exit(1);
    }
    
    $date = date('M d Y');
    mysqli_stmt_bind_param($stmt, 'isiss', $gameid, $playername, $score, $date, $expected_hash);
    
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        if ($format === "json") {
            echo json_encode(['error' => 'Score insertion failed']);
        } else {
            echo "ERROR: Failed to save score";
        }
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        exit(1);
    }
    
    mysqli_stmt_close($stmt);
}

/**
 * Retrieve top scores for the gameid
 */
$tname_escaped = mysqli_real_escape_string($db, $tname);
$select_query = "SELECT playername, score, scoredate FROM `$tname_escaped` 
                 WHERE gameid = ? 
                 ORDER BY score DESC 
                 LIMIT ?";

$stmt = mysqli_prepare($db, $select_query);
if (!$stmt) {
    http_response_code(500);
    if ($format === "json") {
        echo json_encode(['error' => 'Prepared statement creation failed']);
    } else {
        echo "ERROR: Database error";
    }
    mysqli_close($db);
    exit(1);
}

$limit = (int)$score_number;
mysqli_stmt_bind_param($stmt, 'ii', $gameid, $limit);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    if ($format === "json") {
        echo json_encode(['error' => 'Query execution failed']);
    } else {
        echo "ERROR: Query failed";
    }
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    exit(1);
}

$result = mysqli_stmt_get_result($stmt);
$scores = [];

while ($row = mysqli_fetch_assoc($result)) {
    $scores[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($db);

// Return response in requested format
if ($format === "json") {
    // JSON format (for modern clients)
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'gameid' => $gameid,
        'count' => count($scores),
        'scores' => $scores
    ]);
} else {
    // Pipe-delimited format (for Clickteam Fusion compatibility)
    http_response_code(200);
    foreach ($scores as $row) {
        echo $row['playername'] . "|" . $row['score'] . "|" . $row['scoredate'] . "|";
    }
}

exit(0);
?>
