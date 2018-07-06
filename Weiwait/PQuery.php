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
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager;
use QL\Ext\CurlMulti;
use QL\QueryList;
use Symfony\Component\Config\Definition\Exception\Exception;

class PQuery
{
    private $response = '';
    private $urls = [];
    private $start = '';
    private $end = '';
    private $data = [];
    private $stockCodes = [];
    private $counter;

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
                if (empty($data['proxy'][$data['where']+1]['ip:port'])) {
                    $data['where'] = 0;
                } else {
                    $data['where'] += 1;
                }
                file_put_contents('proxy', json_encode($data));
                return $data['proxy'][$data['where']]['ip:port'];
            }
        }

        $guzzle = new Client();
        $proxy = $guzzle->get('http://proxy.mimvp.com/api/fetch.php?orderid=860151130155752298&num=5000&ping_time=0.3&transfer_time=1&result_fields=1,2&http_type=1,2&result_format=json')->getBody()->getContents();
        $proxy = json_decode($proxy, true);

        foreach ($proxy['result'] as $key => $item) {
            try {
                $guzzle->request('GET', 'http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/000911.phtml?year=2018&jidu=2', ['proxy' => $item['ip:port'], 'timeout' => 1, 'allow_redirects' => false]);
            } catch (\Exception $exception) {
                unset($proxy['result'][$key]);
            } catch (GuzzleException $e) {
                unset($proxy['result'][$key]);
            }
        }

        sort($proxy['result']);

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
        $guzzle = new Client();
        $pQuery = new QueryList();
        try {
            $html = $guzzle->request('GET', $url, ['proxy' => $this->getProxy(), 'timeout' => 3, 'allow_redirects' => false])->getBody()->getContents();
            if (!empty($html) || $pQuery->html(\phpQuery::newDocument($html))->find('#FundHoldSharesTable')) {
                return $html;
            } else {
                return $this->getHtml($url);
            }
        } catch (GuzzleException $e) {
            return $this->getHtml($url);
        }


    }

    public function single($url)
    {
        $pQuery = new QueryList();
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
        $year = 2018;
        $season = ceil((date('n'))/3);

//        $stockCodes = Stock::query()->select(['code'])->get()->toArray();
        $stockCodes = StockSz::query()->select(['code'])->get()->toArray();
        $stockCodes = array_column($stockCodes, 'code');
//        $codes = StockMarket::query()->select(['stock_code'])->get()->toArray();
//        $codes = array_column($codes, 'stock_code');
//        $stockCodes = array_diff($stockCodes, $codes);
//        print_r($stockCodes);die;
        $done = [];
        foreach ($stockCodes as $where => $item) {
//            $urls = [];
            if (!$done) {
                $crawlerCycle = StockCrawlerCycle::query()->find(1)->toArray();
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
            for ($i = $season; $i > 0; $i--) {
                $url = "http://money.finance.sina.com.cn/corp/go.php/vMS_MarketHistory/stockid/{$item}.phtml?year={$year}&jidu={$i}";
                $data = $this->single($url);
                if (!empty($data)) {
                    $data = array_chunk($data, 7);
                    asort($data);
                    foreach ($data as $key => $value) {
                        $data[$key] = [
                            'stock_code' => $item,
                            'year' => $year,
                            'quarter' => $i,
                            'date' => $value[0],
                            'opening_price' => $value[1],
                            'maximum_price' => $value[2],
                            'closing_price' => $value[3],
                            'minimum_price' => $value[4],
                            'trading_stocks' => $value[5],
                            'transaction_amount' => $value[6],
                        ];
                    }
//                    $result = StockMarket::query()->insert($data);
                    $result = Manager::table("stock_markets_{$year}_2")->insert($data);
                    if (!$result) {
                        file_put_contents(__DIR__ . '/', "code: {$item}, quarter: {$i}\r", FILE_APPEND);
                    }
                } else {
                    if (file_exists(__DIR__ . '/empty')) {
                        $empty = file_get_contents(__DIR__ . '/empty');
                        $empty = json_decode($empty, true);
                    } else {
                        $empty = [];
                    }
                    $empty[] = ['code' => $item, 'quarter' => $i];
                    $empty = json_encode($empty);
                    file_put_contents(__DIR__ . '/empty', $empty);
                }
            }
            $done[] = $item;
            $ephemeral = [
                'ephemeral_data' => json_encode($done)
            ];
            StockCrawlerCycle::query()->where(['id' => 1])->update($ephemeral);
            print_r("{$where}<>{$item}\t");
        }
    }
}
