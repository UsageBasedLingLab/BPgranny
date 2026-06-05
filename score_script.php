<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require(dirname(__FILE__) . "/config.php");

// Database connection with error handling
$db = @mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Connection failed',
        'message' => mysqli_connect_error(),
        'code' => mysqli_connect_errno()
    ]);
    exit(1);
}

// Set character set
mysqli_set_charset($db, "utf8mb4");

// Create table if it doesn't exist
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
        echo json_encode(['error' => 'Table creation failed: ' . mysqli_error($db)]);
        mysqli_close($db);
        exit(1);
    }
}

// Handle status check
if (isset($_GET["status"])) {
    echo json_encode(['status' => 'online']);
    mysqli_close($db);
    exit(0);
}

// Validate gameid parameter
if (!isset($_GET["gameid"])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing gameid parameter']);
    mysqli_close($db);
    exit(1);
}

$gameid = trim($_GET["gameid"]);
if (!is_numeric($gameid) || $gameid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid gameid format']);
    mysqli_close($db);
    exit(1);
}

// Handle score submission
if (isset($_GET["playername"]) && isset($_GET["score"]) && isset($_GET["code"])) {
    // Validate inputs
    $playername = trim($_GET["playername"]);
    $score = trim($_GET["score"]);
    $code = trim($_GET["code"]);
    
    // Check required fields
    if (empty($playername) || empty($score)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing playername or score']);
        mysqli_close($db);
        exit(1);
    }
    
    // Validate score is numeric
    if (!is_numeric($score) || $score < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid score format']);
        mysqli_close($db);
        exit(1);
    }
    
    // Verify security hash
    $security_md5 = md5($gameid . $playername . $score . $secret_key);
    if ($security_md5 !== $code) {
        http_response_code(403);
        echo json_encode(['error' => 'Security validation failed']);
        mysqli_close($db);
        exit(1);
    }
    
    // Clean playername - remove pipe character
    $playername = str_replace("|", "_", $playername);
    $playername = substr($playername, 0, 255); // Limit length
    
    $date = date('M d Y');
    
    // Use prepared statement for security
    $insert_query = "INSERT INTO `$tname_escaped` (gameid, playername, score, scoredate, md5) 
                     VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $insert_query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepared statement failed: ' . mysqli_error($db)]);
        mysqli_close($db);
        exit(1);
    }
    
    mysqli_stmt_bind_param($stmt, 'isiss', $gameid, $playername, $score, $date, $security_md5);
    
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Insert failed: ' . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        exit(1);
    }
    
    mysqli_stmt_close($stmt);
}

// Retrieve and display scores
$select_query = "SELECT playername, score, scoredate FROM `$tname_escaped` 
                 WHERE gameid = ? 
                 ORDER BY score DESC 
                 LIMIT ?";
$stmt = mysqli_prepare($db, $select_query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepared statement failed: ' . mysqli_error($db)]);
    mysqli_close($db);
    exit(1);
}

$limit = (int)$score_number;
mysqli_stmt_bind_param($stmt, 'ii', $gameid, $limit);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . mysqli_stmt_error($stmt)]);
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

// Return JSON response
echo json_encode([
    'status' => 'success',
    'gameid' => $gameid,
    'count' => count($scores),
    'scores' => $scores
]);

exit(0);
?>
