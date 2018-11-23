<?php

class customRedis
{
    //创建静态私有的变量保存该类对象
    static private $instance;

    //参数
    static private $redis_object;

    private function __construct() {
        $config = config('database.redis');
        $appName = config('app.name');
        try {
            self::$redis_object = new Redis();
            self::$redis_object->connect($config['host'], $config['port'], $config['timeout']);
            self::$redis_object->setOption(Redis::OPT_PREFIX, "{$appName}:");
        } catch (RedisException $e) {
            log::error('redis连接失败, msg: ' . $e->getMessage());
            die('redis connection failed');
        }
    }

    /**
     * 防止克隆对象
     */
    private function __clone(){
    }

    /**
     * @return customRedis
     */
    static public function conn() {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        return self::$instance = new self();
    }

    /**
     * 执行redis命令
     * @param string $commend 命令名称
     * @param mixed   $params 命令参数
     *
     * @return mixed
     */
    public function do(string $commend, $params = null){
        $args_list = func_get_args();
        array_shift($args_list);
        return self::$redis_object->$commend(...$args_list);
    }

}