<?php

namespace AloneFrame\tron\api;

use AloneFrame\tron\Wallet;
use AloneFrame\tron\helper\Method;
use AloneFrame\tron\helper\ApiBody;

/**
 * Trc20
 */
trait Trc20 {
    /**
     * 获取Trc20余额
     * https://developers.tron.network/docs/trc20-contract-interaction#transferfrom
     * @param string $address 钱包地址
     * @return ApiBody
     */
    public function getTrc20(string $address): ApiBody {
        $post['function_selector'] = 'balanceOf(address)';
        $post['owner_address'] = Wallet::addressToHex($address);
        $post['parameter'] = Method::parameter($post['owner_address']);
        $post['contract_address'] = Wallet::addressToHex(self::$TRON_CONTRACT_ADDRESS);
        $this->post('wallet/triggersmartcontract', $post);
        $array = $this->curl->getArray();
        if (isset($array['constant_result'][0])) {
            return $this->come(['data' => Method::balance(Method::toBalance($array['constant_result'][0]), 6), 'body' => $array]);
        }
        if (isset($array['result']['code'])) {
            return $this->come(['body' => $array, 'code' => 6003, 'data' => hex2bin(Method::getArr($array, 'result.message'))]);
        }
        return $this->come(['body' => $array, 'code' => 6004]);
    }

    /**
     * TRC20转帐
     * https://developers.tron.network/reference/broadcasttransaction
     * @param string           $address    收款帐号
     * @param string|int|float $amount     交易数量
     * @param string           $privateKey 转帐私钥
     * @param mixed            $message    备注
     * @return ApiBody
     */
    public function trc20Send(string $address, string|int|float $amount, string $privateKey, mixed $message = ''): ApiBody {
        $res = $this->trc20Create($address, $amount, $privateKey, $message);
        if ($res->code == 200 && $res->data) {
            $res->body['signature'] = [Method::sign($res->data, $privateKey)];
            $this->post('wallet/broadcasttransaction', $res->body);
            $array = $this->curl->getArray();
            if (!empty($message = Method::getArr($array, 'message'))) {
                return $this->come(['body' => $array, 'code' => 6005, 'data' => hex2bin($message)]);
            }
            $txId = Method::getArr($array, 'txid', Method::getArr($array, 'txID'));
            if (!empty($txId)) {
                return $this->come(['data' => $txId, 'body' => $array]);
            }
            return $this->come(['body' => $array, 'code' => 6006]);
        }
        return $res;
    }

    /**
     * 创建TRC20转账交易
     * https://developers.tron.network/docs/trc20-contract-interaction#transferfrom
     * @param string           $address    收款帐号
     * @param string|int|float $amount     交易数量
     * @param string           $privateKey 转帐私钥
     * @param mixed            $message    备注
     * @return ApiBody
     */
    public function trc20Create(string $address, string|int|float $amount, string $privateKey, mixed $message = ''): ApiBody {
        $post['call_value'] = 0;
        $post['consume_user_resource_percent'] = 0;
        $post['fee_limit'] = bcmul('1000', '1000000');//本次调用的最大trx消耗
        $post['function_selector'] = 'transfer(address,uint256)';
        $post['owner_address'] = Wallet::getPrivateWallet($privateKey)->addressHex;
        $post['contract_address'] = Wallet::addressToHex(self::$TRON_CONTRACT_ADDRESS);
        $post['parameter'] = Method::parameter(Wallet::addressToHex($address)) . Method::parameter(Method::toAmount($amount));
        if (!is_null($message)) {
            $post['extra_data'] = bin2hex($message);
        }
        $this->post('wallet/triggersmartcontract', $post);
        $array = $this->curl->getArray();
        if (isset($array['result']['code'])) {
            return $this->come(['body' => $array, 'code' => 6001, 'data' => hex2bin(Method::getArr($array, 'result.message'))]);
        }
        $txId = Method::getArr($array, 'transaction.txID', Method::getArr($array, 'transaction.txid'));
        if (!empty($txId)) {
            return $this->come(['data' => $txId, 'body' => $array['transaction']]);
        }
        return $this->come(['body' => $array, 'code' => 6002]);
    }
}