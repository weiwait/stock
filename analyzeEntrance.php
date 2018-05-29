<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:21
 */


use Weiwait\HighOpen;


include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();

//$highOpen = new HighOpen();
//$highOpen->index();

set_time_limit(3600);

$test = new \Weiwait\Test();
$stocks = \db\Stock::all()->toArray();
$stocks = array_chunk($stocks, 100)[0];
$profits = [];
foreach ($stocks as $stock) {
    $profits[] = $test->test($stock['code']);
}

print_r($profits);
