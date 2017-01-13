<?php
/**
 * insert test
 */

require_once "../db/user.php";

$user = new User();

$user->username = "insert test name";
$user->age = 20;

$result = $user->insert();
var_dump($result);