<?php

class base
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

    /**
     * 调用rpc服务
     *
     * @param string $server_name
     * @param string $class_name
     *
     * @return $this
     */
    public function rpc(string $server_name, string $class_name) {
        $address = config('server.'.$server_name);
        if (strrpos($address, '/') !== strlen($address) -1 ) {
            $address .= '/';
        }
        $address .= $class_name;
        $this->rpc_address = $address;
        return $this;
    }

    /**
     * 访问rpc的方法
     * @param string $method
     * @param mixed   $params
     *
     * @return $this
     */
    public function call(string $method, $params = null) {
        $args_list = func_get_args();
        array_shift($args_list);
        try {
            $client = new Yar_Client($this->rpc_address);
            $result = $client->$method(...$args_list);
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
     * 设置当前数据
     * @param $data
     *
     * @return $this
     */
    public function set ($data) {
        $this->code = 'success';
        $this->data = $data;
        return $this;
    }

    /**
     * 获取当前数据
     * @return mixed
     */
    public function get () {
        return $this->data;
    }

    /**
     * 声明当前的错误
     * @param string $code
     * @param string $msg
     *
     * @return $this
     */
    public function error (string $code, string $msg = '') {
        if ($msg == ''){
            $msg  = config('error.'.$code);
        }
        $this->code = $code;
        $this->msg = empty($msg) ? '' : $msg;
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

    /**
     * 封装响应格式数据
     * @return array
     */
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