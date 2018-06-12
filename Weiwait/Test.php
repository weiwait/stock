<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2018/4/26
 * Time: 15:41
 */

namespace Weiwait;


use db\StockMarket;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Test
{
    public function test($code)
    {
        ob_start();
        $stocks = StockMarket::allRecordOfStock($code, 2018, 2018);

        if (!$stocks) {
            return false;
        }

        $trend = $this->trend($stocks);

        if (!$trend) {
            return false;
        }

        $length = count($stocks);
        $day = 1;

//        $openingPrice = $stocks[$length - $day]['opening_price'];

        $currentTrading = $this->currentTrading($code);
        if ($currentTrading) {
            $openingPrice = $currentTrading[1];
            if (!$openingPrice > 0) {
                return false;
            }
        } else {
            return false;
        }

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
        $yesterdayPrice = $openingPrice * $rate * $errorRate + $errorAvg;

        echo "上一日最低价<br>";
        echo $yesterdayPrice;
        echo "<br>";


        //前两天天最佳计算数量
        $rate = $this->minimumRate($stocks, $theDayBeforeYesterdayNum);
        $errors = $this->errorRate($stocks, $rate, $theDayBeforeYesterdayNum);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        $theDayBeforeYesterdayPrice = $openingPrice * $rate * $errorRate + $errorAvg;

        echo "前两天最低价<br>";
        echo $theDayBeforeYesterdayPrice;
        echo "<br>";

        //所有的最佳计算数量
        $rate = $this->minimumRate($stocks, $length);
        $errors = $this->errorRate($stocks, $rate, $length);
        $errorRate = $errors[0];
        $errorAvg = $errors[1];
        $allDaysPrice = $openingPrice * $rate * $errorRate + $errorAvg;

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
            $sum10 += $openingPrice * $rate * $errorRate + $errorAvg;
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
        $maxYesterdayPrice = $openingPrice * $maxRate * $maxErrorRate + $maxErrorAvg;

        echo "上一日最高价<br>";
        echo $maxYesterdayPrice;
        echo "<br>";


        //前两天天最佳计算数量
        $maxRate = $this->maximumRate($stocks, $theDayBeforeYesterdayNum);
        $maxErrors = $this->maxErrorRate($stocks, $maxRate, $theDayBeforeYesterdayNum);
        $maxErrorRate = $maxErrors[0];
        $maxErrorAvg = $maxErrors[1];
        $maxTheDayBeforeYesterdayPrice = $openingPrice * $maxRate * $maxErrorRate + $maxErrorAvg;

        echo "前两天最高价<br>";
        echo $maxTheDayBeforeYesterdayPrice;
        echo "<br>";

        //所有的最佳计算数量
        $maxRate = $this->maximumRate($stocks, $length);
        $maxErrors = $this->maxErrorRate($stocks, $maxRate, $length);
        $maxErrorRate = $maxErrors[0];
        $maxErrorAvg = $maxErrors[1];
        $maxAllDaysPrice = $openingPrice * $maxRate * $maxErrorRate + $maxErrorAvg;

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
            $maxSum10 += $openingPrice * $maxRate * $maxErrorRate + $maxErrorAvg;
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

        $maxPredictedValue -= $maxPredictedValue * 0.03;

//        if ($maxPredictedValue < $stocks[$length - $day]['maximum_price']) {
//            $max = 1;
//        } else {
//            $max = 0;
//        }
//
        $predictedValue += $predictedValue * 0.02;
//
//        if ($predictedValue > $stocks[$length - $day]['minimum_price']) {
//            $min = 1;
//        } else {
//            $min = 0;
//        }

        $profit2 = (10000 / $predictedValue) * ($maxPredictedValue - $predictedValue);
//        $profit3 = (10000 / $stocks[$length - $day]['minimum_price']) * ($stocks[$length - $day]['maximum_price'] - $stocks[$length - $day]['minimum_price']);

        ob_clean();
        ob_end_clean();
        echo "{$code}\n";
        if ($profit2 > 100 && $openingPrice > $predictedValue) {
            return ['code' => $code, 'profit' => $profit, 'profit2' => $profit2, 'predicted_value' => $predictedValue, 'opening_price' => $openingPrice, 'max_predicted_value' => $maxPredictedValue];
        } else {
            return false;
        }
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

    //取近4天走势
    public function trend($stocks)
    {
        $count = count($stocks);
        $perfect = true;
        $desirable = true;
        for ($i = 1; $i < 5; $i++) {
            if (!$stocks[$count - $i]['maximum_price'] > $stocks[$count - 1 - $i]['maximum_price']) {
                $perfect = false;
            }

            if (!$stocks[$count - $i]['maximum_price'] > $stocks[$count - 2 - $i]['maximum_price']) {
                $desirable = false;
            }
        }

        if ($desirable) {
            if ($perfect) {
                return 'perfect';
            } else {
                return 'desirable';
            }
        } else {
            return false;
        }
    }

    public static function currentTrading($stockCode)
    {
        $client = new Client();
        try {
            $result = $client->request('GET', "http://hq.sinajs.cn/list=sz{$stockCode}");
        } catch (GuzzleException $e) {
            return false;
        }
        $code = $result->getStatusCode();
        if (200 == $code) {
            $data = $result->getBody();
            $data = mb_convert_encoding($data, 'UTF-8', 'GBK');
            $data = explode('"', $data);
            $data = explode(',', $data[1]);
            foreach ($data as $key => $value) {
                if ($key > 9 && $key < 30 && $key % 2 == 0) {
                    $data[$key] = substr($value, 0, -2);
                }
            }
            return $data;
        }
        return false;
    }
}