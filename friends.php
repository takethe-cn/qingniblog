<?php
/**
 * 友情链接页
 */
require_once __DIR__ . '/includes/bootstrap.php';

$settings = get_all_settings();
$page = 'friends';

$friends = [];
try {
    $stmt = db()->query('SELECT * FROM friends WHERE status = 1 ORDER BY sort ASC, id ASC');
    $friends = $stmt->fetchAll();
} catch (Exception $e) {
    $friends = [];
}

$page_title = '友情链接';
$page_desc  = $settings['friend_desc'] ?: '交换友链，一起成长。';
require __DIR__ . '/includes/tpl_header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Friends</div>
            <h2>友情链接</h2>
            <p class="sub"><?= e($settings['friend_desc'] ?: '交换友链，一起成长。') ?></p>
        </div>

        <?php if ($friends): ?>
        <div class="friends-grid">
            <?php foreach ($friends as $f): ?>
            <a class="friend-card" href="<?= e($f['url']) ?>" target="_blank" rel="noopener nofollow">
                <?php if (!empty($f['avatar'])): ?>
                <img src="<?= e($f['avatar']) ?>" alt="<?= e($f['name']) ?>" loading="lazy">
                <?php else: ?>
                <img src="<?= url('assets/img/avatar-placeholder.svg') ?>" alt="<?= e($f['name']) ?>" loading="lazy">
                <?php endif; ?>
                <div>
                    <div class="f-name"><?= e($f['name']) ?></div>
                    <?php if (!empty($f['description'])): ?>
                    <div class="f-desc"><?= e($f['description']) ?></div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty">
            <h2>暂无友链</h2>
            <p>管理员可在后台「友情链接」中添加。</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/tpl_footer.php'; ?>
