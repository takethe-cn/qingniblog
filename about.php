<?php
/**
 * 关于我
 * 头像 / 姓名 / 文案均可通过后台「首页设置 → 关于我区块」修改
 */
require_once __DIR__ . '/includes/bootstrap.php';

$settings = get_all_settings();
$page = 'about';
$page_title = $settings['about_title'] ?: '关于我';
$page_desc = '关于' . ($settings['about_name'] ?: $settings['site_name'] ?: '本站') . '的介绍';

require __DIR__ . '/includes/tpl_header.php';
?>
<section class="section about-page">
    <div class="container">
        <div class="section-head">
            <div class="kicker">About</div>
            <h2><?= e($settings['about_title'] ?: '关于我') ?></h2>
        </div>

        <div class="about-wrap">
            <?php if (!empty($settings['about_avatar'])): ?>
            <img class="about-avatar" src="<?= e($settings['about_avatar']) ?>" alt="<?= e($settings['about_name'] ?: '头像') ?>" loading="lazy">
            <?php else: ?>
            <img class="about-avatar" src="<?= url('assets/img/avatar-placeholder.svg') ?>" alt="头像" loading="lazy">
            <?php endif; ?>
            <div class="about-content">
                <?php if (!empty($settings['about_name'])): ?><h3><?= e($settings['about_name']) ?></h3><?php endif; ?>
                <?php foreach (preg_split('/\R/', $settings['about_content'] ?: '') as $line): ?>
                    <?php if (trim($line) !== ''): ?><p><?= e($line) ?></p><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="about-extra">
            想了解我的更多文章，可以去看看 <a href="<?= url('blog.php') ?>">博客</a>；
            也可以和我交换 <a href="<?= url('friends.php') ?>">友情链接</a>。
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/tpl_footer.php'; ?>
