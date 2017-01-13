<?php
/**
 * update test
 */

require_once "../db/user.php";

$user = new User();

$user->username = "update test name for insert";
$user->age = 20;

$result = $user->insert();
var_dump($result);


$user->username = "update test name for update";
$result = $user->save();
var_dump($result);