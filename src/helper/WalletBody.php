<?php

namespace AloneFrame\tron\helper;

class WalletBody {
    public mixed $mnemonic   = '';
    public mixed $privateKey = '';
    public mixed $publicKey  = '';
    public mixed $addressHex = '';
    public mixed $address    = '';

    public function __construct(array $arr = []) {
        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }
}