<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:21
 */


error_reporting(E_ALL);
ini_set('display_errors', 1);

use Weiwait\PQuery;

include_once 'vendor/autoload.php';


new db\DatabaseManager\DatabaseManager();
$phpQuery = new PQuery();
//echo new \Weiwait\PQuery('index');
//$phpQuery->stockCode('http://quote.eastmoney.com/stocklist.html');


/**
 * @param $phpQuery PQuery
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
