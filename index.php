<?php
require_once "vendor/autoload.php";

use Model\User;


$user = new User();

$user->username = "insert test name";
$user->age = 20;

$result = $user->insert();
var_dump($result);