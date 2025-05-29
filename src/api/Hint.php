<?php

namespace AloneFrame\tron\api;

use AloneFrame\tron\helper\Method;
use AloneFrame\tron\helper\ApiBody;

/**
 * 返回提示设置
 */
trait Hint {
    public string|array $lang     = ['en', 'cn'];
    public array        $language = [
        'cn' => [
            200  => '成功',
            5001 => '创建TRX交易失败',
            5002 => '创建TRX转帐失败',
            5003 => '获取TRX余额失败',
            5004 => 'TRX转帐失败',
            6001 => '创建TRC20交易失败',
            6002 => '创建TRC20转帐失败',
            6003 => '获取TRC20余额失败',
            6004 => '获取TRC20余额错误',
            6005 => 'TRC20转帐失败',
            6006 => 'TRC20转帐错误',
            7001 => '创建资源代理失败',
            7002 => '发送资源代理失败',
            7003 => '发送资源代理错误',
            7004 => '创建资源回收失败',
            7005 => '发送资源回收失败',
            7006 => '发送资源回收错误',
            8001 => '获取汇率失败',
            8002 => '获取资源信息失败',
            8003 => '获取账户信息失败',
            8004 => '获取账户信息错误',
            8005 => '获取交易详情失败',
            8006 => '获取交易信息失败',
        ],
        'en' => [
            200 => 'success'
        ]
    ];

    /**
     * @param array $arr
     * @return ApiBody
     */
    protected function come(array $arr): ApiBody {
        return new ApiBody($arr, function(ApiBody $self) {
            $self->code = !empty($self->code) ? $self->code : 200;
            $lang = is_array($this->lang) ? $this->lang : explode(',', $this->lang);
            if (empty($self->msg)) {
                foreach ($lang as $v) {
                    $msg = Method::getArr($this->language, $v . "." . $self->code);
                    if (!empty($msg)) {
                        $self->msg = $msg;
                        break;
                    }
                }
            }
            $self->msg = !empty($self->msg) ? $self->msg : ($self->code ? 'success' : 'error');
        });
    }
}