<?php

final class log extends SeasLog
{

    public static function setPath() {
        parent::setBasePath('');
    }

    /**
     * 记录debug日志
     *
     * @param        $message
     * @param array  $content
     * @param string $logger
     *
     * @return mixed
     */
    public static function debug($message, $content = [], $logger = '') {
        self::setPath();
        return parent::debug($message, $content, $logger);
    }

    /**
     * 记录info日志
     *
     * @param        $message
     * @param array  $content
     * @param string $logger
     */
    public static function info($message, $content = [], $logger = '') {
        self::setPath();
        parent::info($message, $content, $logger);
    }

    /**
     * 记录notice日志
     *
     * @param        $message
     * @param array  $content
     * @param string $logger
     */
    public static function notice($message, $content = [], $logger = '') {
        self::setPath();
        parent::notice($message, $content, $logger);
    }

    /**
     * 记录warning日志
     *
     * @param        $message
     * @param array  $content
     * @param string $logger
     */
    public static function warn($message, $content = [], $logger = '') {
        self::setPath();
        parent::warning($message, $content, $logger);
    }

    /**
     * 记录error日志
     *
     * @param        $message
     * @param array  $content
     * @param string $logger
     */
    public static function error($message, $content = [], $logger = '') {
        self::setPath();
        parent::error($message, $content, $logger);
    }

}
