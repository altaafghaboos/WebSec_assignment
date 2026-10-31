<?php

// configuration of the database
$host		= "localhost";
$dbname		= "beauty_salon_booking_system";
$username	= "root";
$password	= "";
$charset	= "utf8mb4";

// dsn setup
// tells pdo which db to use and where to find it
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";


$options = [

	//allows the throwing of exceptions
	PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,

	//ensures that db results are returned as associative arrays, i.e. names of columns instead of column number
	PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,

	//forces native db-level prepared statements, makes better protection against SQL injection
	PDO::ATTR_EMULATE_PREPARES		=> false,
];


//attempting to create the pdo
try {

	$pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
	die('Database Connection Failed: ' . $e->getMessage());
}
?>
