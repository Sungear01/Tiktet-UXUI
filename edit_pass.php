<?php
session_start();
require_once './connect.php';

$id = $_POST['id'];
$pass = $_POST['new_pass'];
$username = $_POST['username'];



$update_pass = ExecuteQuery("UPDATE `password_emp` SET 
`password`='$pass'
WHERE  name_short = '$username'
"); 

if($update_pass){

    $json = 'อัพเดทเรียบร้อย';
    
    echo json_encode($json);
    

}




