<?php

static $_config;


if (! function_exists('config')) {
    /**
     * 获取配置参数
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        $conf = new config();
        // 优先执行设置获取或赋值
        if (is_string($key)) {
            $value = $conf->get($key);
            return $value === null ? $default : $value;
        }
        // 设置配置
        if (is_array($key)){
            $conf->set($key);
        }
        // 避免非法参数
        return null;
    }
}

if (! function_exists('base_path')) {
    function base_path($path = null)
    {
        if ($path == null) {
            return $_SERVER['DOCUMENT_ROOT'];
        }
        return $_SERVER['DOCUMENT_ROOT']. '/'.$path;
    }
}

if (! function_exists('db')) {
    function db()
    {
        return db::conn();
    }
}

if (! function_exists('redis')) {
    function redis() {
        return $redis = customRedis::conn();
    }
}


if (! function_exists('assembleSqlIn')) {
    function assembleSqlIn($key, $length) {
        $str = [];
        for ($i=0;$i<$length;$i++) {
            $str[] = ':ginv_in_param_'. $key. '_'. $i;
        }
        return implode(',', $str);
    }
}

if (! function_exists('redis_key')) {
    function redis_key(string $key, $_=null) {
        $args_list = func_get_args();
        array_shift($args_list);
        $redis_key = config('redis_key.'.$key);
        if ($redis_key == null) {
            return null;
        }
        $key = @sprintf($redis_key, ...$args_list);
        if ($key == false) {
            return null;
        }
        return $key;
    }
}

