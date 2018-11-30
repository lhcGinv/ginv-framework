<?php

class db
{
    //创建静态私有的变量保存该类对象
    static private $instance;

    //参数
    static private $pdo;

    private $sql;
    private $fields = null;

    private function __construct(string $connect_name) {
        if ($connect_name == null) {
            $connect_name = config('database.default');
        }
        $config = config('database.'.$connect_name);
        try {
            self::$pdo = new customPDO($config);
        } catch (PDOException $e) {
            log::error('数据库连接失败, msg: ' . $e->getMessage());
            die('Database connection failed');
        }
    }

    /**
     * 防止克隆对象
     */
    private function __clone(){
    }

    /**
     * @param string $connect_name
     *
     * @return db
     */
    static public function conn($connect_name = '') {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        return self::$instance = new self($connect_name);
    }

    public function pdo (){
        return self::$pdo;
    }

    /**
     * 重置查询字段
     * @param $fields
     * @param $_
     *
     * @return $this
     */
    public function select($fields, $_) {
        $args_list = func_get_args();
        $this->fields = implode(',', $args_list);
        return $this;
    }


    /**
     * 查询多条记录
     * @param string $queryName
     * @param array  $params
     *
     * @return array
     */
    public function query(string $queryName, $params = []) {
        $params = $this->sqlDec($queryName, $params);
        $stmt   = $this->prepare();
        if ($stmt === false) {
            return [];
        }
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(self::$pdo::FETCH_ASSOC);
        } catch (PDOException $e) {
            log::error('sql执行失败, sql: '. $this->sql.', params: '. json_encode($params) .' ,msg: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 查询单条记录
     * @param string $queryName
     * @param array  $params
     *
     * @return array|mixed
     */
    public function queryRow(string $queryName, $params = []) {
        $params = $this->sqlDec($queryName, $params);
        $stmt   = $this->prepare();
        if ($stmt === false) {
            return [];
        }
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            log::error('sql执行失败, sql: '. $this->sql.', params: '. json_encode($params) .' ,msg: ' . $e->getMessage());
            return [];
        }
        return $stmt->fetch(self::$pdo::FETCH_ASSOC);
    }

    /**
     * count查询快捷返回函数
     *
     * @param string $queryName
     * @param array  $params
     *
     * @return int|mixed
     */
    public function count(string $queryName, $params = []) {
        $count_result = $this->queryRow($queryName, $params);
        if ($count_result) {
            return current($count_result);
        } else {
            return 0;
        }
    }

    /**
     * 执行一条 SQL 语句，并返回受影响的行数
     * @param string $queryName
     * @param array  $params
     *
     * @return int
     */
    public function exec(string $queryName, $params = []) {
        $params = $this->sqlDec($queryName, $params);
        $stmt   = $this->prepare();
        if ($stmt === false) {
            return 0;
        }
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            log::error('sql执行失败, sql: '. $this->sql.', params: '. json_encode($params) .' ,msg: ' . $e->getMessage());
            return 0;
        }
        return $stmt->rowCount();
    }

    public function lastInsertId() {
        $pdo = self::$pdo;
        return $pdo->lastInsertId();
    }

    public function getSql() {
        return $this->sql;
    }

    /**
     * @return bool|PDOStatement
     */
    private function prepare() {
        try {
            return self::$pdo->prepare($this->sql, array(self::$pdo::ATTR_CURSOR => self::$pdo::CURSOR_FWDONLY));
        } catch (PDOException $e) {
            log::error('sql预执行语句失败, sql: '. $this->sql.', msg: ' . $e->getMessage());
            return false;
        }
    }

    private function sqlDec($queryName, $params) {
        $p = [];
        foreach ($params as $k => $v) {
            if (!empty($v)) {
                if (is_array($v)) {
                    foreach ($v as $in_key => $in_item) {
                        $p[':ginv_in_param_'. $k . '_' . $in_key] = $in_item;
                    }
                } else {
                    $p[':' . $k] = $v;
                }
            }
        }
        $prepare_params = $p;
        $queryName      = $this->getFunctionName($queryName);
        $this->sql      = trim($queryName($params));
        $this->sql      = preg_replace ( "/\s(?=\s)/","\\1", $this->sql );
        if ($this->fields !== '') {
            $this->sql = str_replace('select *','select '.$this->fields, $this->sql);
            $this->sql = str_replace('SELECT *','SELECT '.$this->fields, $this->sql);
        }
        $this->fields = '';
        return $prepare_params;
    }

    private function getFunctionName($queryName) {
        $name = str_replace('.', ' ', $queryName);
        $name = 'ginV ' . $name;
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        $name = str_replace('.', '$', $name);
        return lcfirst($name);
    }

    /**
     * 判断当前是否在事务中
     * @return bool
     */
    public function isBegin() {
        return self::$pdo->inTransaction();
    }


    /**
     * 启动事务
     * @return bool
     */
    public function begin() {
        if ($this->isBegin() == false) {
            return self::$pdo->beginTransaction();
        }
        return true;
    }


    /**
     * 提交事务
     * @return bool
     */
    public function commit() {
        return self::$pdo->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack() {
        return self::$pdo->rollBack();
    }

}