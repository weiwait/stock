<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/4/26
 * Time: 15:41
 */

namespace Weiwait;


use db\StockMarket;

class Test
{
    public function test()
    {
        $stocks = StockMarket::allRecordOfStock('000001', 2017, 2017);

        $pickup = [];

//        foreach ($stocks as $key => $stock) {
//            if ($stock['maximum'] > $stocks[$key - 1]['minimum']) {
//                $pickup[] = [$stocks[$key - 1], $stock];
//            }
//        }

//        $i = 0;
//        do {
//            if ($stocks[$i + 1]['maximum_price'] > $stocks[$i]['minimum_price']) {
//                $pickup[] = [$stocks[$i], $stocks[$i + 1]];
//            }
//            $i++;
//        } while (isset($stocks[$i + 1]));
//
//        print_r($pickup);

        $rate = $this->minimumRate($stocks);
        $errors = $this->errorRate($stocks, $rate);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        echo $rate . '</br>';
        echo $errorRate . '</br>';
        echo $errorAvg . '</br>';
        echo $stocks[count($stocks) - 1]['minimum_price'] . '</br>';
        echo $stocks[count($stocks) - 1]['opening_price'] * $rate * $errorRate + $errorAvg;
    }


    //最低价与开盘价比率 计算最低价
    private function minimumRate($stocks)
    {
        $sum = 0;

        $i = count($stocks) - 80;
        do {
            $sum += $stocks[$i]['minimum_price'] / $stocks[$i]['opening_price'];
            $i++;
        } while (isset($stocks[$i + 1]));

        return $sum / 80;
    }

    //计算实际最低价和通过最低价率算出的最低价的比率
    private function errorRate($stocks, $rate)
    {
        $errorRateSum = 0;
        $avgSum = 0;

        $i = count($stocks) - 80;
        do {
            $errorRateSum += $stocks[$i]['minimum_price'] / ($stocks[$i]['opening_price'] * $rate);
            $avgSum += $stocks[$i]['minimum_price'] - ($stocks[$i]['opening_price'] * $rate);
            $i++;
        } while (isset($stocks[$i + 1]));

        return [$errorRateSum / 80, $avgSum / 80];
    }

    //预测需要多少条历史记录去计算最低价
    private function historyItems()
    {

    }

}