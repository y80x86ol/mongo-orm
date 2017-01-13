<?php
/**
 * delete test
 */

require_once "../db/user.php";

$user = new User();

$user->username = "insert test name1";
$user->age = 30;

$result = $user->insert();
var_dump($result);

$user = new User();
$user->username = "insert test name2";
$user->age = 30;

$result = $user->insert();
var_dump($result);

$result = $user->remove();
var_dump($result);