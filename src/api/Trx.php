<?php

namespace AloneFrame\tron\api;

use AloneFrame\tron\Wallet;
use AloneFrame\tron\helper\Method;
use AloneFrame\tron\helper\ApiBody;

/**
 * Trx
 */
trait Trx {
    /**
     * 获取TRX余额
     * https://developers.tron.network/reference/walletsolidity-getaccount
     * @param string $address 钱包地址
     * @return ApiBody
     */
    public function getTrx(string $address): ApiBody {
        $post = ['address' => Wallet::addressToHex($address)];
        $this->post('walletsolidity/getaccount', $post);
        $array = $this->curl->getArray();
        if (isset($array['address']) && isset($array['balance'])) {
            return $this->come(['body' => $array, 'data' => Method::balance($array['balance'], 6)]);
        }
        return $this->come(['body' => $array, 'code' => 5003]);
    }

    /**
     * TRX转帐
     * https://developers.tron.network/reference/broadcasttransaction
     * @param string           $address    收款帐号
     * @param string|int|float $amount     交易数量
     * @param string           $privateKey 转帐私钥
     * @param mixed            $message    备注
     * @return ApiBody
     */
    public function trxSend(string $address, string|int|float $amount, string $privateKey, mixed $message = ''): ApiBody {
        $res = $this->trxCreate($address, $amount, $privateKey, $message);
        if ($res->code == 200) {
            $res->body['signature'] = [Method::sign($res->data, $privateKey)];
            $this->post('wallet/broadcasttransaction', $res->body);
            $array = $this->curl->getArray();
            $txID = Method::getArr($array, 'txid', Method::getArr($array, 'txID'));
            if ((!empty($txID) && ($array['result'] ?? ''))) {
                return $this->come(['data' => $txID, 'body' => $array]);
            }
            return $this->come(['body' => $array, 'code' => 5004]);
        }
        return $res;
    }

    /**
     * 创建TRX转账交易
     * https://developers.tron.network/reference/createtransaction
     * @param string           $address    收款帐号
     * @param string|int|float $amount     交易数量
     * @param string           $privateKey 转帐私钥
     * @param mixed            $message    备注
     * @return ApiBody
     */
    public function trxCreate(string $address, string|int|float $amount, string $privateKey, mixed $message = ''): ApiBody {
        $post = [
            'to_address'    => Wallet::addressToHex($address),
            'owner_address' => Wallet::getPrivateWallet($privateKey)->addressHex,
            'amount'        => Method::amount($amount)
        ];
        if (!empty($message)) {
            $post['extra_data'] = bin2hex($message);
        }
        $this->post('wallet/createtransaction', $post);
        $array = $this->curl->getArray();
        if (!empty($error = Method::getArr($array, 'Error', Method::getArr($array, 'error')))) {
            return $this->come(['body' => $array, 'data' => $error, 'code' => 5001]);
        }
        $txId = Method::getArr($array, 'txid', Method::getArr($array, 'txID'));
        if (!empty($txId)) {
            return $this->come(['body' => $array, 'data' => $txId]);
        }
        return $this->come(['body' => $array, 'data' => $error, 'code' => 5002]);
    }
}