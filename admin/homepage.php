<?php
/**
 * 首页设置（图形化）
 * 以可视化开关卡片 + 表单方式修改首页各区块与首页元素
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'homepage';
$admin_page_title = '首页设置';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_setting('hero_enabled',   isset($_POST['hero_enabled'])   ? '1' : '0');
    set_setting('about_enabled',  isset($_POST['about_enabled'])  ? '1' : '0');
    set_setting('recent_enabled', isset($_POST['recent_enabled']) ? '1' : '0');
    set_setting('friend_enabled', isset($_POST['friend_enabled']) ? '1' : '0');

    set_setting('site_slogan',  trim($_POST['site_slogan'] ?? ''));
    set_setting('site_subtitle', trim($_POST['site_subtitle'] ?? ''));

    set_setting('about_title',   trim($_POST['about_title'] ?? ''));
    set_setting('about_name',    trim($_POST['about_name'] ?? ''));
    set_setting('about_content', trim($_POST['about_content'] ?? ''));
    set_setting('about_avatar',  trim($_POST['about_avatar'] ?? ''));

    $recentCount = max(1, min(20, (int)($_POST['recent_count'] ?? 5)));
    set_setting('recent_count', (string)$recentCount);

    set_setting('friend_desc', trim($_POST['friend_desc'] ?? ''));

    $msg = '首页设置已保存。';
}

$S = get_all_settings();

require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<form method="post">
    <?= csrf_field() ?>

    <div class="card">
        <h3>首页区块开关</h3>
        <p style="color:var(--muted);margin-top:0">点击卡片即可开启 / 关闭首页上的对应区块。</p>
        <div class="switch-grid">
            <?php
            $switches = [
                ['hero_enabled', 'Hero 大标题', '首页首屏的站点标语区', $S['hero_enabled'] ?? '1'],
                ['about_enabled', '关于我', '站点介绍与头像', $S['about_enabled'] ?? '1'],
                ['recent_enabled', '最新文章', '首页展示近期文章', $S['recent_enabled'] ?? '1'],
                ['friend_enabled', '友情链接页', '是否启用独立友链页面', $S['friend_enabled'] ?? '1'],
            ];
            foreach ($switches as $sw):
                $on = ($sw[3] ?? '1') === '1';
            ?>
            <label class="switch-card <?= $on ? 'on' : '' ?>" data-key="<?= $sw[0] ?>">
                <input type="hidden" name="<?= e($sw[0]) ?>" value="<?= $on ? '1' : '0' ?>">
                <div class="sc-flag"><?= $on ? '已开启' : '已关闭' ?></div>
                <div class="sc-name"><?= e($sw[1]) ?></div>
                <div class="sc-desc"><?= e($sw[2]) ?></div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Hero 首屏文案</h3>
        <div class="grid2">
            <div class="field">
                <label>大标题</label>
                <input type="text" name="site_slogan" value="<?= e($S['site_slogan'] ?? '') ?>">
            </div>
            <div class="field">
                <label>副标题</label>
                <input type="text" name="site_subtitle" value="<?= e($S['site_subtitle'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card">
        <h3>「关于我」区块</h3>
        <div class="grid2">
            <div class="field">
                <label>标题</label>
                <input type="text" name="about_title" value="<?= e($S['about_title'] ?? '') ?>">
            </div>
            <div class="field">
                <label>姓名 / 昵称</label>
                <input type="text" name="about_name" value="<?= e($S['about_name'] ?? '') ?>" placeholder="例如：青柠">
            </div>
        </div>
        <div class="field">
            <label>每行介绍 <span class="hint">每行一段，可多行</span></label>
            <textarea name="about_content" rows="4"><?= e($S['about_content'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label>头像</label>
            <div class="avatar-upload">
                <img id="aboutAvatarPreview" src="<?= e($S['about_avatar'] ?: url('assets/img/avatar-placeholder.svg')) ?>" alt="头像预览">
                <div style="flex:1">
                    <input type="text" name="about_avatar" id="aboutAvatar" value="<?= e($S['about_avatar'] ?? '') ?>" placeholder="填写图片 URL，或上传：">
                    <div style="display:flex;gap:8px;margin-top:8px">
                        <button type="button" class="btn btn-sm btn-ghost" id="btnUploadAvatar">上传头像</button>
                        <input type="file" id="avatarFileInput" accept="image/*" style="display:none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>「最新文章」区块</h3>
        <div class="field">
            <label>首页展示文章数量</label>
            <input type="number" name="recent_count" min="1" max="20" value="<?= e($S['recent_count'] ?? 5) ?>">
        </div>
    </div>

    <div class="card">
        <h3>友情链接页说明</h3>
        <div class="field">
            <label>页面说明文字</label>
            <input type="text" name="friend_desc" value="<?= e($S['friend_desc'] ?? '') ?>" placeholder="例如：交换友链，一起成长。">
        </div>
    </div>

    <button type="submit" class="btn">保存首页设置</button>
</form>

<script>
(function () {
    'use strict';
    // 区块开关卡片
    document.querySelectorAll('.switch-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            if (e.target.tagName === 'INPUT') return;
            var key = card.getAttribute('data-key');
            var hidden = card.querySelector('input[type="hidden"]');
            var flag = card.querySelector('.sc-flag');
            var isOn = hidden.value === '1';
            hidden.value = isOn ? '0' : '1';
            card.classList.toggle('on', !isOn);
            flag.textContent = !isOn ? '已开启' : '已关闭';
        });
    });

    // 头像上传（AJAX）
    var btn = document.getElementById('btnUploadAvatar');
    var input = document.getElementById('avatarFileInput');
    var urlInput = document.getElementById('aboutAvatar');
    var preview = document.getElementById('aboutAvatarPreview');
    var cfg = window.ZBLOG || {};
    if (btn && input) {
        btn.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fd.append('csrf_token', <?= json_encode(csrf_token()) ?>);
            fetch(<?= json_encode(url('admin/upload.php')) ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) {
                        urlInput.value = res.url;
                        preview.src = res.url;
                        alert('头像上传成功，点击「保存首页设置」生效。');
                    } else {
                        alert('上传失败：' + (res.msg || '未知错误'));
                    }
                    input.value = '';
                })
                .catch(function () { alert('上传失败：网络错误'); input.value = ''; });
        });
    }
    // 手动输入 URL 时同步预览
    urlInput.addEventListener('input', function () { if (urlInput.value.trim()) preview.src = urlInput.value.trim(); });
})();
</script>

<?php require __DIR__ . '/tpl_footer.php'; ?>
