<?
	///////////////////////////////////////////////////////
	// Online Score Script
	// Jeff Vance 
	// Version 1.3
	// Files and sources can be found at www.flyinvinteractive.com
	//////////////////////////////////////////////////////

	// You need to fill in this data from your own mySQL server
	
	// Your host -- for example localhost or mysql.server.com
	$host = 'sql104.byethost7.com';
	
	// Your user name for mySQL
	$user = 'b7_41623426';
	
	// Your password for mySQL
    $pass = '5ZPJdnQGWBWHi$6';
	
	// Your database name for mySQL
	$dbname= 'b7_41623426_BierPierPlayerData';

	// ATTENTION
	// This is your secret key - Needs to be the same as the secret key in your game
	// You can change this but remember to change it in your game.
	// This is used to help secure the score and produce MD5 hashes
    $secret_key = "this is secret";
	
	// Your table name for mySQL
	// You can change this is you wish
	$tname= 'player data';
	
	// Number of scores to save for each gameid
	// Feel free to change this but the example file only lists 10 scores
	// You would need to code this
	$score_number = '100';
	
?>