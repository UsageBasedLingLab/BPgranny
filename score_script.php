<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require(dirname(__FILE__) . "/config.php");
$db = mysqli_connect($host, $user, $pass, $dbname, 3306);
if (!$db) {
    die("Connection failed: " . mysqli_connect_error() . " (Code: " . mysqli_connect_errno() . ")");

$db = mysqli_connect($host, $user, $pass, $dbname, 3306) or die(mysqli_connect_error());

if (!mysqli_num_rows(mysqli_query($db, "SHOW TABLES LIKE '" . mysqli_real_escape_string($db, $tname) . "'")))
{
    $query = "CREATE TABLE `$tname` (
        `id` int(11) NOT NULL auto_increment,
        `gameid` varchar(255) NOT NULL,
        `playername` varchar(255) NOT NULL,
        `score` int(255) NOT NULL,
        `scoredate` varchar(255) NOT NULL,
        `md5` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    mysqli_query($db, $query) or die(mysqli_error($db));
}

if (isset($_GET["status"]))
{
    echo "online";
    exit;
}

$gameid_safe = mysqli_real_escape_string($db, $_GET["gameid"]);
if (!is_numeric($gameid_safe))
{
    exit; 
}

if (isset($_GET["playername"]) && isset($_GET["gameid"]) && isset($_GET["score"]))
{
    $playername_safe = str_replace("|", "_", $_GET["playername"]);
    $playername_safe = mysqli_real_escape_string($db, $playername_safe);
    $score_safe = mysqli_real_escape_string($db, $_GET["score"]);
    $date = date('M d Y');
    
    if (!is_numeric($score_safe)) { exit; }
    
    $security_md5 = md5($_GET["gameid"] . $_GET["playername"] . $_GET["score"] . $secret_key);
    
    if ($security_md5 != $_GET["code"]) { exit; }
    
    $query = "INSERT INTO `$tname` (gameid, playername, score, scoredate, md5) 
              VALUES ('$gameid_safe', '$playername_safe', '$score_safe', '$date', '$security_md5')";
    mysqli_query($db, $query) or die(mysqli_error($db));
}

if ($gameid_safe)
{
    $query = "SELECT * FROM `$tname` WHERE gameid='$gameid_safe' ORDER BY score DESC LIMIT 10";
    $view_data = mysqli_query($db, $query) or die(mysqli_error($db));
    while ($row_data = mysqli_fetch_array($view_data))
    {
        print($row_data["playername"]);
        print("|");
        print($row_data["score"]);
        print("|");
        print($row_data["scoredate"]);
        print("|");
    }
}
echo "Connected successfully";
die();
?>
