<?php

namespace AloneFrame\tron\helper;

class ApiBody {
    public mixed $code = '';
    public mixed $data = '';
    public mixed $msg  = '';
    public mixed $body = '';

    public function __construct(array $arr = [], callable|null $process = null) {
        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
        if (!empty($process) && is_callable($process)) {
            $process($this);
        }
    }

    public function val(): ApiVal {
        return new ApiVal($this->code, $this->data, $this->msg);
    }
}