<?php
/**
 * query test
 */

require_once "../db/user.php";

$user = new User();

$user->username = "query test name for insert1";
$user->age = 50;
$result = $user->insert();

$user->username = "query test name for insert2";
$user->age = 50;
$result = $user->insert();

//单个查询
$findResult = $user->findOne(['id' => $user->id]);
var_dump($findResult);
var_dump($findResult->username);

//单个查询ID
$findResult = $user->findOneById($user->id);
var_dump($findResult);

//查询所有记录
$findAllResult = $user->find(['age' => 50]);
foreach ($findAllResult as $item) {
    print_r($item);
    echo "<br>";
}

//查询统计
$count = $user->count(['age' => 50]);
var_dump($count);


//分页查询
$findAllResult = $user->find(['age' => 50], [], 1, 2);
foreach ($findAllResult as $item) {
    print_r($item->username);
    echo "<br>";
}