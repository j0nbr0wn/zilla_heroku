<?php

$name = "?first_name=";
$name .= $_GET['first_name'];
$name .= "&last_name=";
$name .= $_GET['last_name'];

$username = "?username=";
$username .= $_GET['first_name'];
$username .= " ";
$username .= $_GET['last_name'];

header("location: login.php".$username);
?>