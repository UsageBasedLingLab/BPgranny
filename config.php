<?php
/**
 * BPgranny Configuration
 * Load configuration from environment variables for production safety
 */

// Get environment variables or use defaults
$host = getenv('DB_HOST') ?: 'mysql.railway.internal';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 3306);

// Application configuration
$secret_key = getenv('SECRET_KEY') ?: 'bearpearonabeerpier';
$tname = getenv('TABLE_NAME') ?: 'scores';
$score_number = (int)(getenv('SCORE_NUMBER') ?: 10);
?>
