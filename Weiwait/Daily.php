<?php
/**
 * Created by PhpStorm.
 * User: weiwait
 * Date: 2017/10/26
 * Time: 9:23
 */

namespace Weiwait;


use db\DatabaseManager\DatabaseManager;
use db\Stock;
use db\StockCrawlerCycle;
use db\StockMarket;
use db\StockSz;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager;
use QL\Ext\CurlMulti;
use QL\QueryList;
use Symfony\Component\Config\Definition\Exception\Exception;

class Daily
{
    private $response = '';
    private $urls = [];
    private $start = '';
    private $end = '';
    private $data = [];
    private $stockCodes = [];
    private $counter = 0;
    private $test = false;

    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->start = microtime(true);
    }

    public function __toString()
    {
        return $this->response;
    }

    public function pickUpStock()
    {
        $year = date('Y');
        $month = date('m');
        $season = ceil((date('n'))/3);

        $stockCodes = Stock::query()->select(['prefix_code'])->get()->toArray();
        $stockCodes = array_column($stockCodes, 'prefix_code');

        $crawlerCycle = StockCrawlerCycle::query()->where(['year' => date('md')])->get()->toArray();
        if (empty($crawlerCycle)) {
            $crawlerCycleId = StockCrawlerCycle::query()->insertGetId(['year' => date('md')]);
        } else {
            $crawlerCycleId = $crawlerCycle[0]['id'];
        }

        $done = [];
        foreach ($stockCodes as $where => $item) {
            if (!$done) {
                $crawlerCycle = StockCrawlerCycle::query()->find($crawlerCycleId)->toArray();
                if ($crawlerCycle['ephemeral_data']) {
                    $done = json_decode($crawlerCycle['ephemeral_data'], true);
                    if (in_array($item, $done)) {
                        continue;
                    }
                }
            } else {
                if (in_array($item, $done)) {
                    continue;
                }
            }

            $invalid = json_decode(file_get_contents('invalid'), true);
            if (in_array($item, $invalid)) {
                continue;
            }

            $data = $this->currentTrading($item);

            if (empty($data)) {
                $invalid = json_decode(file_get_contents('invalid'), true);
                $invalid[] = $item;
                file_put_contents('invalid', json_encode($invalid));
                continue;
            }

            if ($data[1] != 0) {
                $save = [
                    'stock_code' => substr($item, 2, 6),
                    'year' => $year,
                    'quarter' => $season,
                    'date' => $data[30],
                    'opening_price' => $data[1],
                    'maximum_price' => $data[4],
                    'closing_price' => $data[3],
                    'minimum_price' => $data[5],
                    'trading_stocks' => $data[8],
                    'transaction_amount' => $data[9],
                ];

                Manager::table("stock_markets_{$year}_3")->insert($save);
            }
            $done[] = $item;
            $ephemeral = [
                'ephemeral_data' => json_encode($done)
            ];
            StockCrawlerCycle::query()->where(['id' => $crawlerCycleId])->update($ephemeral);
        }
    }


    public static function currentTrading($stockCode)
    {
        $client = new Client();
        try {
            $result = $client->request('GET', "http://hq.sinajs.cn/list={$stockCode}");
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
