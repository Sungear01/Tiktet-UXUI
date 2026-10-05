<?php
// Copy this file to connect.php and fill in real values. connect.php must never contain real credentials in git.
$dbhost  = "localhost";
$dbuser  = "CHANGE_ME";
$dbpass  = "CHANGE_ME";
$dbname1 = "CHANGE_ME";

$conn1 = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname1);
if (!$conn1) {
    die("Database connection failed");
}
mysqli_set_charset($conn1, "utf8mb4");
