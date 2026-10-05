<?php
require_once('../../connect.php');

$brand = $_POST["brand"];
// $brand = 1;


// $team = $_POST["team"];

$serach_home = SelectAllQuery(" SELECT * FROM `homestyles` WHERE brand = '$brand' ORDER BY homestyle ASC");

// var_dump($serach_arch);
$jsonData = json_encode($serach_home, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// $jsonData = json_encode($team, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


header('Content-Type: application/json');
echo $jsonData;
