<?php

namespace AloneFrame\tron\order;

use AloneFrame\tron\Wallet;
use AloneFrame\tron\helper\Method;

/**
 * 处理TRX交易记录
 * https://developers.tron.network/reference/get-transaction-info-by-account-address
 */
class Trx extends Common {
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
                $raw_data = $v['raw_data'] ?? [];
                $contract = $raw_data['contract'][0] ?? [];
                if (!empty($contract) && $contract['type'] == 'TransferContract') {
                    $parameter = $contract['parameter'] ?? [];
                    $value = $parameter['value'] ?? [];
                    if (!empty($balance = ($value['amount'] ?? ''))) {
                        $amount = Method::money($balance / pow(10, 6), 6);
                        //交易状态
                        $arr['type'] = strtolower(Method::getArr($v, 'ret.0.contractRet'));
                        //交易状态
                        $arr['status'] = ($arr['type'] == 'success' && $amount > 0.01);
                        //付款地址
                        $arr['from'] = Wallet::hexToAddress(Method::getArr($value, 'owner_address', ''));
                        //收款地址
                        $arr['to'] = Wallet::hexToAddress(Method::getArr($value, 'to_address', ''));
                        //交易数量
                        $arr['amount'] = $amount;
                        //in=转入,out=转出
                        $arr['mode'] = ((strtolower($arr['to']) == strtolower($this->order->address)) ? 'in' : 'out');
                        //交易哈希号
                        $arr['hash'] = Method::getArr($v, 'txID');
                        //交易时间
                        $arr['timestamp'] = Method::getArr($v, 'block_timestamp');
                        //交易时间
                        $arr['time'] = Method::unixTime($arr['timestamp']);
                        //交易时间
                        $arr['date'] = date("Y-m-d H:i:s", $arr['time']);
                        $res[] = $arr;
                    }
                }
            }
            $array['data'] = $res;
            $array['count'] = count($res);
        }
        return $array;
    }
}