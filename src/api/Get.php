<?php

namespace AloneFrame\tron\api;

use AloneFrame\tron\Wallet;
use AloneFrame\tron\helper\Curl;
use AloneFrame\tron\helper\Method;
use AloneFrame\tron\helper\ApiBody;

/**
 * 查询信息类
 */
trait Get {
    /**
     * 获取交易信息
     * https://developers.tron.network/reference/gettransactioninfobyid
     * @param string $txId
     * @return ApiBody
     */
    public function orderInfo(string $txId): ApiBody {
        $this->post('/wallet/gettransactioninfobyid', ['value' => $txId]);
        $array = $this->curl->getArray();
        if (isset($array['id'])) {
            return $this->come(['body' => $array, 'data' => $array['id']]);
        }
        return $this->come(['body' => $array, 'code' => 8006]);
    }

    /**
     * 获取交易详情
     * https://developers.tron.network/reference/wallet-gettransactionbyid
     * @param string $txId
     * @return ApiBody
     */
    public function orderShow(string $txId): ApiBody {
        $this->post('/wallet/gettransactionbyid', ['value' => $txId]);
        $array = $this->curl->getArray();
        if (isset($array['txID'])) {
            return $this->come(['body' => $array, 'data' => $array['txID']]);
        }
        return $this->come(['body' => $array, 'code' => 8005]);
    }

    /**
     * 获取账户信息
     * https://developers.tron.network/reference/get-account-info-by-address
     * @param string $address
     * @return ApiBody
     */
    public function getAccount(string $address): ApiBody {
        $url = "v1/accounts/{$address}?only_confirmed=true";
        $this->get($url);
        $array = $this->curl->getArray();
        if (!empty($error = Method::getArr($array, 'Error', Method::getArr($array, 'error')))) {
            return $this->come(['body' => $array, 'data' => $error, 'code' => 8003]);
        }
        if (isset($array['data']) && !empty(($array['success'] ?? ''))) {
            return $this->come(['body' => $array['data'][0], 'data' => $array['data'][0]['address']]);
        }
        return $this->come(['body' => $array, 'code' => 8004]);
    }

    /**
     * 查询账户的资源信息（带宽、能量等）
     * https://developers.tron.network/reference/getaccountresource
     * @param string $address
     * @return ApiBody
     */
    public function getResource(string $address): ApiBody {
        $this->post('wallet/getaccountresource', ['address' => Wallet::addressToHex($address)]);
        $array = $this->curl->getArray();
        if (isset($array['freeNetLimit'])) {
            return $this->come(['body' => $array, 'data' => $array['freeNetLimit'] - $array['freeNetUsed'] ?? 0]);
        }
        return $this->come(['body' => $array, 'code' => 8002]);
    }

    /**
     * 实时汇率 USDT TRX CNY
     * https://coinyep.com/api/v1/?from=USDT&to=CNY&lang=zh&format=json
     * https://coinyep.com/api/v1/?from=TRX&to=CNY&lang=zh&format=json
     * https://coinyep.com/api/v1/?from=USDT&to=TRX&lang=zh&format=json
     * https://coinyep.com/api/v1/?from=TRX&to=USDT&lang=zh&format=json
     * @param string $from USDT TRX CNY
     * @param string $to
     * @return ApiBody
     */
    public function getExchange(string $from = 'USDT', string $to = 'CNY'): ApiBody {
        $curl = Curl::send([
            'url'  => "https://coinyep.com/api/v1/?from={$from}&to={$to}&lang=zh&format=json",
            'mode' => 'get'
        ]);
        $array = $curl->getArray();
        if (isset($array['price_change'])) {
            return $this->come(['body' => $array, 'data' => $array['price_change']]);
        }
        return $this->come(['body' => $array, 'code' => 8001]);
    }
}