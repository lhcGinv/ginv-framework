<?php

class DB
{
    //创建静态私有的变量保存该类对象
    static private $instance;

    //参数
    static private $pdo;

    private $sql;

    private function __construct($connect_name) {
        if ($connect_name == null) {
            $connect_name = config('database.default');
        }
        $config = config('database.'.$connect_name);
        self::$pdo = new NewPDO($config);
    }

    /**
     * 防止克隆对象
     */
    private function __clone(){
    }

    /**
     * @param null $connect_name
     *
     * @return DB
     */
    static public function conn($connect_name = null) {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        return self::$instance = new self($connect_name);
    }

    public function pdo (){
        return self::$pdo;
    }


    public function query($queryName = '', $params = []) {
        $params = $this->sqlDec($queryName, $params);
        $pdo    = self::$pdo;
        $stmt   = $pdo->prepare($this->sql, array($pdo::ATTR_CURSOR => $pdo::CURSOR_FWDONLY));
        $stmt->execute($params);
        return $stmt->fetchAll($pdo::FETCH_ASSOC);
    }

    public function queryRow($queryName = '', $params = []) {
        $params = $this->sqlDec($queryName, $params);
        $pdo    = self::$pdo;
        $stmt   = $pdo->prepare($this->sql, array($pdo::ATTR_CURSOR => $pdo::CURSOR_FWDONLY));
        $stmt->execute($params);
        return $stmt->fetch($pdo::FETCH_ASSOC);
    }

    public function getLastID() {
        $pdo = self::$pdo;
        return $pdo->lastInsertId();
    }

    private function sqlDec($queryName, $params) {
        $p = [];
        foreach ($params as $k => $v) {
            if ($v !== 0 && $v !== '' && $v !== null) {
                $p[':' . $k] = $v;
            }
        }
        $prepare_params = $p;
        $queryName      = $this->getFunctionName($queryName);
        $this->sql      = $queryName($p);
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
     * 开始事务
     * @return bool
     */
    public function begin() {
        if (self::$pdo->inTransaction() == false) {
            return self::$pdo->beginTransaction();
        }
        return true;
    }

    /**
     * 判断当前是否在事务中
     * @return bool
     */
    public function isBegin() {
        return self::$pdo->inTransaction();
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