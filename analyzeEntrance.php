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

$test = new \Weiwait\Test();
$test->test();
