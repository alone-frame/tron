<?php

namespace AloneFrame\tron\api;

use AloneFrame\tron\Wallet;
use AloneFrame\tron\helper\Method;
use AloneFrame\tron\helper\ApiBody;

/**
 * 资源代理和回收
 * Delegate
 */
trait Delegate {
    /**
     * 查看已经代理的资源
     * https://developers.tron.network/reference/getdelegatedresourceaccountindexv2-1
     * @param string $address
     * @return ApiBody
     */
    public function getDelegate(string $address): ApiBody {
        $this->post('wallet/broadcasttransaction', ['value' => Wallet::addressToHex($address)]);
        $array = $this->curl->getArray();
        return $this->come(['body' => $array]);
    }

    /**
     * 资源回收
     * https://developers.tron.network/reference/undelegateresource-1
     * @param string           $address    回收地址
     * @param string|int|float $amount     交易数量
     * @param string           $privateKey 转帐私钥
     * @param string           $resource   资源类型 'BANDWIDTH' or 'ENERGY'
     * @return ApiBody
     */
    public function delegateDel(string $address, string|int|float $amount, string $privateKey, string $resource = 'ENERGY'): ApiBody {
        $post['resource'] = $resource;
        $post['balance'] = Method::amount($amount);
        $post['receiver_address'] = Wallet::addressToHex($address);
        $post['owner_address'] = Wallet::getPrivateWallet($privateKey)->addressHex;
        $this->post('wallet/undelegateresource', $post);
        $res = $this->curl->getArray();
        if (!isset($res['txID'])) {
            return $this->come(['body' => $res, 'code' => 7004]);
        }
        $res['signature'] = [Method::sign($res['txID'], $privateKey)];
        $this->post('wallet/broadcasttransaction', $res);
        $array = $this->curl->getArray();
        if (!empty($message = Method::getArr($array, 'message'))) {
            return $this->come(['body' => $array, 'data' => hex2bin($message), 'code' => 7005]);
        }
        $txId = Method::getArr($array, 'txid', Method::getArr($array, 'txID'));
        if (!empty($txId)) {
            return $this->come(['data' => $txId, 'body' => $array]);
        }
        return $this->come(['body' => $array, 'code' => 7006]);
    }

    /**
     * 资源代理
     * https://developers.tron.network/reference/delegateresource-1
     * @param string           $address     接收地址
     * @param string|int|float $amount      交易数量
     * @param string           $privateKey  转帐私钥
     * @param string           $resource    资源类型 'BANDWIDTH' or 'ENERGY'
     * @param bool             $lock        是否锁定资源委托，true表示锁定委托，在lock_period指定的时间内委托不能被取消，false表示未锁定，资源委托可随时取消
     * @param int              $lock_period 锁定周期(要除3秒)，单位为区块间隔（3秒）。只有当锁定为真时，此字段有效。如果代表锁定周期为1天，则lock_period为28800
     * @return ApiBody
     */
    public function delegateSend(string $address, string|int|float $amount, string $privateKey, string $resource = 'ENERGY', bool $lock = false, int $lock_period = 0): ApiBody {
        $post['resource'] = $resource;
        $post['balance'] = Method::amount($amount);
        $post['receiver_address'] = Wallet::addressToHex($address);
        $post['owner_address'] = Wallet::getPrivateWallet($privateKey)->addressHex;
        if ($lock) {
            $post['lock'] = $lock;
            $post['lock_period'] = $lock_period;
        }
        $this->post('wallet/delegateresource', $post);
        $res = $this->curl->getArray();
        if (!isset($res['txID'])) {
            return $this->come(['body' => $res, 'code' => 7001]);
        }
        $res['signature'] = [Method::sign($res['txID'], $privateKey)];
        $this->post('wallet/broadcasttransaction', $res);
        $array = $this->curl->getArray();
        if (!empty($message = Method::getArr($array, 'message'))) {
            return $this->come(['body' => $array, 'data' => hex2bin($message), 'code' => 7002]);
        }
        $txId = Method::getArr($array, 'txid', Method::getArr($array, 'txID'));
        if (!empty($txId)) {
            return $this->come(['data' => $txId, 'body' => $array]);
        }
        return $this->come(['body' => $array, 'code' => 7003]);
    }
}