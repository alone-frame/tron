<?php

namespace AloneFrame\tron\helper;

use AloneElliptic\EC;

class Method {
    /**
     * 地址签名
     * @param string $address
     * @param int    $length
     * @return string
     */
    public static function parameter(string $address, int $length = 64): string {
        return str_pad($address, $length, "0", STR_PAD_LEFT);
    }

    /**
     * 私钥签名
     * @param string|int $data
     * @param string     $privateKey
     * @param bool       $type
     * @return string
     */
    public static function sign(string|int $data, string $privateKey, bool $type = true): string {
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($privateKey);
        $signature = $keyPair->sign($data);
        $sign = $signature->r->toString('hex');
        $sign .= $signature->s->toString('hex');
        $sign .= $type === true
            ? bin2hex(implode('', array_map('chr', [$signature->recoveryParam])))
            : str_pad(dechex($signature->recoveryParam), 2, '0', STR_PAD_LEFT);
        return $sign;
    }


    /**
     * 私钥签名 备用
     * @param string|int $data
     * @param string     $privateKey
     * @return string
     */
    public static function signs(string|int $data, string $privateKey): string {
        $ec = new EC('secp256k1');
        $sign = $ec->sign($data, $privateKey);
        return $sign->r->toString(16) . $sign->s->toString(16) . bin2hex(chr($sign->recoveryParam));
    }

    /**
     * 16进制 转换 金额
     * @param mixed $amount
     * @param int   $scale
     * @return string
     */
    public static function toAmount(mixed $amount, int $scale = 6): string {
        return dechex(bcmul($amount, pow(10, $scale), 0));
    }

    /**
     * 金额 转换 16进制
     * @param mixed $balance
     * @return string
     */
    public static function toBalance(mixed $balance): string {
        return base_convert($balance, 16, 10);
    }

    /**
     * 100000000 转换 100
     * @param string|int|float $data
     * @param int              $decimals
     * @param int              $scale
     * @return string
     */
    public static function balance(string|int|float $data, int $decimals = 2, int $scale = 6): string {
        return static::money(bcdiv($data, pow(10, $scale), $scale), $decimals);
    }

    /**
     * 100 转换 100000000
     * @param string|int|float $double
     * @return int
     */
    public static function amount(string|int|float $double): int {
        return (int) bcmul((string) $double, (string) 1e6, 0);
    }

    /**
     * 金额小数点
     * @param        $int
     * @param int    $decimals
     * @param string $separator
     * @param string $thousands
     * @return float
     */
    public static function money($int, int $decimals = 2, string $thousands = '', string $separator = '.'): float {
        return (float) number_format($int, $decimals, $separator, $thousands);
    }

    /**
     * 判断字符串是否json,返回array
     * @param mixed $data
     * @param bool  $type
     * @return mixed
     */
    public static function isJson(mixed $data, bool $type = true): mixed {
        $data = json_decode((is_string($data) ? ($data ?: '') : ''), $type);
        return (($data && is_object($data)) || (is_array($data) && $data)) ? $data : [];
    }

    /**
     * 通过a.b.c.d获取数组内容
     * @param array|null      $array   要取值的数组
     * @param string|null|int $key     支持aa.bb.cc.dd这样获取数组内容
     * @param mixed           $default 默认值
     * @param string          $symbol  自定符号
     * @return mixed
     */
    public static function getArr(array|null $array, string|null|int $key, mixed $default = null, string $symbol = '.'): mixed {
        if (isset($key)) {
            if (isset($array[$key])) {
                $array = $array[$key] ?: $default;
            } else {
                $symbol = $symbol ?: '.';
                $arr = explode($symbol, trim($key, $symbol));
                foreach ($arr as $v) {
                    if (isset($v) && isset($array[$v])) {
                        $array = $array[$v] ?: $default;
                    } else {
                        $array = $default;
                        break;
                    }
                }
            }
        }
        return $array;
    }

    /**
     * 13位时间转10位
     * @param string|int $time
     * @return float|int|string
     */
    public static function unixTime(string|int $time): float|int|string {
        return (int) (ceil($time / 1000));
    }

    /**
     * 生成13位时间
     * @param int|null|string $time
     * @param bool            $date
     * @return int
     */
    public static function getUnix(null|int|string $time = null, bool $date = false): int {
        if (!empty($time)) {
            return sprintf('%.6f', $date ? strtotime($time) : $time) * 1000;
        }
        [$t1, $t2] = explode(" ", microtime());
        return (int) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}