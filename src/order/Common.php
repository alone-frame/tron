<?php

namespace AloneFrame\tron\order;

use AloneFrame\tron\Order;
use AloneFrame\tron\helper\Curl;

class Common {
    public Order $order;
    public Curl  $curl;
    public array $array = [];

    /**
     * @param Order $order
     */
    public function __construct(Order $order) {
        $this->order = $order;
        $this->curl = $this->order->curl;
    }

    /**
     * 可分页数据
     * @param int $page 获取几页数据,0=全部
     * @return array
     */
    public function page(int $page = 1): array {
        $array = $this->curl->getArray();
        $res['data'] = ($array['data'] ?? []);
        $hits = 1;
        $curr = 0;
        while (true) {
            ++$curr;
            $res['next'] = ($array['meta']['links']['next'] ?? '');
            if (empty($res['next']) || $page == 1 || $curr == $page || empty($array['success'] ?? '')) {
                break;
            }
            ++$hits;
            $array = $this->curl($res['next'])->getArray();
            $res['data'] = array_merge($res['data'], $array['data'] ?? []);
        }
        $res['page'] = $hits;
        $res['count'] = count($res['data']);
        return $res;
    }

    /**
     * 原样数据
     * @param bool $json 是否返回数组
     * @return int|string|array
     */
    public function body(bool $json = true): int|string|array {
        return $json ? $this->curl->getBody() : $this->curl->getArray();
    }

    /**
     * @param string $url
     * @param array  $config
     * @return Curl
     */
    protected function curl(string $url, array $config = []): Curl {
        return Curl::send(array_merge(['url' => $url, 'mode' => 'get'], $config));
    }
}