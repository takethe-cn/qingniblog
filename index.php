<?php
/**
 * 首页
 * 结构参考：Hero 大标题 → 关于 → 最新文章 → 页脚
 * 各区块可通过后台「首页设置」开关控制。
 */
require_once __DIR__ . '/includes/bootstrap.php';

$settings = get_all_settings();
$page = 'index';

$heroEnabled   = ($settings['hero_enabled'] ?? '1') === '1';
$aboutEnabled  = ($settings['about_enabled'] ?? '1') === '1';
$recentEnabled = ($settings['recent_enabled'] ?? '1') === '1';
$recentCount   = max(1, (int)($settings['recent_count'] ?? 5));

$recentPosts = [];
if ($recentEnabled) {
    try {
        $stmt = db()->prepare(
            'SELECT id, title, slug, summary, cover, category, tags, is_pinned, content_html, created_at
             FROM posts WHERE status = 1
             ORDER BY is_pinned DESC, created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $recentCount, PDO::PARAM_INT);
        $stmt->execute();
        $recentPosts = $stmt->fetchAll();
    } catch (Exception $e) {
        $recentPosts = [];
    }
}

require __DIR__ . '/includes/tpl_header.php';
?>

<?php if ($heroEnabled): ?>
<section class="hero">
    <div class="container">
        <h1 class="hero-title"><?= e($settings['site_slogan'] ?: '记录，让时间有迹可循') ?></h1>
        <p class="hero-subtitle"><?= e($settings['site_subtitle'] ?: '用代码和文字，各留一份') ?></p>
        <div class="hero-scroll"><span>下滑继续浏览</span><span class="arrow"></span></div>
    </div>
</section>
<?php endif; ?>

<?php if ($aboutEnabled): ?>
<section class="section <?= $heroEnabled ? 'alt' : '' ?>">
    <div class="container">
        <div class="section-head">
            <div class="kicker">About</div>
            <h2><?= e($settings['about_title'] ?: '关于我') ?></h2>
        </div>
        <div class="about-wrap">
            <?php if (!empty($settings['about_avatar'])): ?>
            <img class="about-avatar" src="<?= e($settings['about_avatar']) ?>" alt="头像" loading="lazy">
            <?php endif; ?>
            <div class="about-content">
                <?php if (!empty($settings['about_name'])): ?><h3><?= e($settings['about_name']) ?></h3><?php endif; ?>
                <?php foreach (preg_split('/\R/', $settings['about_content'] ?: '') as $line): ?>
                    <?php if (trim($line) !== ''): ?><p><?= e($line) ?></p><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($recentEnabled): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Blog</div>
            <h2><?= e($settings['blog_title'] ?: '最新文章') ?></h2>
            <p class="sub"><?= e($settings['blog_subtitle'] ?: '用文字记录走过的路') ?></p>
        </div>
        <?php if ($recentPosts): ?>
        <div class="posts-grid">
            <?php foreach ($recentPosts as $p): ?>
            <a class="post-card <?= $p['is_pinned'] ? 'pinned' : '' ?>" href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>">
                <?php if (!empty($p['cover'])): ?>
                <img class="post-cover" src="<?= e($p['cover']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                <?php endif; ?>
                <div class="post-meta">
                    <?php if (!empty($p['category'])): ?><span class="cat"><?= e($p['category']) ?></span><?php endif; ?>
                    <span><?= fmt_date($p['created_at']) ?></span>
                </div>
                <h3><?= e($p['title']) ?></h3>
                <p><?= e($p['summary'] ?: excerpt($p['content_html'] ?? '', 80)) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty"><h2>还没有文章</h2><p>进入后台发布第一篇吧。</p></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/tpl_footer.php'; ?>
