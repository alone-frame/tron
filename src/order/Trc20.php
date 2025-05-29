<?php

namespace AloneFrame\tron\order;

use AloneFrame\tron\helper\Method;

/**
 * 处理TRC20交易记录
 * https://developers.tron.network/reference/get-trc20-transaction-info-by-account-address
 */
class Trc20 extends Common {
    /**
     * 处理后的数据
     * @param int $page 获取几页数据,0=全部
     * @return array
     */
    public function get(int $page = 1): array {
        $array = $this->page($page);
        if ($array['count'] > 0) {
            $res = [];
            foreach (($array['data'] ?? []) as $v) {
                $arr = [];
                //交易状态
                $arr['type'] = strtolower(Method::getArr($v, 'type'));
                //交易状态
                $arr['status'] = $arr['type'] == 'transfer';
                //付款地址
                $arr['from'] = Method::getArr($v, 'from', '');
                //收款地址
                $arr['to'] = Method::getArr($v, 'to', '');
                //交易数量
                $arr['amount'] = Method::money(Method::getArr($v, 'value', 0) / pow(10, Method::getArr($v, 'token_info.decimals', 0)), 6);
                //交易时间
                $arr['timestamp'] = Method::getArr($v, 'block_timestamp');
                //交易时间
                $arr['time'] = Method::unixTime($arr['timestamp']);
                //交易时间
                $arr['date'] = date("Y-m-d H:i:s", $arr['time']);
                //in=转入,out=转出
                $arr['mode'] = ((strtolower($arr['to']) == strtolower($this->order->address)) ? 'in' : 'out');
                //交易哈希号
                $arr['hash'] = Method::getArr($v, 'transaction_id');
                $res[] = $arr;
            }
            $array['data'] = $res;
            $array['count'] = count($res);
        }
        return $array;
    }
}