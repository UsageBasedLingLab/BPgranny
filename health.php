<?php
/**
 * Health check endpoint - no database required
 * This is served immediately to verify the app is running
 */

header('Content-Type: text/plain; charset=utf-8');

// Simple health check - just verify PHP works
echo "online";
exit(0);
?>
