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
        $stocks = StockMarket::allRecordOfStock('000002', 2017, 2017);

        $length = count($stocks);
        $day = 1;
        $yesterday = $stocks[$length - $day];
        $theDayBeforeYesterday = $stocks[$length - $day - 1];
        $yesterdayNum = $this->historyItems(array_slice($stocks, 0, $length - $day), $length - $day, $yesterday);
        $theDayBeforeYesterdayNum = $this->historyItems(array_slice($stocks, 0, $length - $day - 1), $length - $day - 1, $theDayBeforeYesterday);

        echo "day1{$yesterdayNum}<br>day2{$theDayBeforeYesterdayNum}<br>";

        //上一天最佳计算数量
        $rate = $this->minimumRate($stocks, $yesterdayNum);
        $errors = $this->errorRate($stocks, $rate, $yesterdayNum);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        $yesterdayPrice = $stocks[$length - $day]['opening_price'] * $rate * $errorRate + $errorAvg;

        echo "上一日最低价<br>";
        echo $yesterdayPrice;
        echo "<br>";


        //前两天天最佳计算数量
        $rate = $this->minimumRate($stocks, $theDayBeforeYesterdayNum);
        $errors = $this->errorRate($stocks, $rate, $theDayBeforeYesterdayNum);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        $theDayBeforeYesterdayPrice = $stocks[$length - $day]['opening_price'] * $rate * $errorRate + $errorAvg;

        echo "前两天最低价<br>";
        echo $theDayBeforeYesterdayPrice;
        echo "<br>";

        //所有的最佳计算数量
        $rate = $this->minimumRate($stocks, $length);
        $errors = $this->errorRate($stocks, $rate, $length);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        $allDaysPrice = $stocks[$length - $day]['opening_price'] * $rate * $errorRate + $errorAvg;

        echo "总最低价<br>";
        echo $allDaysPrice;
        echo "<br>";

        //近十天递归计算
        $sum10 = 0;
        for ($j = 1; $j < 11; $j++) {
            $rate = $this->minimumRate($stocks, $j);
            $errors = $this->errorRate($stocks, $rate, $j);
            $errorRate = $errors[0];
            $errorAvg = $errors[1];
            $sum10 += $stocks[$length - $day]['opening_price'] * $rate * $errorRate + $errorAvg;
        }

        $sum = $sum10 + $yesterdayPrice + $theDayBeforeYesterdayPrice + $allDaysPrice;
        $predictedValue = $sum / 13;



        $yesterdayNum = $this->maxHistoryItems(array_slice($stocks, 0, $length - $day), $length - $day, $yesterday);
        $theDayBeforeYesterdayNum = $this->maxHistoryItems(array_slice($stocks, 0, $length - $day - 1), $length - $day - 1, $theDayBeforeYesterday);



        echo "day1{$yesterdayNum}<br>day2{$theDayBeforeYesterdayNum}<br>";
        //上一天最佳计算数量
        $maxRate = $this->maximumRate($stocks, $yesterdayNum);
        $maxErrors = $this->maxErrorRate($stocks, $rate, $yesterdayNum);
        $maxErrorRate = $maxErrors[0];
        $maxErrorAvg = $maxErrors[1];
        $maxYesterdayPrice = $stocks[$length - $day]['opening_price'] * $maxRate * $maxErrorRate + $maxErrorAvg;

        echo "上一日最高价<br>";
        echo $maxYesterdayPrice;
        echo "<br>";


        //前两天天最佳计算数量
        $maxRate = $this->maximumRate($stocks, $theDayBeforeYesterdayNum);
        $maxErrors = $this->maxErrorRate($stocks, $maxRate, $theDayBeforeYesterdayNum);
        $maxErrorRate = $maxErrors[0];
        $maxErrorAvg = $maxErrors[1];
        $maxTheDayBeforeYesterdayPrice = $stocks[$length - $day]['opening_price'] * $maxRate * $maxErrorRate + $maxErrorAvg;

        echo "前两天最高价<br>";
        echo $maxTheDayBeforeYesterdayPrice;
        echo "<br>";

        //所有的最佳计算数量
        $maxRate = $this->maximumRate($stocks, $length);
        $maxErrors = $this->maxErrorRate($stocks, $maxRate, $length);
        $maxErrorRate = $maxErrors[0];
        $maxErrorAvg = $maxErrors[1];
        $maxAllDaysPrice = $stocks[$length - $day]['opening_price'] * $maxRate * $maxErrorRate + $maxErrorAvg;

        echo "总最高价<br>";
        echo $maxAllDaysPrice;
        echo "<br>";

        //近十天递归计算
        $maxSum10 = 0;
        for ($j = 1; $j < 11; $j++) {
            $maxRate = $this->maximumRate($stocks, $j);
            $maxErrors = $this->maxErrorRate($stocks, $maxRate, $j);
            $maxErrorRate = $maxErrors[0];
            $maxErrorAvg = $maxErrors[1];
            $maxSum10 += $stocks[$length - $day]['opening_price'] * $maxRate * $maxErrorRate + $maxErrorAvg;
        }

        $sum = $maxSum10 + $maxYesterdayPrice + $maxTheDayBeforeYesterdayPrice + $maxAllDaysPrice;
        $maxPredictedValue = $sum / 13;

        echo "实际最低价<br>";
        echo $stocks[$length - $day]['minimum_price'];
        echo "<br>";
        echo "最终最低价<br>";
        echo $predictedValue;
        echo "<br>";
        echo "近十日最低价<br>";
        echo $sum10 / 10;
        echo "<br>";

        echo "实际最高价<br>";
        echo $stocks[$length - $day]['maximum_price'];
        echo "<br>";
        echo "最终最高价<br>";
        echo $maxPredictedValue;
        echo "<br>";
        echo "近十日最高价<br>";
        echo $maxSum10 / 10;
        echo "<br>";
        echo "万元盈利<br>";
        $profit = (10000 / $predictedValue) * ($maxPredictedValue - $predictedValue);
        echo $profit;
        echo "<br>";
        echo "盈利率<br>";
        echo $profit / 100 . "%";
    }


    //最低价与开盘价比率 计算最低价
    private function minimumRate($stocks, $num)
    {
        $sum = 0;

        $i = count($stocks) - $num;
        $j = 0;
        do {
            $sum += $stocks[$i]['minimum_price'] / $stocks[$i]['opening_price'];
            $i++;
            $j++;
        } while (isset($stocks[$i + 1]));

        return $sum / $j;
    }

    //计算实际最低价和通过最低价率算出的最低价的比率
    private function errorRate($stocks, $rate, $num)
    {
        $errorRateSum = 0;
        $avgSum = 0;
        $j = 0;

        $i = count($stocks) - $num;
        do {
            $errorRateSum += $stocks[$i]['minimum_price'] / ($stocks[$i]['opening_price'] * $rate);
            $avgSum += $stocks[$i]['minimum_price'] - ($stocks[$i]['opening_price'] * $rate);
            $i++;
            $j++;
        } while (isset($stocks[$i + 1]));

        return [$errorRateSum / $j, $avgSum / $j];
    }

    //预测需要多少条历史记录去计算最低价
    private function historyItems($stocks, $length, $currentStock)
    {
        $priceSpread = 0;
        $num = 1;
        $i = 1;

        do {
            $rate = $this->minimumRate($stocks, $i);
            $minimumPrice = $currentStock['opening_price'] * $rate;
            $tmpPS = $minimumPrice - $currentStock['minimum_price'];

            if ($priceSpread > $tmpPS) {
                $priceSpread = $tmpPS;
                $num = $i;
            }

            $i++;
        } while (isset($stocks[$length - $i]));

        return $num;
    }


    //最高价与开盘价比率 计算最低价
    private function maximumRate($stocks, $num)
    {
        $sum = 0;

        $i = count($stocks) - $num;
        $j = 0;
        do {
            $sum += $stocks[$i]['maximum_price'] / $stocks[$i]['opening_price'];
            $i++;
            $j++;
        } while (isset($stocks[$i + 1]));

        return $sum / $j;
    }

    //计算实际最低价和通过最低价率算出的最低价的比率
    private function maxErrorRate($stocks, $rate, $num)
    {
        $errorRateSum = 0;
        $avgSum = 0;
        $j = 0;

        $i = count($stocks) - $num;
        do {
            $errorRateSum += $stocks[$i]['maximum_price'] / ($stocks[$i]['opening_price'] * $rate);
            $avgSum += $stocks[$i]['maximum_price'] - ($stocks[$i]['opening_price'] * $rate);
            $i++;
            $j++;
        } while (isset($stocks[$i + 1]));

        return [$errorRateSum / $j, $avgSum / $j];
    }

    //预测需要多少条历史记录去计算最低价
    private function maxHistoryItems($stocks, $length, $currentStock)
    {
        $priceSpread = 0;
        $num = 1;
        $i = 1;

        do {
            $rate = $this->maximumRate($stocks, $i);
            $maximumPrice = $currentStock['opening_price'] * $rate;
            $tmpPS = $maximumPrice - $currentStock['maximum_price'];

            if ($priceSpread > $tmpPS) {
                $priceSpread = $tmpPS;
                $num = $i;
            }

            $i++;
        } while (isset($stocks[$length - $i]));

        return $num;
    }

    //
}