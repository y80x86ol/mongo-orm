<?php
/**
 * 帮助类
 */

namespace mongoOrm;

class Handle {
    /**
     * 展示错误消息
     * @param string $errorMsg 错误内容
     */
    public static function showError($errorMsg = '') {
        $errorMsg = $errorMsg ? $errorMsg : 'have a error';
        die($errorMsg);
    }
}