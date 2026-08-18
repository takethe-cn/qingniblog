<?php
/**
 * 文章详情页
 * 通过 slug 或 id 定位文章，正文为 Markdown 渲染后的安全 HTML
 */
require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if ($slug === '' && $id === 0) {
    http_response_code(404);
    require __DIR__ . '/includes/tpl_header.php';
    echo '<div class="section"><div class="container"><div class="empty"><h2>文章不存在</h2><p><a href="' . url('blog.php') . '">返回博客列表</a></p></div></div></div>';
    require __DIR__ . '/includes/tpl_footer.php';
    exit;
}

try {
    if ($slug !== '') {
        $stmt = db()->prepare('SELECT * FROM posts WHERE slug = ? AND status = 1 LIMIT 1');
        $stmt->execute([$slug]);
    } else {
        $stmt = db()->prepare('SELECT * FROM posts WHERE id = ? AND status = 1 LIMIT 1');
        $stmt->execute([$id]);
    }
    $post = $stmt->fetch();
} catch (Exception $e) {
    $post = false;
}

if (!$post) {
    http_response_code(404);
    require __DIR__ . '/includes/tpl_header.php';
    echo '<div class="section"><div class="container"><div class="empty"><h2>文章不存在或已下线</h2><p><a href="' . url('blog.php') . '">返回博客列表</a></p></div></div></div>';
    require __DIR__ . '/includes/tpl_footer.php';
    exit;
}

// 浏览量 +1
bump_views($post['id']);
$post['views'] = (int)$post['views'] + 1;

// 评论
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/captcha.php';
$commentMsg = '';
$commentErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    if (csrf_verify($_POST['csrf_token'] ?? '')) {
        if (!captcha_verify($_POST['c_captcha'] ?? '')) {
            $commentErr = '验证码错误，请重新输入。';
        } else {
            $res = save_comment(
                $post['id'],
                $_POST['c_name'] ?? '',
                $_POST['c_email'] ?? '',
                $_POST['c_website'] ?? '',
                $_POST['c_content'] ?? ''
            );
            if ($res['ok']) {
                $commentMsg = $res['msg'];
                $_POST['c_name'] = $_POST['c_email'] = $_POST['c_website'] = $_POST['c_content'] = '';
            } else {
                $commentErr = $res['msg'];
            }
        }
    } else {
        $commentErr = '表单校验失败，请刷新页面后重试。';
    }
}
$comments = get_comments($post['id']);
$commentCount = count($comments);

// 正文：优先使用保存的 HTML，否则实时渲染
$contentHtml = $post['content_html'];
if ($contentHtml === null || trim($contentHtml) === '') {
    $contentHtml = markdown_to_html($post['content_md']);
}

// 上一篇 / 下一篇（按创建时间）
$prev = $next = null;
try {
    $stmt = db()->prepare('SELECT id, title, slug FROM posts WHERE status = 1 AND created_at < ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$post['created_at']]);
    $prev = $stmt->fetch();
    $stmt = db()->prepare('SELECT id, title, slug FROM posts WHERE status = 1 AND created_at > ? ORDER BY created_at ASC LIMIT 1');
    $stmt->execute([$post['created_at']]);
    $next = $stmt->fetch();
} catch (Exception $e) {
    // 忽略
}

$tags = array_values(array_filter(array_map('trim', explode(',', (string)$post['tags']))));

$page = 'post';
$page_title = $post['title'];
$page_desc  = $post['summary'] ?: excerpt($contentHtml, 100);
require __DIR__ . '/includes/tpl_header.php';
?>

<section class="section">
    <div class="container">
        <article class="post-main">
            <div class="post-head">
                <h1><?= e($post['title']) ?></h1>
                <div class="meta">
                    <?php if (!empty($post['category'])): ?><span class="cat"><?= e($post['category']) ?></span><?php endif; ?>
                    <span><?= fmt_datetime($post['created_at']) ?></span>
                    <span><?= (int)$post['views'] ?> 次浏览</span>
                    <span><a href="#comments"><?= $commentCount ?> 条评论</a></span>
                </div>
            </div>

            <?php if (!empty($post['cover'])): ?>
            <img class="post-cover-lg" src="<?= e($post['cover']) ?>" alt="<?= e($post['title']) ?>">
            <?php endif; ?>

            <div class="markdown-body"><?= $contentHtml ?></div>

            <?php if ($tags): ?>
            <div class="tags">
                <?php foreach ($tags as $t): ?>
                <a href="<?= url('blog.php?q=' . rawurlencode($t)) ?>"># <?= e($t) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <nav class="post-nav">
                <?php if ($prev): ?>
                <a href="<?= url('post.php?slug=' . rawurlencode($prev['slug'])) ?>">← 上一篇：<?= e(mb_strimwidth($prev['title'], 0, 30, '…')) ?></a>
                <?php else: ?><span></span><?php endif; ?>
                <?php if ($next): ?>
                <a class="next" href="<?= url('post.php?slug=' . rawurlencode($next['slug'])) ?>">下一篇：<?= e(mb_strimwidth($next['title'], 0, 30, '…')) ?> →</a>
                <?php endif; ?>
            </nav>
        </article>

        <div class="comments" id="comments">
            <div class="comments-head">
                <h2>全部评论</h2>
                <span class="count"><?= $commentCount ?> 条评论</span>
            </div>

            <?php if ($comments): ?>
            <ul class="comment-list">
                <?php foreach ($comments as $c): ?>
                <li class="comment-item">
                    <?php if (!empty($c['avatar'])): ?>
                    <img class="c-avatar" src="<?= e($c['avatar']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                    <?php else: ?>
                    <img class="c-avatar" src="<?= url('assets/img/avatar-placeholder.svg') ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="c-body">
                        <div>
                            <?php if (!empty($c['website'])): ?>
                            <span class="c-name"><a href="<?= e($c['website']) ?>" target="_blank" rel="noopener nofollow"><?= e($c['name']) ?></a></span>
                            <?php else: ?>
                            <span class="c-name"><?= e($c['name']) ?></span>
                            <?php endif; ?>
                            <span class="c-time"><?= fmt_datetime($c['created_at']) ?></span>
                        </div>
                        <div class="c-text"><?= nl2br(e($c['content'])) ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="comment-empty">还没有任何评论，你来说两句呐!</div>
            <?php endif; ?>

            <div class="comment-form">
                <h3>发表评论</h3>
                <?php if ($commentMsg): ?><div class="cf-msg ok"><?= e($commentMsg) ?></div><?php endif; ?>
                <?php if ($commentErr): ?><div class="cf-msg err"><?= e($commentErr) ?></div><?php endif; ?>
                <form method="post" action="<?= url('post.php?slug=' . rawurlencode($post['slug'])) ?>#comments">
                    <?= csrf_field() ?>
                    <div class="cf-row">
                        <input type="text" name="c_name" value="<?= e($_POST['c_name'] ?? '') ?>" placeholder="昵称 *" maxlength="100" required>
                        <input type="email" name="c_email" value="<?= e($_POST['c_email'] ?? '') ?>" placeholder="邮箱 *" maxlength="190" required>
                        <input type="url" name="c_website" value="<?= e($_POST['c_website'] ?? '') ?>" placeholder="网站（选填）" maxlength="255">
                    </div>
                    <textarea name="c_content" placeholder="说点什么…" maxlength="2000" required><?= e($_POST['c_content'] ?? '') ?></textarea>
                    <div class="cf-row captcha-row">
                        <input type="text" name="c_captcha" placeholder="验证码 *" maxlength="4" required autocomplete="off">
                        <img class="captcha-img" id="captchaImg"
                             src="<?= url('captcha.php?t=' . time()) ?>" alt="验证码"
                             title="看不清？点击刷新" onclick="refreshCaptcha()">
                    </div>
                    <div class="cf-hint">邮箱仅用于显示头像，不会公开。使用 <b>QQ 邮箱</b> 填写，即可自动显示你的 <b>QQ 头像</b>。</div>
                    <div class="btn-row"><button type="submit" name="comment_submit" value="1">发表评论</button></div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function refreshCaptcha() {
    var img = document.getElementById('captchaImg');
    if (!img) return;
    img.src = '<?= url('captcha.php') ?>?t=' + Date.now();
}
</script>

<?php require __DIR__ . '/includes/tpl_footer.php'; ?>
