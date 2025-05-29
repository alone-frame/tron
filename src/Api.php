<?php

namespace AloneFrame\tron;

use AloneFrame\tron\helper\Curl;

/**
 * tron api
 * https://tronscan.org/#/tools/tronstation
 */
class Api {
    //资源代理和回收
    use api\Delegate;

    //查询信息
    use api\Get;

    //错误提示
    use api\Hint;

    //Trc20
    use api\Trc20;

    //Trx类
    use api\Trx;

    /**
     * @var Curl
     */
    public Curl $curl;

    //波场合约地址
    public static string $TRON_CONTRACT_ADDRESS = "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t";

    //接口网址
    public string $url = 'https://api.trongrid.io';

    //接口密钥
    public array|string $key = '';

    /**
     * 接口密钥申请地址 https://www.trongrid.io/
     * https://developers.tron.network/reference/select-network
     * @param array|string $key 接口密钥
     * @param string       $url 接口网址
     */
    public function __construct(array|string $key = [], string $url = '') {
        $this->key = !empty($key) ? $key : $this->key;
        $this->url = !empty($url) ? $url : $this->url;
        $this->key = is_array($this->key) ? $this->key : [$this->key];
    }

    /**
     * post请求
     * @param string $path
     * @param array  $body
     * @param array  $config
     * @return $this
     */
    protected function post(string $path, array $body = [], array $config = []): static {
        $this->curl = Curl::send(array_merge([
            'url'    => $this->url,
            'path'   => $path,
            'body'   => $body,
            'mode'   => 'post',
            'format' => 'json',
            'header' => (!empty($this->key) ? ['TRON-PRO-API-KEY' => $this->key[array_rand($this->key)]] : [])
        ], $config));
        return $this;
    }

    /**
     * get请求
     * @param string $path
     * @param array  $config
     * @return $this
     */
    protected function get(string $path, array $config = []): static {
        $this->curl = Curl::send(array_merge([
            'url'    => $this->url,
            'path'   => $path,
            'mode'   => 'get',
            'header' => (!empty($this->key) ? ['TRON-PRO-API-KEY' => $this->key[array_rand($this->key)]] : [])
        ], $config));
        return $this;
    }
}