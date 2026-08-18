<?php
/**
 * CSRF 防护（双提交令牌）
 */

// 前台评论也使用 CSRF，这里统一确保会话已开启
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** 获取或生成 CSRF Token */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** 输出隐藏域 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** 校验 Token */
function csrf_verify($token = null)
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** 校验所有 POST 请求，失败直接终止 */
function csrf_guard()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        exit('请求校验失败（CSRF），请返回上一页刷新后重试。');
    }
}
