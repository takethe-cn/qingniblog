<?php
/**
 * 站点引导文件（前台页面统一入口）
 * 未安装时自动跳转安装向导；已安装则加载公共函数库。
 */
if (!file_exists(dirname(__DIR__) . '/config.php')) {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $install = ($base === '' || $base === '/') ? '' : $base;
    header('Location: ' . $install . '/install/');
    exit;
}

require_once __DIR__ . '/functions.php';
