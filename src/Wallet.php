<?php

namespace AloneFrame\tron;

use AloneElliptic\EC;
use AloneFrame\tron\helper\WalletBody;

/**
 * tron钱包生成转换类
 */
class Wallet {
    /**
     * 生成钱包信息 不带助记词
     * @return WalletBody
     */
    public static function privateWallet(): WalletBody {
        return static::getPrivateWallet(static::privateKey());
    }

    /**
     * 生成钱包信息 带助记词
     * @param int $numWords 助记词数量
     * @return WalletBody
     */
    public static function mnemonicWallet(int $numWords = 12): WalletBody {
        return static::getMnemonicWallet(static::mnemonic($numWords));
    }

    /**
     * 通过 私钥 生成钱包
     * @param string $private
     * @return WalletBody
     */
    public static function getPrivateWallet(string $private): WalletBody {
        $obj = new WalletBody(['privateKey' => $private]);
        $obj->publicKey = static::privateKeyToPublicKey($obj->privateKey);
        $obj->addressHex = static::publicKeyToAddressHex($obj->publicKey);
        $obj->address = static::hexToAddress($obj->addressHex);
        return $obj;
    }

    /**
     * 通过 助记词 生成钱包
     * @param string $mnemonic 助记词
     * @return WalletBody
     */
    public static function getMnemonicWallet(string $mnemonic): WalletBody {
        $obj = new WalletBody(['mnemonic' => $mnemonic]);
        $obj->privateKey = static::mnemonicToPrivateKey($obj->mnemonic);
        $obj->publicKey = static::privateKeyToPublicKey($obj->privateKey);
        $obj->addressHex = static::publicKeyToAddressHex($obj->publicKey);
        $obj->address = static::hexToAddress($obj->addressHex);
        return $obj;
    }

    /**
     * 验证TRC地址是否有效
     */
    public static function verifyAddress(string $address): bool {
        if (preg_match('/^T[A-HJ-NP-Za-km-z1-9]{33}$/', $address)) {
            $hex = static::addressToHex($address);
            return $hex && static::hexToAddress($hex) == $address;
        }
        return false;
    }

    /**
     * 钱包地址 转换 hex钱包地址
     * @param string $address
     * @return string
     */
    public static function addressToHex(string $address): string {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num = '0';
        // Base58 解码
        for ($i = 0, $len = strlen($address); $i < $len; $i++) {
            $num = bcmul($num, '58');
            $num = bcadd($num, (string) strpos($alphabet, $address[$i]));
        }
        // 转换为 Hex
        $hex = '';
        while (bccomp($num, '0') > 0) {
            $hex = str_pad(dechex(bcmod($num, '256')), 2, '0', STR_PAD_LEFT) . $hex;
            $num = bcdiv($num, '256', 0);
        }
        // 处理前导零
        for ($i = 0; $i < strlen($address) && $address[$i] === '1'; $i++) {
            $hex = '00' . $hex; // 每个前导 '1' 对应两个 '00'
        }
        // 确保地址长度为 40 个字符（不包括校验码）
        $hex = str_pad($hex, 40, '0', STR_PAD_LEFT);
        // 添加校验码
        $hash0 = hash('sha256', hex2bin($hex));
        $hash1 = hash('sha256', hex2bin($hash0));
        $checksum = substr($hash1, 0, 8);
        // 拼接 Hex 和校验码
        $fullHex = $hex . $checksum;
        // 确保返回的 Hex 地址总长度为 42 个字符
        return substr($fullHex, 0, 42); // 只取前 42 个字符
    }

    /**
     * hex钱包地址 转换 钱包地址
     * @param string $hexAddress
     * @return string
     */
    public static function hexToAddress(string $hexAddress): string {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        // 移除 0x 前缀（如果有）
        $hexAddress = str_replace('0x', '', $hexAddress);
        // 计算地址的 SHA256 哈希（用于校验码）
        $hash0 = hash('sha256', hex2bin($hexAddress));
        $hash1 = hash('sha256', hex2bin($hash0));
        // 取前 4 字节作为校验码
        $checksum = substr($hash1, 0, 8);
        // 拼接原始地址和校验码
        $fullHex = $hexAddress . $checksum;
        // 转换为十进制
        $num = '0';
        for ($i = 0, $len = strlen($fullHex); $i < $len; $i += 2) {
            $num = bcmul($num, '256');
            $num = bcadd($num, (string) hexdec(substr($fullHex, $i, 2)));
        }
        // 进行 Base58 编码
        $base58 = '';
        while (bccomp($num, '0') > 0) {
            $mod = bcmod($num, '58');
            $base58 = $alphabet[(int) $mod] . $base58;
            $num = bcdiv($num, '58', 0);
        }
        // 处理前导 0
        for ($i = 0; $i < strlen($fullHex) && substr($fullHex, $i, 2) === '00'; $i += 2) {
            $base58 = '1' . $base58;
        }
        return $base58;
    }

    /**
     * 公钥 转换 hex钱包地址
     * @param string $pubKeyHex
     * @param string $prefix
     * @return string
     */
    public static function publicKeyToAddressHex(string $pubKeyHex, string $prefix = "41"): string {
        $pubKeyBin = hex2bin($pubKeyHex);
        $pubKeyBin = strlen($pubKeyBin) == 65 ? substr($pubKeyBin, 1) : $pubKeyBin;
        $beefcake = function(&$st) {
            $beefcake_rotc = [1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 2, 14, 27, 41, 56, 8, 25, 43, 62, 18, 39, 61, 20, 44];
            $beefcake_piling = [10, 7, 11, 17, 18, 3, 5, 16, 8, 21, 24, 4, 15, 23, 19, 13, 12, 2, 20, 14, 22, 9, 6, 1];
            $beefcake_red = [
                [0x00000000, 0x00000001], [0x00000000, 0x00008082], [0x80000000, 0x0000808a], [0x80000000, 0x80008000],
                [0x00000000, 0x0000808b], [0x00000000, 0x80000001], [0x80000000, 0x80008081], [0x80000000, 0x00008009],
                [0x00000000, 0x0000008a], [0x00000000, 0x00000088], [0x00000000, 0x80008009], [0x00000000, 0x8000000a],
                [0x00000000, 0x8000808b], [0x80000000, 0x0000008b], [0x80000000, 0x00008089], [0x80000000, 0x00008003],
                [0x80000000, 0x00008002], [0x80000000, 0x00000080], [0x00000000, 0x0000800a], [0x80000000, 0x8000000a],
                [0x80000000, 0x80008081], [0x80000000, 0x00008080], [0x00000000, 0x80000001], [0x80000000, 0x80008008]
            ];
            $bc = [];
            for ($round = 0; $round < 24; $round++) {
                for ($i = 0; $i < 5; $i++) {
                    $bc[$i] = [
                        $st[$i][0] ^ $st[$i + 5][0] ^ $st[$i + 10][0] ^ $st[$i + 15][0] ^ $st[$i + 20][0],
                        $st[$i][1] ^ $st[$i + 5][1] ^ $st[$i + 10][1] ^ $st[$i + 15][1] ^ $st[$i + 20][1]
                    ];
                }
                for ($i = 0; $i < 5; $i++) {
                    $t = [
                        $bc[($i + 4) % 5][0] ^ (($bc[($i + 1) % 5][0] << 1) | ($bc[($i + 1) % 5][1] >> 31)) & (0xFFFFFFFF),
                        $bc[($i + 4) % 5][1] ^ (($bc[($i + 1) % 5][1] << 1) | ($bc[($i + 1) % 5][0] >> 31)) & (0xFFFFFFFF)
                    ];

                    for ($j = 0; $j < 25; $j += 5) {
                        $st[$j + $i] = [
                            $st[$j + $i][0] ^ $t[0],
                            $st[$j + $i][1] ^ $t[1]
                        ];
                    }
                }
                $t = $st[1];
                for ($i = 0; $i < 24; $i++) {
                    $j = $beefcake_piling[$i];
                    $bc[0] = $st[$j];
                    $n = $beefcake_rotc[$i];
                    $hi = $t[0];
                    $lo = $t[1];
                    if ($n >= 32) {
                        $n -= 32;
                        $hi = $t[1];
                        $lo = $t[0];
                    }
                    $st[$j] = [
                        (($hi << $n) | ($lo >> (32 - $n))) & (0xFFFFFFFF),
                        (($lo << $n) | ($hi >> (32 - $n))) & (0xFFFFFFFF)
                    ];

                    $t = $bc[0];
                }
                for ($j = 0; $j < 25; $j += 5) {
                    for ($i = 0; $i < 5; $i++) {
                        $bc[$i] = $st[$j + $i];
                    }
                    for ($i = 0; $i < 5; $i++) {
                        $st[$j + $i] = [
                            $st[$j + $i][0] ^ ~$bc[($i + 1) % 5][0] & $bc[($i + 2) % 5][0],
                            $st[$j + $i][1] ^ ~$bc[($i + 1) % 5][1] & $bc[($i + 2) % 5][1]
                        ];
                    }
                }
                $st[0] = [
                    $st[0][0] ^ $beefcake_red[$round][0],
                    $st[0][1] ^ $beefcake_red[$round][1]
                ];
            }
        };
        $encoding = "8bit";
        $capacity = 256 / 8;
        $inlet = mb_strlen($pubKeyBin, $encoding);
        $rsi = 200 - 2 * $capacity;
        $resize = $rsi / 8;
        $st = array_fill(0, 25, [0, 0]);
        for ($in_t = 0; $inlet >= $rsi; $inlet -= $rsi, $in_t += $rsi) {
            for ($i = 0; $i < $resize; $i++) {
                $t = unpack('V*', mb_substr($pubKeyBin, $i * 8 + $in_t, 8, $encoding));
                $st[$i] = [
                    $st[$i][0] ^ $t[2],
                    $st[$i][1] ^ $t[1]
                ];
            }
            $beefcake($st);
        }
        $temp = mb_substr($pubKeyBin, $in_t, $inlet, $encoding);
        $temp = str_pad($temp, $rsi, "\x0");
        $temp[$inlet] = chr(0x01);
        $temp[$rsi - 1] = chr(ord($temp[$rsi - 1]) | 0x80);
        for ($i = 0; $i < $resize; $i++) {
            $t = unpack('V*', mb_substr($temp, $i * 8, 8, $encoding));
            $st[$i] = [
                $st[$i][0] ^ $t[2],
                $st[$i][1] ^ $t[1]
            ];
        }
        $beefcake($st);
        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= pack('V*', $st[$i][1], $st[$i][0]);
        }
        return $prefix . substr(bin2hex(mb_substr($out, 0, 256 / 8, $encoding)), 24);
    }

    /**
     * 私钥 转换 公钥
     * @param string $private
     * @return string
     */
    public static function privateKeyToPublicKey(string $private): string {
        return (new EC('secp256k1'))->keyFromPrivate($private)->getPublic(false, "hex");
    }

    /**
     * 生成私钥
     * @return string
     */
    public static function privateKey(): string {
        return (new EC('secp256k1'))->genKeyPair()->getPrivate('hex');
    }

    /**
     * 助记词 转 私钥
     * @param string $mnemonic
     * @return string
     */
    public static function mnemonicToPrivateKey(string $mnemonic): string {
        $path = [44, 195, 0, 0, 0];
        $privateKey = hash_pbkdf2("sha512", trim($mnemonic), "mnemonic", 2048, 64, true);
        foreach ($path as $index) {
            $privateKey = hash_hmac('sha512', $privateKey . pack('N', $index), 'Bitcoin seed', true);
        }
        return hash('sha256', substr(hash('sha256', $privateKey), 0, 32));
    }

    /**
     * 生成助记词
     * @param int $numWords 助记词数量
     * @param int $length
     * @return string
     */
    public static function mnemonic(int $numWords = 12, int $length = 128): string {
        // 生成伪随机种子，使用mt_rand()生成16字节的随机数据
        $seedHex = '';
        for ($i = 0; $i < $length; $i++) {
            $seedHex .= str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);  // 每次生成0到255的数字并转换为两位十六进制
        }
        // 载入英文单词表，Word list file 可以是一个标准的 BIP39 单词列表 (2048个单词)
        $wordlist = file(__DIR__ . '/../file/bip-0039.txt', FILE_IGNORE_NEW_LINES);
        // 将种子转换为二进制
        $seedBin = hex2bin($seedHex);
        // 计算 SHA256 校验和
        $checksum = hash('sha256', $seedBin, true);
        // 计算校验和的位数
        $checksumLength = (strlen($seedBin) * 8) / 32;  // 128位种子需要4位校验和
        // 获取校验和的前 N 位
        $checksumBits = substr(bin2hex($checksum), 0, $checksumLength);
        // 合并种子和校验和
        $combined = $seedBin . hex2bin($checksumBits);
        // 转换为二进制字符串
        $combinedBinary = '';
        foreach (str_split($combined) as $byte) {
            $combinedBinary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);  // 每个字节转为二进制并补齐
        }
        // 计算需要的二进制位数
        $requiredBits = $numWords * 11;
        // 截取合并后的二进制字符串前 $requiredBits 位
        $combinedBinary = substr($combinedBinary, 0, $requiredBits);
        // 每 11 位对应一个单词
        $mnemonic = [];
        $combinedLength = strlen($combinedBinary);
        // 按 11 位进行切分并对应词库中的单词
        for ($i = 0; $i < $combinedLength / 11; $i++) {
            $index = bindec(substr($combinedBinary, $i * 11, 11));  // 每 11 位转为一个数字
            $mnemonic[] = $wordlist[$index];                        // 从词库中查找单词
        }
        return implode(' ', $mnemonic);
    }
}