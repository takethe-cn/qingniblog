<?php
/**
 * 前台公共头部
 * 使用前需加载 includes/functions.php
 * 可传变量：$page_title（页面标题）、$page（当前页标识：index/blog/post/friends）、$page_desc
 */
if (!defined('BLOG_INSTALLED')) {
    exit('请先运行安装向导 install/');
}
$settings = get_all_settings();
$siteName = $settings['site_name'] ?: '我的博客';
$preload  = ($settings['preload_enabled'] ?? '1') === '1';
$headFont = $settings['heading_font'] ?? '';
$page = $page ?? '';
$title = isset($page_title) && $page_title !== '' ? $page_title . ' - ' . $siteName : $siteName;
$desc  = $page_desc ?? ($settings['site_description'] ?: $siteName);
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($settings['site_keywords'])): ?>
<meta name="keywords" content="<?= e($settings['site_keywords']) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= url('assets/css/main.css') ?>">
<?php if (in_array($headFont, ['ma_shan', 'zcool', 'long_cang', 'zhi_mang'], true)): ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ma+Shan+Zheng&family=ZCOOL+KuaiLe&family=Long+Cang&family=Zhi+Mang+Xing&display=swap">
<?php endif; ?>
<?php if (!empty($settings['custom_css'])): ?>
<style><?= $settings['custom_css'] ?></style>
<?php endif; ?>
</head>
<body data-preload="<?= $preload ? '1' : '0' ?>" class="page-<?= e($page !== '' ? $page : 'other') ?>">

<?php if ($preload): ?>
<div class="preloader" id="preloader">
    <div class="preloader-inner">
        <div class="preloader-ring"></div>
        <div class="preloader-text"><?= e($settings['preload_text'] ?: '正在加载') ?></div>
    </div>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="container">
        <a class="logo" href="<?= url('index.php') ?>"><span class="logo-dot"></span><?= e($siteName) ?></a>
        <nav class="nav" id="siteNav">
            <a href="<?= url('index.php') ?>" class="<?= $page === 'index' ? 'active' : '' ?>">首页</a>
            <a href="<?= url('about.php') ?>" class="<?= $page === 'about' ? 'active' : '' ?>">关于我</a>
            <a href="<?= url('blog.php') ?>" class="<?= $page === 'blog' ? 'active' : '' ?>">博客</a>
            <?php if (($settings['friend_enabled'] ?? '1') === '1'): ?>
            <a href="<?= url('friends.php') ?>" class="<?= $page === 'friends' ? 'active' : '' ?>">友情链接</a>
            <?php endif; ?>
        </nav>
        <button class="nav-toggle" id="navToggle" aria-label="菜单">☰</button>
    </div>
</header>

<?php if (($settings['announcement_enabled'] ?? '0') === '1' && !empty($settings['announcement'])): ?>
<div class="announcement"><?= e($settings['announcement']) ?></div>
<?php endif; ?>
