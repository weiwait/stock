<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/1/25
 * Time: 11:50
 */


include_once 'vendor/autoload.php';


//new db\DatabaseManager\DatabaseManager();
//
//$before = microtime(true);
//
//$db = new \Illuminate\Database\Capsule\Manager();
//for ($i = 1; $i < 2000; $i++) {
//    $db->table('stocks')->find($i);
//}
//
//$after = microtime(true);
//echo ($after - $before);

$t = getopt('t:');
print_r($t);


