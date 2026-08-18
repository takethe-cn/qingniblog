<?php
/**
 * 前台公共底部
 */
$settings = get_all_settings();
$siteName = $settings['site_name'] ?: '我的博客';
$icpEnabled = ($settings['icp_enabled'] ?? '1') === '1';
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="f-logo"><?= e($siteName) ?></div>
                <p><?= e($settings['site_subtitle'] ?: '记录生活与代码') ?></p>
            </div>
            <div class="footer-col">
                <h4>导航</h4>
                <a href="<?= url('index.php') ?>">首页</a>
                <a href="<?= url('about.php') ?>">关于我</a>
                <a href="<?= url('blog.php') ?>">博客</a>
                <?php if (($settings['friend_enabled'] ?? '1') === '1'): ?>
                <a href="<?= url('friends.php') ?>">友情链接</a>
                <?php endif; ?>
            </div>
            <div class="footer-col">
                <h4>更多</h4>
                <a href="<?= url('admin/login.php') ?>">后台管理</a>
            </div>
        </div>
        <div class="footer-bottom">
            <?php if (!empty($settings['footer_line'])): ?>
            <div><?= e($settings['footer_line']) ?></div>
            <?php endif; ?>
            <?php if (!empty($settings['copyright_text'])): ?>
            <div><?= e($settings['copyright_text']) ?></div>
            <?php endif; ?>
            <?php if ($icpEnabled && !empty($settings['icp_text'])): ?>
            <div>
                <a class="icp-link" href="<?= e($settings['icp_url'] ?: 'https://beian.miit.gov.cn/') ?>" target="_blank" rel="noopener">
                    <?= e($settings['icp_text']) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
