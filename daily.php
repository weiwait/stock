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

    public function __construct()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->start = microtime(true);
//        $this->urls = [
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2018&jidu=1",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=1",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=2",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=3",
//            "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000631.phtml?year=2017&jidu=4",
//        ];
//        $this->stockCode('http://quote.eastmoney.com/stocklist.html');
//        $this->response = $this->$execute();
//         = is_array($data) ? json_encode($data) : $data;
    }

    public function __toString()
    {
        return $this->response;
    }

    public function stockCode($url)
    {
        $phpQuery = new QueryList();
        $rules = [
            'stockCode' => ['#quotesearch', 'html', '', function ($match) {
                $data = QueryList::getInstance()->html($match)->find('li')->texts()->toArray();
                $data = array_filter($data);
                foreach ($data as $key => $item) {
                    $item = trim($item, ')');
                    $item = explode('(', $item);
                    $data[$key] = ['name' => $item[0], 'code' => $item[1]];
                }
                usort($data, function ($first, $second) {
                    return $first['code'] <=> $second['code'];
                });
                Stock::query()->insert($data);
            }]
        ];
        $stockCodes = $phpQuery->get($url)->rules($rules)->removeHead()->encoding('UTF-8')->query()->getData()->all();
//        $stockCodes = $stockCodes[0]['stockCode'];
//        if (count($stockCodes) > 1000) {
//            file_put_contents(__DIR__ . '/stockCodes', json_encode($stockCodes));
//            $this->stockCodes = $stockCodes;
//        }
    }

    private function getProxy()
    {
        if (file_exists('proxy')) {
            $data = json_decode(file_get_contents('proxy'), true);
            if ($data['date'] === date('Y-m-d')) {
                $data['where'] += 1;
                file_put_contents('proxy', json_encode($data));
                return $data['proxy'][$data['where']]['ip:port'];
            }
        }

        $guzzle = new Client();
        $proxy = $guzzle->get('http://proxy.mimvp.com/api/fetch.php?orderid=860151130155752298&num=5000&ping_time=0.3&transfer_time=1&result_fields=1,2&http_type=1,2&result_format=json')->getBody()->getContents();
        $proxy = json_decode($proxy, true);

        $data = [
            'date' => date('Y-m-d'),
            'proxy' => $proxy['result'],
            'where' => 0
        ];

        file_put_contents('proxy', json_encode($data));
        return $proxy['result'][0]['ip:port'];
    }

    private function getHtml($url)
    {
        if ($this->counter > 5) {
            return '';
        }
        $guzzle = new Client();
        try {
            $this->counter++;
            return $guzzle->request('GET', $url, ['proxy' => $this->getProxy()])->getBody()->getContents();
        } catch (GuzzleException $e) {
            return $this->getHtml($url);
        }


    }

    public function single($url)
    {
        $pQuery = new QueryList();
        $guzzle = new Client();
        $rules = [
            'quarter' => ['#FundHoldSharesTable', 'html', '', function ($match) {
                try {
                    $query = QueryList::getInstance()->html($match);
                    $data = $query->find('div')->texts()->toArray();
                    $query->destruct();
                } catch (Exception $exception) {
                    return [];
                }
                array_splice($data, 0, 7);
                return $data;
            }],
        ];

        try {
            $this->counter = 0;
            $html = $this->getHtml($url);
            $data = $pQuery->html($html)->rules($rules)->query()->getData()->toArray();
            $pQuery->destruct();
        } catch (Exception $exception) {
            $data = [];
        }
        if ($data) {
            return $data[0]['quarter'];
        } else {
            return [];
        }
    }

    /**
     * @name CurlMulti multipleThread
     * @return array
     */
    public function multiple(Array $urls)
    {
        $pq = QueryList::getInstance();
        $pq->use(CurlMulti::class, 'multipleThread');
        $pq->rules([
            'quarter' => ['#FundHoldSharesTable', 'html', '', function ($match) {
                $data = QueryList::getInstance()->html($match)->find('div')->texts()->toArray();
                array_splice($data, 0, 7);
                return $data;
            }],
//            'stock' => ['.productviewcart', 'html', '', function ($match) {
//                $stock = QueryList::getInstance()->html($match)->find('button[title=Add to Cart]')->attrs('onclick');
//                $stock = array_reverse(explode(',', $stock))[0];
//                return intval(trim($stock, "'"));
//            }]
            /* @name curlMulti multipleThread */
        ])->multipleThread($urls)->success(function (QueryList $pq) {
            $this->data[] = ($pq->query()->getData()->first())['quarter'];
        })->start();
        $this->end = microtime(true);
//        echo 'executing: ' . round($this->end - $this->start, 3) . " seconds</br>";
        $data = [];
        foreach ($this->data as $item) {
            $data = array_merge($data, $item);
        }
        $data = array_chunk($data, 7);
        asort($data);
        $this->data = [];
//        $maximum = 0;
//        $minimum = 0;
//        foreach ($data as $oneDay) {
//            $maximum += $oneDay[2];
//            $minimum += $oneDay[4];
//        }
//        $total = count($data);
//        echo "maximumAverage: " . $maximum / $total . "\r";
//        echo "minimumAverage: " . $minimum / $total . "\r";
//        echo "persents: " . (($maximum / $total) - ($minimum / $total)) / ($minimum / $total) * 5000 . "\r";
//        print_r($data);die;
        return $data;
    }

    public function pickUpStock()
    {
        $year = date('Y');
        $month = date('m');
        $season = ceil((date('n'))/3);

        $stockCodes = Stock::query()->select(['code'])->get()->toArray();
        $stockCodes = array_column($stockCodes, 'code');

        $crawlerCycleId =  StockCrawlerCycle::query()->insertGetId(['year' => date('md')]);

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

            $url = "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/{$item}.phtml?year={$year}&jidu={$season}";
            $data = $this->single($url);

            if ($data) {
                $data = array_chunk($data, 7);
                asort($data);
                $value = array_pop($data);

                $data = [
                    'stock_code' => $item,
                    'year' => $year,
                    'quarter' => $season,
                    'date' => $value[0],
                    'opening_price' => $value[1],
                    'maximum_price' => $value[2],
                    'closing_price' => $value[3],
                    'minimum_price' => $value[4],
                    'trading_stocks' => $value[5],
                    'transaction_amount' => $value[6],
                ];

                if (date('md', strtotime($data['date'])) != $crawlerCycle['year']) {
                    continue;
                }

                Manager::table("stock_markets_{$year}")->insert($data);
            }
            $done[] = $item;
            $ephemeral = [
                'ephemeral_data' => json_encode($done)
            ];
                StockCrawlerCycle::query()->where(['id' => $crawlerCycleId])->update($ephemeral);
        }
    }
}


error_reporting(E_ALL);
ini_set('display_errors', 1);

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





