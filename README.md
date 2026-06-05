# BPgranny - Game Score Server

A lightweight, secure PHP-based high-score tracking system for games.

## Features

✅ **Score Management** - Store and retrieve game scores  
✅ **Security** - MD5 hash validation for score submissions  
✅ **JSON API** - RESTful endpoints for game clients  
✅ **Auto-initialization** - Automatically creates database tables  
✅ **Docker Ready** - Pre-configured for containerized deployment  

## Quick Start

### 1. Environment Configuration

Copy `.env.example` to `.env` and update with your database credentials:

```bash
cp .env.example .env
```

Edit `.env`:
```ini
DB_HOST=mysql.railway.internal
DB_USER=root
DB_PASS=your_password_here
DB_NAME=railway
DB_PORT=3306
SECRET_KEY=your_secret_key_here
TABLE_NAME=scores
SCORE_NUMBER=10
```

### 2. Docker Deployment

```bash
docker build -t bpgranny .
docker run -p 8080:8080 --env-file .env bpgranny
```

Access at: `http://localhost:8080`

### 3. Direct PHP (Development)

```bash
php -S localhost:8080
```

## API Reference

### Check Server Status

```
GET /score_script.php?status=1
```

**Response:**
```json
{"status": "online"}
```

### Submit a Score

```
GET /score_script.php?gameid=123&playername=Player&score=1000&code=HASH
```

**Parameters:**
- `gameid` - Game identifier (numeric)
- `playername` - Player name (max 255 chars)
- `score` - Score value (non-negative integer)
- `code` - MD5 security hash

**Calculate the hash (PHP):**
```php
$code = md5($gameid . $playername . $score . $secret_key);
```

**Success Response:**
```json
{
  "status": "success",
  "gameid": "123",
  "count": 1,
  "scores": [
    {
      "playername": "Player",
      "score": "1000",
      "scoredate": "Jun 05 2026"
    }
  ]
}
```

**Error Response:**
```json
{
  "error": "Security validation failed"
}
```

### Retrieve Top Scores

```
GET /score_script.php?gameid=123
```

**Response:**
```json
{
  "status": "success",
  "gameid": "123",
  "count": 3,
  "scores": [
    {
      "playername": "Champion",
      "score": "5000",
      "scoredate": "Jun 05 2026"
    },
    {
      "playername": "Runner-up",
      "score": "3500",
      "scoredate": "Jun 04 2026"
    },
    {
      "playername": "Third Place",
      "score": "2000",
      "scoredate": "Jun 03 2026"
    }
  ]
}
```

## Security Notes

⚠️ **IMPORTANT:**
- Never commit `.env` with real credentials to version control
- Use HTTPS in production for all API calls
- Keep `SECRET_KEY` secure and consistent between game clients and server
- The MD5 hash provides basic validation; use HTTPS for encryption in transit
- Change default `SECRET_KEY` from `dragonfruit42` before production deployment

## Database Schema

```sql
CREATE TABLE scores (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gameid VARCHAR(255) NOT NULL,
    playername VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    scoredate VARCHAR(255) NOT NULL,
    md5 VARCHAR(32) NOT NULL,
    INDEX gameid_idx (gameid),
    INDEX score_idx (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

## File Structure

```
.
├── config.php           # Configuration & environment setup
├── score_script.php     # Main API endpoint (refactored)
├── index.php            # Web interface & documentation
├── Dockerfile           # Docker configuration
├── php.ini              # PHP settings
├── .env.example         # Environment variables template
└── README.md            # This file
```

## Improvements Made

### Code Quality
- ✅ Fixed syntax errors (missing closing brace in original)
- ✅ Removed duplicate database connections
- ✅ Replaced string concatenation with prepared statements
- ✅ Added proper input validation and error handling
- ✅ Implemented JSON responses for all endpoints

### Security
- ✅ Removed hardcoded credentials from source code
- ✅ Switched from `mysqli_real_escape_string` to prepared statements
- ✅ Added `hash_equals()` for timing-safe comparison
- ✅ Proper HTTP status codes for error responses
- ✅ Input length validation

### Database
- ✅ Changed from MyISAM to InnoDB engine
- ✅ Updated charset to utf8mb4 (supports all Unicode)
- ✅ Added indexes on `gameid` and `score` for query performance
- ✅ Proper integer type for score and port

### Operations
- ✅ Environment variable support for multi-environment deployment
- ✅ Better error messages for debugging
- ✅ Connection timeout configuration
- ✅ Docker support with FPM and health checks

## Troubleshooting

**Connection Failed**
- Check database credentials in `.env`
- Verify database server is running and accessible
- Check network connectivity to database host

**Scores Not Saving**
- Verify MD5 hash calculation matches server-side calculation
- Check `SECRET_KEY` matches between client and server
- Review database permissions for INSERT operations

**Table Already Exists Error**
- This is normal; the script checks before creating
- If you need to reset, manually drop the table in your database

## License

See LICENSE file in repository

## Support

For issues or questions, please open an issue on GitHub.
