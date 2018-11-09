<?php

class Base
{
    // 错误信息，储存code,msg组成的数组
    protected $error = [];

    // 错误信息，储存code,data和msg
    protected $res;

    protected $rpc_address;

    private $code = 'success';
    private $msg = '';
    private $data;

    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    public function rpc($server_name, $class_name) {
        $address = config('server.'.$server_name);
        if (strrpos($address, '/') !== strlen($address) -1 ) {
            $address .= '/';
        }
        $address .= $class_name;
        $this->rpc_address = $address;
        return $this;
    }

    public function call($method, $params = null) {
        try {
            $client = new Yar_Client($this->rpc_address);
            $result = $client->$method($params);
            if ($result['code'] == 'success') {
                $this->data = $result['data'];
            } else {
                $this->code = $result['code'];
                $this->msg = $result['msg'];
            }
        } catch (Exception $e) {
            switch ($e->getCode()) {
                case 2: $code = 'error.rpc.server_class_err';break;
                case 4: $code = 'error.rpc.server_function_err';break;
                case 16: $code = 'error.rpc.server_connect_err';break;
                default: $code = 'error.rpc.server_other_err';
            }
            $this->error($code);
        }
        return $this;
    }

    /**
     * @param null $data
     *
     * @return $this
     */
    public function set ($data) {
        $this->code = 'success';
        $this->data = $data;
        return $this;
    }

    public function get () {
        return $this->data;
    }

    public function error ($code, $msg = null) {
        if ($msg == ''){
            $msg  = config($code);
        }
        $this->code = $code;
        $this->msg = $msg;
        return $this;
    }

    /**
     * 判断是否有错误
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->code !== 'success';
    }

    public function response() {
        if ($this->code != 'success') {
            return [
                'code' => $this->code,
                'msg'  => $this->msg,
            ];
        }

        return [
            'code' => 'success',
            'data' => $this->data
        ];
    }
}