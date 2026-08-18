<?php
/**
 * 数据库连接（PDO）
 * 使用预处理语句，避免 SQL 注入。
 * 若 config.php 不存在（尚未安装），调用 db() 会抛出 RuntimeException。
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!file_exists(__DIR__ . '/../config.php')) {
            throw new RuntimeException('系统尚未安装，请先运行安装向导。');
        }
        require_once __DIR__ . '/../config.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            defined('DB_PORT') ? DB_PORT : '3306',
            DB_NAME,
            defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
