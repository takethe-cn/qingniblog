<?php
/**
 * 后台公共头部（已登录）
 * 需传变量：$admin_page（侧栏高亮标识）
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// 尚未安装时，引导到登录页（登录页会提示前往安装向导）
if (!file_exists(__DIR__ . '/../config.php')) {
    header('Location: ' . url('admin/login.php'));
    exit;
}
require_login();

$siteName = get_setting('site_name', '我的博客');
$adminPage = $admin_page ?? '';
$adminTitle = $admin_page_title ?? '控制台';

$menu = [
    'dashboard' => ['控制台', 'index.php', '◈'],
    'posts'     => ['文章管理', 'posts.php', '▤'],
    'comments'  => ['评论管理', 'comments.php', '❝'],
    'homepage'  => ['首页设置', 'homepage.php', '⌂'],
    'settings'  => ['站点设置', 'settings.php', '⚙'],
    'friends'   => ['友情链接', 'friends.php', '☍'],
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($adminTitle) ?> - <?= e($siteName) ?> 后台</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/main.css') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">青柠<span class="dot">·</span>博客 后台</div>
        <ul class="menu">
            <?php foreach ($menu as $key => $m): ?>
            <li><a href="<?= url('admin/' . $m[1]) ?>" class="<?= $adminPage === $key ? 'active' : '' ?>">
                <span class="ico"><?= $m[2] ?></span><?= $m[0] ?>
            </a></li>
            <?php endforeach; ?>
            <li><a href="<?= url('index.php') ?>" target="_blank"><span class="ico">↗</span>查看网站</a></li>
            <li><a href="<?= url('admin/logout.php') ?>"><span class="ico">×</span>退出登录</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="topbar">
            <h1><?= e($adminTitle) ?></h1>
            <div class="user">
                <span><?= e(defined('ADMIN_USER') ? ADMIN_USER : '') ?></span>
                <a class="btn btn-sm btn-ghost" href="<?= url('index.php') ?>" target="_blank">前台预览</a>
            </div>
        </div>
