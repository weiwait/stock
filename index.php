<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:21
 */

use db\Checked;
use Illuminate\Database\Capsule\Manager;

include_once 'vendor/autoload.php';

new db\DatabaseManager\DatabaseManager();


error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pre = Checked::query()->where(['code' => $_POST['code'], 'date' => '2018-07-16'])->first()->toArray();
    if (!empty($pre)) {
        $records = Manager::table('stock_markets_2018_2')->where(['stock_code' => $_POST['code'], 'date' => '2018-07-11'])->get()->map(function ($v) {
            return (array) $v;
        })->toArray()[0];
        $data = [
            'code' => $_POST['code'],
            'buy_price' => $_POST['price'],
            'volume' => $_POST['volume'],
            'predicted_profit' => $pre['profit'],
            'min_predicted_price' => $pre['predicted_value'],
            'max_predicted_price' => $pre['max_predicted_value'],
            'yesterday_opening_price' => $pre['opening_price'],
            'yesterday_minimum_price' => $records['minimum_price'],
            'yesterday_maximum_price' => $records['maximum_price'],
//            'sell_opening_price' => $pre['opening_price'],
//            'sell_minimum_price' => $records['minimum_price'],
//            'sell_maximum_price' => $records['maximum_price'],
            'buy_time' => date('Y-m-d H:i:s'),
        ];

        Manager::table('boughts')->insert($data);
    }
}

?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<form action="" method="post">
    code: <input title type="text" name="code">
    price: <input title type="text" name="price">
    volume: <input title type="text" name="volume">
    <input title type="submit" value="submit">
</form>
</body>
</html>
