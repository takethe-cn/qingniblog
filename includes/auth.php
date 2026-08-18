<?php
/**
 * 后台登录认证与访问控制
 */

if (session_status() === PHP_SESSION_NONE) {
    // 会话 Cookie 加固
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_name('ZBLOG_SESS');
    session_start();
}

/** 是否已登录 */
function is_logged_in()
{
    return !empty($_SESSION['admin_logged_in']);
}

/** 需要登录的页面守卫 */
function require_login()
{
    if (!is_logged_in()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

/** 执行登录（成功时刷新会话 ID，防会话固定） */
function do_login($username, $password)
{
    if (!defined('ADMIN_USER') || !defined('ADMIN_PASS_HASH') || ADMIN_PASS_HASH === '') {
        return ['ok' => false, 'msg' => '管理员账号尚未配置，请重新运行安装向导。'];
    }
    $ok = hash_equals(ADMIN_USER, (string)$username)
        && password_verify((string)$password, ADMIN_PASS_HASH);
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        return ['ok' => true, 'msg' => '登录成功'];
    }
    return ['ok' => false, 'msg' => '用户名或密码错误'];
}

/** 退出登录 */
function do_logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
