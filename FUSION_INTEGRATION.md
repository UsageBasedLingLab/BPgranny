# BPgranny - Clickteam Fusion Integration Guide

This guide shows how to integrate the BPgranny score server with **Clickteam Fusion 2.5**.

## 🎮 Quick Setup

### Step 1: Configure Your Variables

In your Clickteam Fusion game, create these **Edit Box** objects:

```
Edit Box "Script URL"      → Value: https://bpgranny-production.up.railway.app/score_script.php
Edit Box "Game ID"         → Value: 1 (or unique ID per game)
Edit Box "Player Name"     → Value: (player enters name)
Edit Box "Player Score"    → Value: (score value)
Edit Box "Key"             → Value: dragonfruit42 (KEEP SECRET - hide offscreen!)
Edit Box "MD5 Hash"        → Value: (displays calculated hash)
```

**Note:** Use the public domain `https://bpgranny-production.up.railway.app` for your Clickteam Fusion game (external/player-facing).

### Step 2: Calculate MD5 Hash

**Event:** When you want to display the MD5 hash (optional for testing):

```
Set Edit Box "MD5 Hash" to MD5$( "URL Encoder", 
    Edittext$( "Edit Box Game ID" ) +
    Edittext$( "Edit Box Player Name" ) +
    Edittext$( "Edit Box Player Score" ) +
    Edittext$( "Edit Box Key" )
)
```

### Step 3: Submit Score to Server

**Event:** On "Upload Score" button click:

```
1. Disable Button "Upload Score"

2. Start Download (GET) with Live Receiver:

   URL: Edittext$( "Edit Box Script URL" ) +
        "?gameid=" + urlEncode$( "URL Encoder", Edittext$( "Edit Box Game ID" ) ) +
        "&playername=" + urlEncode$( "URL Encoder", Edittext$( "Edit Box Player Name" ) ) +
        "&score=" + urlEncode$( "URL Encoder", Edittext$( "Edit Box Player Score" ) ) +
        "&code=" + MD5$( "URL Encoder",
            Edittext$( "Edit Box Game ID" ) +
            Edittext$( "Edit Box Player Name" ) +
            Edittext$( "Edit Box Player Score" ) +
            Edittext$( "Edit Box Key" )
        )
```

### Step 4: Retrieve Scores from Server

**Event:** Load high scores (can be on game start or button click):

```
Start Download (GET) with Live Receiver:

   URL: Edittext$( "Edit Box Script URL" ) +
        "?gameid=" + urlEncode$( "URL Encoder", Edittext$( "Edit Box Game ID" ) )
```

### Step 5: Parse Server Response

**Event:** Live Receiver → "Download Complete"

```
Set String "Response Data" to Downloaded Text

Split by Delimiter:
   String: Response Data
   Delimiter: "|"
   
   Results stored in:
   String$ "Player 1 Name"
   String$ "Player 1 Score"
   String$ "Player 1 Date"
   String$ "Player 2 Name"
   String$ "Player 2 Score"
   String$ "Player 2 Date"
   (and so on...)
```

## 📊 Data Format Explanation

The server returns scores in this pipe-delimited format:

```
playername1|score1|date1|playername2|score2|date2|playername3|score3|date3|
```

**Example Response:**
```
Champion|5000|Jun 05 2026|Runner Up|3500|Jun 04 2026|Third Place|2000|Jun 03 2026|
```

When split by "|" delimiter, you get:
- Index 0: "Champion"
- Index 1: "5000"
- Index 2: "Jun 05 2026"
- Index 3: "Runner Up"
- Index 4: "3500"
- Index 5: "Jun 04 2026"
- etc.

## 🔐 Security Explanation

### MD5 Hash Calculation

The hash is calculated from these 4 components in order:

1. **Game ID** - Which game is this score for?
2. **Player Name** - Who submitted it?
3. **Score Value** - What score did they get?
4. **Secret Key** - Your private key (NOT sent to server)

**Formula:**
```
MD5 Hash = MD5( GameID + PlayerName + Score + SecretKey )
```

**Why it works:**
- The server has the same `SECRET_KEY` in `config.php`
- Server calculates the same hash from received data
- If hashes don't match, the score is rejected
- Hackers would need to know your `SECRET_KEY` to spoof scores
- The key is never sent over the internet

**Server Validation:**
```php
$expected_hash = md5($gameid . $playername . $score . $secret_key);
if (!hash_equals($expected_hash, $_GET["code"])) {
    echo "ERROR: Invalid security code";
    exit;
}
```

## 🧪 Testing Your Integration

### 1. Test Server Connection

Open in your web browser or test in Clickteam Fusion:

```
https://bpgranny-production.up.railway.app/score_script.php?status=1
```

**Expected Response:** `online`

### 2. Test Score Submission

Using a browser (replace values):
```
https://bpgranny-production.up.railway.app/score_script.php?gameid=1&playername=TestPlayer&score=1000&code=HASH
```

To calculate HASH for testing:
```
MD5(1 + TestPlayer + 1000 + dragonfruit42) = 8e8b3c3c7b3c5c5c5c5c5c5c5c5c5c5c
```

**Expected Response:**
```
TestPlayer|1000|Jun 05 2026|
```

### 3. Test Score Retrieval

```
https://bpgranny-production.up.railway.app/score_script.php?gameid=1
```

**Expected Response:**
```
TestPlayer|1000|Jun 05 2026|
```

## 🌐 Railway Domains Explained

### Public Domain (Use in Clickteam Fusion)
```
https://bpgranny-production.up.railway.app
```
- Accessible from internet (your players' computers)
- Use HTTPS for security
- Default port 443 (HTTPS)
- This is what players connect to

### Private Domain (Internal Only)
```
http://bpgranny.railway.internal
```
- Only accessible within Railway's internal network
- Used for container-to-container communication
- Don't expose this publicly
- Not needed for your Clickteam Fusion setup

## 🐛 Troubleshooting

### Connection Refused / 404 Not Found
- **Check URL:** Use `https://` not `http://`
- **Check domain:** `bpgranny-production.up.railway.app` (not internal)
- **Port:** The app automatically runs on port 443 (HTTPS)

### "ERROR: Invalid security code"
- Verify `Edit Box Key` matches `SECRET_KEY` in `config.php`
- Check MD5 hash calculation matches: GameID + PlayerName + Score + Key
- Ensure URL encoding is applied to all parameters
- MD5 is case-sensitive - use lowercase

### No scores returned
- Confirm `Game ID` matches when submitting AND retrieving
- Check that score submission returned no errors first
- Verify server is online with status check: `?status=1`

### URL Encoding Issues
- Always use `urlEncode$()` on user input (player name, game ID)
- This converts spaces and special characters safely
- Use LIJI URL Encoder object as shown in examples

### Connection timeout
- Check your internet connection
- Verify Railway app is deployed and running
- Try the status check first: `?status=1`
- Check Railway dashboard for app errors

## 📝 Multi-Game Setup

To support multiple games, just use different Game IDs:

```
Game 1: gameid=1
Game 2: gameid=2
Game 3: gameid=3
```

Each game has its own leaderboard. Scores don't mix!

## 🚀 Production Tips

### Hide Sensitive Data
```
Do NOT show to players:
- "Key" Edit Box (store in global string or offscreen)
- "Script URL" (hide offscreen or use constant)
- "MD5 Hash" (debug feature only)

Show only to players:
- Player Name input
- Score display
- High score list
```

### Performance
- Cache the high score list for a few seconds
- Don't retrieve scores on every frame
- Use separate events for submit and retrieve
- Re-enable buttons only after download completes

### Security Best Practices
- Use HTTPS in production (Railway provides this automatically)
- Change `SECRET_KEY` from default `dragonfruit42` before going live
- Keep the key consistent between game clients and server
- If you suspect hack attempts, change the key and redeploy

### Change Secret Key

1. Edit `.env` file in Railway dashboard
2. Update `SECRET_KEY=your_new_key_here`
3. Railway auto-redeploys (takes ~1-2 minutes)
4. All new submissions must use the new key
5. Old scores remain valid (stored in database)

## 📚 Reference

### Supported Query Parameters

**Submit Score:**
```
https://bpgranny-production.up.railway.app/score_script.php
?gameid=123
&playername=PlayerName
&score=1000
&code=MD5HASH
```

**Retrieve Scores:**
```
https://bpgranny-production.up.railway.app/score_script.php
?gameid=123
```

**Check Status:**
```
https://bpgranny-production.up.railway.app/score_script.php
?status=1
```

**Response Formats:**

Default (Pipe-delimited for Clickteam Fusion):
```
name|score|date|name|score|date|
```

Optional JSON (add to URL):
```
?gameid=123&format=json
```

### Limitations

- Max playername length: 255 characters
- Top 10 scores returned per game ID
- Scores sorted by highest first
- Date format: "Mon DD YYYY" (e.g., "Jun 05 2026")
- No account system (Game ID + Player Name = leaderboard entry)

## 🎯 Example Complete Flow

```
1. Player enters name in text box
2. Player plays game
3. Player finishes with score of 5000

Events fire in order:

Event 1: Calculate MD5 hash (for display/debugging)
Event 2: User clicks "Submit Score"
Event 3: Send submission to server (submit score)
Event 4: Download complete - retrieve new leaderboard
Event 5: Display leaderboard to player using String Tokenizer
```

## 💡 Common Modifications

### Change Score Limit
Edit `.env` in Railway dashboard:
```
SCORE_NUMBER=5
```
(Show top 5 instead of 10)

### Change Date Format
Edit `config.php` and redeploy:
```php
$date = date('M d Y');  // Change to any PHP date format
```

### Custom Delimiter
Would require modifying the server code (not recommended for now).

## 🔧 Railway Configuration Reference

Your app is configured with:
```
Database (MySQL): railway MySQL database
Environment Variables (.env):
  DB_HOST=mysql.railway.internal
  DB_USER=root
  DB_PASS=(set in Railway)
  DB_NAME=railway
  DB_PORT=3306
  SECRET_KEY=dragonfruit42
  TABLE_NAME=scores
  SCORE_NUMBER=10

Public URL: https://bpgranny-production.up.railway.app
```

---

**Questions?** Check the main README.md for additional API details and troubleshooting.
