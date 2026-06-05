<?php
/**
 * Database Configuration
 * WARNING: Move this to environment variables in production
 */

// Get config from environment variables or use defaults
$host = getenv('DB_HOST') ?: 'mysql.railway.internal';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 3306);

// Secret key for MD5 hashing (should match game client)
$secret_key = getenv('SECRET_KEY') ?: 'dragonfruit42';

// Database table name
$tname = getenv('TABLE_NAME') ?: 'scores';

// Number of scores to display
$score_number = (int)(getenv('SCORE_NUMBER') ?: 10);

// Database connection options
$db_options = array(
    mysqli_init(),
    MYSQLI_OPT_CONNECT_TIMEOUT => 5,
);
?>
