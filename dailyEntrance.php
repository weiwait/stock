<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/6/7
 * Time: 12:15
 */



//error_reporting(E_ALL);
//ini_set('display_errors', 1);

use db\DatabaseManager\DatabaseManager;
use Weiwait\Daily;

include_once 'vendor/autoload.php';


new DatabaseManager();
$phpQuery = new Daily();

/**
 * @param $phpQuery Daily
 */
function again($phpQuery){
    try {
        $phpQuery->pickUpStock();
    } catch (Exception $exception) {
        file_put_contents(__DIR__ . '/log', $exception, FILE_APPEND);
        again($phpQuery);
    }
}
again($phpQuery);
