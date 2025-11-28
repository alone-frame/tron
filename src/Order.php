<?php

namespace AloneFrame\tron;

use AloneFrame\tron\order\Trx;
use AloneFrame\tron\order\Trc20;
use AloneFrame\tron\helper\Curl;
use AloneFrame\tron\helper\Method;

/**
 * 获取TRC20交易记录
 * https://developers.tron.network/reference/get-trc20-transaction-info-by-account-address
 * 获取TRX交易记录
 * https://developers.tron.network/reference/get-transaction-info-by-account-address
 */
class Order {
    public Curl $curl;
    //订单域名
    public string $order = "https://tronscan.org";
    //接口域名
    public string $url = "https://api.trongrid.io";
    //合约地址
    public string $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    //查询地址
    public string $address = '';
    //查询参数
    public array $param = [];
    //接口密钥
    public array|string|null $key = null;
    //默认参数
    public array $def = [
        //每页交易数 20-200
        'limit'         => 200,
        //交易类型 true=确认的交易,false=未确认的交易,null=全部交易
        'confirmed'     => null,
        //收入记录 true=是,false=否,null=默认
        'only_to'       => null,
        //支出记录 true=是,false=否,null=默认
        'only_from'     => null,
        //交易开始时间 null默认
        'min_timestamp' => null,
        //交易结束时间 null默认
        'max_timestamp' => null,
        //上一页返回的最后一笔交易的指纹；在使用它时，其他参数和过滤器应保持不变
        'fingerprint'   => null,
        //排序asc | desc
        'order_by'      => null
    ];

    /**
     * 设置key,array随机使用
     * @param array|string|null $key
     */
    public function __construct(array|string|null $key = null) {
        $this->key = $key;
    }

    /**
     * 设置查询地址
     * @param string $address
     * @return $this
     */
    public static function address(string $address): static {
        $self = new static();
        $self->address = $address;
        return $self;
    }

    /**
     * 设置查询地址
     * @param string $address
     * @return $this
     */
    public function wallet(string $address): static {
        $this->address = $address;
        return $this;
    }

    /**
     * 设置key,array随机使用
     * @param string|array $key
     * @return $this
     */
    public function key(string|array $key): static {
        $this->key = $key;
        return $this;
    }

    /**
     * 每页交易数量，默认20，最大200
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static {
        $this->def['limit'] = $limit;
        return $this;
    }

    /**
     * true=收入记录，false=支出记录，null=全部记录
     * @param bool|null $type
     * @return $this
     */
    public function type(bool|null $type = null): static {
        if ($type === true) {
            $this->def['only_to'] = true;
        } elseif ($type === false) {
            $this->def['only_from'] = true;
        }
        return $this;
    }

    /**
     * 交易时间范围 null默认
     * BJ utf+8 Y-m-d H:i:s
     * @param string|null $top
     * @param string|null $end
     * @return $this
     */
    public function time(string|null $top, string|null $end): static {
        $this->def['min_timestamp'] = $top;
        $this->def['max_timestamp'] = $end;
        return $this;
    }

    /**
     * true=降排序,时间大的在前
     * false=升排序,时间小的在前
     * @param bool $desc
     * @return $this
     */
    public function by(bool $desc = true): static {
        $this->def['order_by'] = 'block_timestamp,' . ($desc ? 'desc' : 'asc');
        return $this;
    }

    /**
     * 交易类型 true=确认的交易,false=未确认的交易,null=全部交易
     * @param bool|null $confirm
     * @return $this
     */
    public function confirm(bool|null $confirm = true): static {
        $this->def['confirmed'] = $confirm;
        return $this;
    }

    /**
     * 上一页返回的最后一笔交易的指纹；在使用它时，其他参数和过滤器应保持不变
     * @param string $print
     * @return $this
     */
    public function print(string $print): static {
        $this->def['fingerprint'] = $print;
        return $this;
    }

    /**
     * 执行获取Trc20记录
     * @return Trc20
     */
    public function trc20(): Trc20 {
        $this->query(['contract_address' => $this->contract_address]);
        $this->curl("v1/accounts/" . $this->address . "/transactions/trc20");
        return new Trc20($this);
    }

    /**
     * 执行获取Trx记录
     * true=正常和内部交易。false=仅应用于正常交易 null=默认
     * @param bool|null $search_internal
     * @return Trx
     */
    public function trx(bool|null $search_internal = null): Trx {
        $this->query(is_null($search_internal) ? [] : ['search_internal' => $search_internal]);
        $this->curl("v1/accounts/" . $this->address . "/transactions");
        return new Trx($this);
    }

    /**
     * curl get请求
     * @param string $path
     * @param array  $config
     * @return $this
     */
    protected function curl(string $path, array $config = []): static {
        if (!empty($this->key)) {
            $apiKey = is_array($this->key) ? $this->key : explode(',', $this->key);
            $header["TRON-PRO-API-KEY"] = $apiKey[array_rand($apiKey)];
        }
        $this->curl = Curl::send(array_merge([
            'url'    => $this->url,
            'path'   => $path,
            'mode'   => 'get',
            'query'  => $this->param,
            'header' => $header ?? []
        ], $config));
        return $this;
    }

    /**
     * 处理参数
     * @param array $param
     * @return $this
     */
    protected function query(array $param = []): static {
        foreach ($this->def as $k => $v) {
            if (!is_null($v)) {
                switch ($k) {
                    case "limit":
                        $this->param[$k] = max(min($v, 200), 20);
                        break;
                    case "confirm":
                        $this->param[$v ? 'only_confirmed' : 'only_unconfirmed'] = true;
                        break;
                    case "min_timestamp":
                    case "max_timestamp":
                        if (!empty($v)) {
                            $this->param[$k] = Method::getUnix($v, true);
                        }
                        break;
                    default:
                        $this->param[$k] = $v;
                        break;
                }
            }
        }
        $this->param = array_merge($this->param, $param);
        return $this;
    }
}