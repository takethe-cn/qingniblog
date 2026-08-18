<?php
/**
 * 站点设置
 * 包含：站点信息、页脚（版权/ICP）、预加载开关、公告、博客参数、手写字体、自定义CSS、修改密码
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'settings';
$admin_page_title = '站点设置';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'site') {
        foreach (['site_name', 'site_slogan', 'site_subtitle', 'site_description', 'site_keywords'] as $k) {
            set_setting($k, trim($_POST[$k] ?? ''));
        }
        $msg = '站点信息已保存。';
    } elseif ($section === 'footer') {
        set_setting('footer_line', trim($_POST['footer_line'] ?? ''));
        set_setting('copyright_text', trim($_POST['copyright_text'] ?? ''));
        set_setting('icp_text', trim($_POST['icp_text'] ?? ''));
        set_setting('icp_url', trim($_POST['icp_url'] ?? 'https://beian.miit.gov.cn/'));
        set_setting('icp_enabled', isset($_POST['icp_enabled']) ? '1' : '0');
        $msg = '页脚信息已保存。';
    } elseif ($section === 'preload') {
        set_setting('preload_enabled', isset($_POST['preload_enabled']) ? '1' : '0');
        set_setting('preload_text', trim($_POST['preload_text'] ?? '正在加载'));
        $msg = '预加载设置已保存。';
    } elseif ($section === 'announcement') {
        set_setting('announcement_enabled', isset($_POST['announcement_enabled']) ? '1' : '0');
        set_setting('announcement', trim($_POST['announcement'] ?? ''));
        $msg = '公告设置已保存。';
    } elseif ($section === 'blog') {
        set_setting('blog_title', trim($_POST['blog_title'] ?? ''));
        set_setting('blog_subtitle', trim($_POST['blog_subtitle'] ?? ''));
        $pageSize = max(1, min(50, (int)($_POST['blog_page_size'] ?? 8)));
        set_setting('blog_page_size', (string)$pageSize);
        $msg = '博客参数已保存。';
    } elseif ($section === 'font') {
        set_setting('heading_font', trim($_POST['heading_font'] ?? ''));
        set_setting('custom_css', (string)($_POST['custom_css'] ?? ''));
        $msg = '字体与样式设置已保存。';
    } elseif ($section === 'password') {
        $old = (string)($_POST['old_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $new2 = (string)($_POST['new_password2'] ?? '');
        if (!password_verify($old, defined('ADMIN_PASS_HASH') ? ADMIN_PASS_HASH : '')) {
            $err = '当前密码不正确。';
        } elseif (strlen($new) < 8) {
            $err = '新密码长度不能少于 8 位。';
        } elseif ($new !== $new2) {
            $err = '两次输入的新密码不一致。';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $file = __DIR__ . '/../config.php';
            $content = file_get_contents($file);
            // 用回调避免哈希中的 $ 被 preg_replace 当作反向引用
            $content = preg_replace_callback("/define\('ADMIN_PASS_HASH',\s*'[^']*'\);/", function () use ($hash) {
                return "define('ADMIN_PASS_HASH', '" . $hash . "');";
            }, $content);
            if ($content !== null && file_put_contents($file, $content) !== false) {
                $msg = '密码已修改，下次登录请使用新密码。';
            } else {
                $err = '密码文件写入失败，请检查 config.php 权限。';
            }
        }
    }
}

$S = get_all_settings();
$fonts = [
    'ma_shan'  => '马善政楷书（Ma Shan Zheng）',
    'zcool'    => '站酷快乐体（ZCOOL KuaiLe）',
    'long_cang' => '龙藏体（Long Cang）',
    'zhi_mang' => '智芒手书（Zhi Mang Xing）',
    'local'    => '系统手写（楷体 / 仿宋等本地字体）',
];
$curFont = $S['heading_font'] ?? '';

require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="site">
    <div class="card">
        <h3>站点信息</h3>
        <div class="field">
            <label>站点名称</label>
            <input type="text" name="site_name" value="<?= e($S['site_name'] ?? '') ?>">
        </div>
        <div class="grid2">
            <div class="field">
                <label>首页大标题（Hero）</label>
                <input type="text" name="site_slogan" value="<?= e($S['site_slogan'] ?? '') ?>">
            </div>
            <div class="field">
                <label>副标题</label>
                <input type="text" name="site_subtitle" value="<?= e($S['site_subtitle'] ?? '') ?>">
            </div>
        </div>
        <div class="field">
            <label>站点描述（SEO）</label>
            <input type="text" name="site_description" value="<?= e($S['site_description'] ?? '') ?>">
        </div>
        <div class="field">
            <label>站点关键词（SEO，逗号分隔）</label>
            <input type="text" name="site_keywords" value="<?= e($S['site_keywords'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">保存站点信息</button>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="footer">
    <div class="card">
        <h3>页脚（版权与备案）</h3>
        <div class="field">
            <label>页脚声明</label>
            <input type="text" name="footer_line" value="<?= e($S['footer_line'] ?? '') ?>" placeholder="例如：页面由本站独立设计 & 开发">
        </div>
        <div class="field">
            <label>版权信息</label>
            <input type="text" name="copyright_text" value="<?= e($S['copyright_text'] ?? '') ?>" placeholder="例如：Copyright © 2026 xxx. All Rights Reserved.">
        </div>
        <div class="grid2">
            <div class="field">
                <label>ICP 备案号</label>
                <input type="text" name="icp_text" value="<?= e($S['icp_text'] ?? '') ?>" placeholder="例如：京ICP备2026000000号-1">
            </div>
            <div class="field">
                <label>备案链接</label>
                <input type="url" name="icp_url" value="<?= e($S['icp_url'] ?? 'https://beian.miit.gov.cn/') ?>">
            </div>
        </div>
        <div class="checkline">
            <input type="checkbox" name="icp_enabled" id="icp_enabled" <?= (($S['icp_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
            <label for="icp_enabled">显示 ICP 备案号</label>
        </div>
        <button type="submit" class="btn">保存页脚信息</button>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="preload">
    <div class="card">
        <h3>网站预加载</h3>
        <p style="color:var(--muted);margin-top:0">开启后，网站每次进入会先展示一个加载动画（青柠绿/柠檬黄），避免内容加载的闪烁感。</p>
        <div class="checkline">
            <input type="checkbox" name="preload_enabled" id="preload_enabled" <?= (($S['preload_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
            <label for="preload_enabled">开启网站预加载动画</label>
        </div>
        <div class="field">
            <label>加载动画文字</label>
            <input type="text" name="preload_text" value="<?= e($S['preload_text'] ?? '正在加载') ?>">
        </div>
        <div class="preload-demo">
            <div class="ring"></div>
            <div class="txt"><?= e($S['preload_text'] ?? '正在加载') ?></div>
        </div>
        <div style="margin-top:16px"><button type="submit" class="btn">保存预加载设置</button></div>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="announcement">
    <div class="card">
        <h3>公告栏</h3>
        <div class="checkline">
            <input type="checkbox" name="announcement_enabled" id="announcement_enabled" <?= (($S['announcement_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
            <label for="announcement_enabled">在顶部显示公告</label>
        </div>
        <div class="field">
            <label>公告内容</label>
            <input type="text" name="announcement" value="<?= e($S['announcement'] ?? '') ?>" placeholder="例如：本站全新上线，欢迎常来看看">
        </div>
        <button type="submit" class="btn">保存公告</button>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="blog">
    <div class="card">
        <h3>博客参数</h3>
        <div class="grid2">
            <div class="field">
                <label>博客区块标题（首页）</label>
                <input type="text" name="blog_title" value="<?= e($S['blog_title'] ?? '') ?>">
            </div>
            <div class="field">
                <label>博客区块副标题</label>
                <input type="text" name="blog_subtitle" value="<?= e($S['blog_subtitle'] ?? '') ?>">
            </div>
        </div>
        <div class="field">
            <label>博客列表每页文章数</label>
            <input type="number" name="blog_page_size" min="1" max="50" value="<?= e($S['blog_page_size'] ?? 8) ?>">
        </div>
        <button type="submit" class="btn">保存博客参数</button>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="font">
    <div class="card">
        <h3>手写字体与自定义样式</h3>
        <div class="field">
            <label>标题 / 站点手写字体</label>
            <select name="heading_font">
                <option value="local" <?= $curFont === 'local' || $curFont === '' ? 'selected' : '' ?>>系统手写（楷体/仿宋等本地字体）</option>
                <?php foreach ($fonts as $v => $label): ?>
                    <?php if ($v === 'local') continue; ?>
                    <option value="<?= e($v) ?>" <?= $curFont === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="help">选择在线字体需联网加载；正文始终使用系统默认字体，仅在标题与站点名称上使用手写字体。</p>
        </div>
        <div class="field">
            <label>自定义 CSS <span class="hint">高级功能，谨慎使用</span></label>
            <textarea name="custom_css" rows="6" placeholder="例如：&#10;.hero-title { color: #65a30d; }"><?= e($S['custom_css'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">保存字体与样式</button>
    </div>
</form>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="password">
    <div class="card">
        <h3>修改登录密码</h3>
        <div class="grid2">
            <div class="field">
                <label>当前密码</label>
                <input type="password" name="old_password" autocomplete="current-password">
            </div>
            <div class="field">
                <label>新密码（至少 8 位）</label>
                <input type="password" name="new_password" autocomplete="new-password">
            </div>
        </div>
        <div class="field">
            <label>确认新密码</label>
            <input type="password" name="new_password2" autocomplete="new-password">
        </div>
        <button type="submit" class="btn">修改密码</button>
    </div>
</form>

<?php require __DIR__ . '/tpl_footer.php'; ?>
