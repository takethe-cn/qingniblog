<?php
/**
 * 博客配置文件（示例）
 * 实际部署时由「安装向导」(install/) 自动生成 config.php，
 * 正常情况下无需手工修改本文件。
 */

// ========== 数据库配置 ==========
define('DB_HOST', '127.0.0.1');      // 数据库主机
define('DB_PORT', '3306');           // 数据库端口
define('DB_NAME', 'blog');           // 数据库名
define('DB_USER', 'blog');           // 数据库用户
define('DB_PASS', 'password');       // 数据库密码
define('DB_CHARSET', 'utf8mb4');     // 字符集（请保持 utf8mb4）

// ========== 站点路径配置 ==========
// 安装站点时所在目录的相对根路径，例如：
//   根目录安装（域名直接指向站点根）：define('BLOG_BASE_URL', '');
//   子目录安装（如 /blog/）：define('BLOG_BASE_URL', '/blog');
define('BLOG_BASE_URL', '');

// ========== 安装状态 ==========
define('BLOG_INSTALLED', true);

// ========== 管理员账号（由安装向导写入） ==========
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', ''); // password_hash() 生成的散列

// 站点时区
date_default_timezone_set('Asia/Shanghai');
