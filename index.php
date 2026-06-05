<?php
/**
 * BPgranny - Game Scores Web Interface
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPgranny - Scores</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .info-box {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .info-box p {
            color: #666;
            line-height: 1.6;
        }
        .info-box code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .game {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .game:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .game h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .rank {
            font-weight: bold;
            color: #667eea;
            width: 50px;
        }
        .coming-soon {
            background: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .coming-soon h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .coming-soon p {
            color: #666;
            margin-bottom: 10px;
        }
        .api-docs {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .api-docs h3 {
            color: #667eea;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .api-example {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 BPgranny - Game Scores</h1>
        
        <div class="coming-soon">
            <h2>🚀 Web Interface Coming Soon</h2>
            <p>The full web listing of games and their scores is under development.</p>
            <p>Currently, use the API endpoints below to submit and retrieve scores.</p>
        </div>

        <div class="api-docs">
            <h3>📡 API Documentation</h3>
            
            <h3>✅ Check Server Status</h3>
            <div class="api-example">
GET /score_script.php?status=1
            </div>
            <p><strong>Response:</strong> <code>{"status":"online"}</code></p>
            
            <h3>📤 Submit a Score</h3>
            <div class="api-example">
GET /score_script.php?gameid=123&playername=Player&score=1000&code=HASH
            </div>
            <p><strong>Parameters:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><code>gameid</code> - Game identifier (numeric)</li>
                <li><code>playername</code> - Player name (up to 255 chars)</li>
                <li><code>score</code> - Score value (non-negative integer)</li>
                <li><code>code</code> - MD5 hash for security validation</li>
            </ul>
            <p style="margin-top: 10px;"><strong>Hash Calculation (PHP example):</strong></p>
            <div class="api-example">
$code = md5($gameid . $playername . $score . $secret_key);
            </div>
            
            <h3>📥 Retrieve Top Scores</h3>
            <div class="api-example">
GET /score_script.php?gameid=123
            </div>
            <p><strong>Response:</strong></p>
            <div class="api-example">
{
  "status": "success",
  "gameid": "123",
  "count": 5,
  "scores": [
    {
      "playername": "Player1",
      "score": "5000",
      "scoredate": "Jun 05 2026"
    }
  ]
}
            </div>
        </div>
    </div>
</body>
</html>
?>
