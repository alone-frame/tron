<?php

namespace AloneFrame\tron\helper;

class ApiVal {
    public mixed $code = '';
    public mixed $data = '';
    public mixed $msg  = '';

    public function __construct($code, $data, $msg) {
        $this->code = $code;
        $this->data = $data;
        $this->msg = $msg;
    }
}