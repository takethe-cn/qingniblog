<?php
/**
 * 后台控制台
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$admin_page = 'dashboard';
$admin_page_title = '控制台';

// 统计
$totalPosts = $publishPosts = $draftPosts = $totalViews = $totalFriends = 0;
try {
    $totalPosts   = (int)db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $publishPosts = (int)db()->query('SELECT COUNT(*) FROM posts WHERE status = 1')->fetchColumn();
    $draftPosts   = (int)db()->query('SELECT COUNT(*) FROM posts WHERE status = 0')->fetchColumn();
    $totalViews   = (int)db()->query('SELECT COALESCE(SUM(views),0) FROM posts')->fetchColumn();
    $totalFriends = (int)db()->query('SELECT COUNT(*) FROM friends')->fetchColumn();
} catch (Exception $e) { /* 表不存在时忽略 */ }

// 最近文章
$recent = [];
try {
    $recent = db()->query('SELECT id, title, status, is_pinned, created_at FROM posts ORDER BY id DESC LIMIT 8')->fetchAll();
} catch (Exception $e) { $recent = []; }

$needUpgrade = !defined('BLOG_INSTALLED');
$preload = get_setting('preload_enabled', '1') === '1';

require __DIR__ . '/tpl_header.php';
?>

<div class="stats">
    <div class="stat"><div class="num"><?= $totalPosts ?></div><div class="label">全部文章</div></div>
    <div class="stat yellow"><div class="num"><?= $publishPosts ?></div><div class="label">已发布</div></div>
    <div class="stat"><div class="num"><?= $draftPosts ?></div><div class="label">草稿</div></div>
    <div class="stat yellow"><div class="num"><?= $totalViews ?></div><div class="label">总浏览量</div></div>
    <div class="stat"><div class="num"><?= $totalFriends ?></div><div class="label">友情链接</div></div>
</div>

<div class="card">
    <div class="card-title-row">
        <h3>最近文章</h3>
        <a class="btn btn-sm" href="<?= url('admin/post-edit.php') ?>">写文章</a>
    </div>
    <?php if ($recent): ?>
    <table class="table">
        <thead><tr><th>标题</th><th>状态</th><th>发布时间</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $p): ?>
        <tr>
            <td><?= e($p['title']) ?> <?= $p['is_pinned'] ? '<span class="tag pin">置顶</span>' : '' ?></td>
            <td><?= $p['status'] ? '<span class="tag">已发布</span>' : '<span class="tag off">草稿</span>' ?></td>
            <td><?= e(fmt_datetime($p['created_at'])) ?></td>
            <td class="ops">
                <a href="<?= url('admin/post-edit.php?id=' . (int)$p['id']) ?>">编辑</a>
                <a href="<?= url('post.php?id=' . (int)$p['id']) ?>" target="_blank">查看</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:var(--muted)">还没有文章，点击右上角「写文章」开始创作吧。</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>快速开始</h3>
    <ul style="line-height:2.2;color:#374151">
        <li><a href="<?= url('admin/post-edit.php') ?>">发布第一篇 Markdown 文章</a>（支持在线编写、上传 .md 文件）</li>
        <li><a href="<?= url('admin/homepage.php') ?>">调整首页布局</a>（Hero / 关于 / 最新文章 开关）</li>
        <li><a href="<?= url('admin/settings.php') ?>">设置站点信息与页脚</a>（站点名、ICP 备案号、版权、预加载开关）</li>
        <li><a href="<?= url('admin/friends.php') ?>">管理友情链接</a></li>
    </ul>
</div>

<?php require __DIR__ . '/tpl_footer.php'; ?>
