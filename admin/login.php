<?php
/**
 * 后台登录页
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// 已安装则加载配置（用于判断 BLOG_INSTALLED）
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 已登录则直接进入后台
if (is_logged_in()) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = '请求校验失败，请刷新页面后重试。';
    } else {
        $res = do_login($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($res['ok']) {
            header('Location: ' . url('admin/index.php'));
            exit;
        }
        $error = $res['msg'];
    }
}

// 若未安装则引导
$needsInstall = !(defined('BLOG_INSTALLED') && BLOG_INSTALLED && file_exists(__DIR__ . '/../config.php'));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - 后台</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="login-body">
<div class="login-box">
    <h1>青柠·博客</h1>
    <div class="sub">后台管理登录</div>
    <?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($needsInstall): ?>
    <div class="alert info">系统尚未安装，请先运行安装向导。</div>
    <div style="text-align:center"><a class="btn" href="<?= url('install/') ?>">前往安装</a></div>
    <?php else: ?>
    <form method="post" action="login.php" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
            <label>用户名</label>
            <input type="text" name="username" required autofocus>
        </div>
        <div class="field">
            <label>密码</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%">登 录</button>
    </form>
    <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:16px">
        <a href="<?= url('index.php') ?>">返回网站首页</a>
    </p>
    <?php endif; ?>
</div>
</body>
</html>
