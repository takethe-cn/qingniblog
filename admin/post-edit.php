<?php
/**
 * 文章编辑（Markdown）
 * 支持：在线编写（带工具栏与实时预览）、上传 .md 文件、封面上传/URL、正文插图上传/URL
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'posts';
$id = (int)($_GET['id'] ?? 0);
$post = null;
if ($id > 0) {
    try {
        $stmt = db()->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $post = $stmt->fetch();
    } catch (Exception $e) { $post = null; }
    if (!$post) {
        die('文章不存在');
    }
}

// 编辑态默认值
$title    = $post['title'] ?? '';
$slug     = $post['slug'] ?? '';
$category = $post['category'] ?? '';
$tags     = $post['tags'] ?? '';
$cover    = $post['cover'] ?? '';
$summary  = $post['summary'] ?? '';
$content  = $post['content_md'] ?? '';
$isPinned = (int)($post['is_pinned'] ?? 0);
$status   = (int)($post['status'] ?? 1);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $slug     = trim($_POST['slug'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags     = trim($_POST['tags'] ?? '');
    $cover    = trim($_POST['cover'] ?? '');
    $summary  = trim($_POST['summary'] ?? '');
    $content  = (string)($_POST['content_md'] ?? '');
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
    $status   = isset($_POST['status']) ? 1 : 0;

    if ($title === '') {
        $err = '标题不能为空。';
    }
    if ($slug === '') {
        $slug = slugify($title, 'post-' . time());
    }
    // 唯一性检查
    try {
        $stmt = db()->prepare('SELECT id FROM posts WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . random_str(4);
        }
    } catch (Exception $e) { /* 忽略 */ }

    // 校验封面/图片 URL 协议（仅允许 http/https 或相对路径）
    $safeUrl = static function ($v) {
        $scheme = strtolower((string)parse_url($v, PHP_URL_SCHEME));
        return $scheme === '' || in_array($scheme, ['http', 'https'], true);
    };
    if ($cover !== '' && !$safeUrl($cover)) {
        $err = '封面地址不合法。';
    }

    if (!$err) {
        try {
            $html = markdown_to_html($content);
            $now  = date('Y-m-d H:i:s');
            if ($id > 0) {
                $stmt = db()->prepare(
                    'UPDATE posts SET title=?, slug=?, category=?, tags=?, cover=?, summary=?, content_md=?, content_html=?, is_pinned=?, status=?, updated_at=? WHERE id=?'
                );
                $stmt->execute([$title, $slug, $category, $tags, $cover, $summary, $content, $html, $isPinned, $status, $now, $id]);
                $msg = '文章已更新。';
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO posts (title, slug, category, tags, cover, summary, content_md, content_html, is_pinned, status, views, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,0,?,?)'
                );
                $stmt->execute([$title, $slug, $category, $tags, $cover, $summary, $content, $html, $isPinned, $status, $now, $now]);
                $id = (int)db()->lastInsertId();
                $msg = '文章已保存。';
            }
        } catch (Exception $e) {
            $err = '保存失败：' . e($e->getMessage());
        }
    }
}

$images = list_uploads();
$admin_page_title = $id > 0 ? '编辑文章' : '写文章';
require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<form method="post" action="<?= url('admin/post-edit.php?id=' . $id) ?>" id="postForm">
    <?= csrf_field() ?>

    <div class="card">
        <div class="field">
            <label>标题 <span class="hint">必填</span></label>
            <input type="text" name="title" id="fTitle" value="<?= e($title) ?>" required>
        </div>
        <div class="grid2">
            <div class="field">
                <label>分类</label>
                <input type="text" name="category" id="fCategory" value="<?= e($category) ?>" placeholder="例如：生活 / 技术">
            </div>
            <div class="field">
                <label>标签 <span class="hint">用英文逗号分隔</span></label>
                <input type="text" name="tags" id="fTags" value="<?= e($tags) ?>" placeholder="例如：随笔, 想法">
            </div>
        </div>
        <div class="field">
            <label>摘要 <span class="hint">留空则自动从正文截取</span></label>
            <textarea name="summary" rows="2"><?= e($summary) ?></textarea>
        </div>
        <div class="field">
            <label>封面图 <span class="hint">支持本地上传或直接填写图片 URL</span></label>
            <div style="display:flex;gap:10px;align-items:center">
                <input type="text" name="cover" id="fCover" value="<?= e($cover) ?>" placeholder="https:// 或相对路径（留空则无封面）">
                <button type="button" class="btn btn-ghost btn-sm" id="btnPickCover">选择</button>
            </div>
        </div>
        <div class="grid2">
            <div class="field">
                <label>链接别名（slug）<span class="hint">留空自动生成</span></label>
                <input type="text" name="slug" value="<?= e($slug) ?>">
            </div>
            <div class="field">
                <label>选项</label>
                <div class="checkline"><input type="checkbox" name="status" id="chkStatus" <?= $status ? 'checked' : '' ?>><label for="chkStatus">立即发布（取消则为草稿）</label></div>
                <div class="checkline"><input type="checkbox" name="is_pinned" id="chkPinned" <?= $isPinned ? 'checked' : '' ?>><label for="chkPinned">置顶显示</label></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title-row">
            <h3>正文（Markdown）</h3>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-ghost btn-sm" id="btnImportMd">上传 .md 文件</button>
                <input type="file" id="mdFileInput" accept=".md,.markdown,text/markdown" style="display:none">
                <button type="submit" class="btn btn-sm">保存文章</button>
            </div>
        </div>

        <div class="editor-toolbar" id="editorToolbar">
            <button type="button" data-cmd="h1" title="一级标题">H1</button>
            <button type="button" data-cmd="h2" title="二级标题">H2</button>
            <button type="button" data-cmd="h3" title="三级标题">H3</button>
            <button type="button" data-cmd="bold" title="加粗">B</button>
            <button type="button" data-cmd="italic" title="斜体">I</button>
            <button type="button" data-cmd="strike" title="删除线">S</button>
            <button type="button" data-cmd="quote" title="引用">引用</button>
            <button type="button" data-cmd="code" title="行内代码">代码</button>
            <button type="button" data-cmd="codeblock" title="代码块">代码块</button>
            <button type="button" data-cmd="link" title="链接">链接</button>
            <button type="button" data-cmd="image" title="插入图片">图片</button>
            <button type="button" data-cmd="ul" title="无序列表">列表</button>
            <button type="button" data-cmd="ol" title="有序列表">序号</button>
            <button type="button" data-cmd="table" title="表格">表格</button>
            <button type="button" data-cmd="task" title="任务清单">任务</button>
            <button type="button" data-cmd="hr" title="分割线">—</button>
        </div>
        <div class="editor-split">
            <textarea name="content_md" id="mdEditor" spellcheck="false" placeholder="在这里使用 Markdown 书写正文…&#10;&#10;支持：标题、加粗、斜体、引用、列表、代码块、表格、任务清单、图片、链接等全部 Markdown 语法。"><?= e($content) ?></textarea>
            <div class="preview-pane" id="previewPane"><div class="preview-empty">输入内容后将在此实时预览（全屏：Ctrl+Shift+P）</div></div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn">保存文章</button>
        <a class="btn btn-ghost" href="<?= url('admin/posts.php') ?>">返回列表</a>
        <?php if ($id > 0): ?>
        <a class="btn btn-ghost" href="<?= url('post.php?id=' . $id) ?>" target="_blank">预览</a>
        <?php endif; ?>
    </div>
</form>

<!-- 图片选择弹窗 -->
<div class="modal-mask" id="imgModal" style="display:none">
    <div class="modal">
        <div class="modal-head">
            <b>插入 / 上传图片</b>
            <button type="button" class="modal-close" id="imgModalClose">×</button>
        </div>
        <div class="modal-tabs">
            <button type="button" class="tab on" data-tab="upload">本地上传</button>
            <button type="button" class="tab" data-tab="url">图片 URL</button>
            <button type="button" class="tab" data-tab="lib">图库（<?= count($images) ?>）</button>
        </div>
        <div class="modal-body">
            <div class="tab-pane" data-pane="upload">
                <input type="file" id="modalUploadInput" accept="image/*" style="display:none">
                <button type="button" class="btn" id="btnModalUpload">选择图片上传</button>
                <div id="uploadHint" class="help" style="margin-top:10px">支持 jpg/png/gif/webp/bmp，最大 8MB。上传成功后自动插入正文。</div>
            </div>
            <div class="tab-pane" data-pane="url" style="display:none">
                <div class="field" style="margin:0">
                    <input type="text" id="modalUrlInput" placeholder="https://example.com/image.png">
                </div>
                <button type="button" class="btn" id="btnModalUrlInsert">插入该图片链接</button>
            </div>
            <div class="tab-pane" data-pane="lib" style="display:none">
                <?php if ($images): ?>
                <div class="img-picker" id="imgPicker">
                    <?php foreach ($images as $img): ?>
                    <div class="item" data-url="<?= e($img['url']) ?>">
                        <img src="<?= e($img['url']) ?>" alt="<?= e($img['name']) ?>" loading="lazy">
                        <div class="fn"><?= e($img['name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--muted)">图库为空，可先在本地上传页面上传。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.modal-mask { position: fixed; inset: 0; background: rgba(20,28,12,.45); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal { width: 620px; max-width: 94vw; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.modal-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--border); }
.modal-close { background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted); }
.modal-tabs { display: flex; border-bottom: 1px solid var(--border); }
.modal-tabs .tab { flex: 1; padding: 10px; border: none; background: #fafbf6; cursor: pointer; font-size: 14px; color: var(--muted); border-bottom: 2px solid transparent; }
.modal-tabs .tab.on { color: var(--lime-deep); border-bottom-color: var(--lime); background: #fff; font-weight: 600; }
.modal-body { padding: 18px; max-height: 60vh; overflow-y: auto; }
</style>

<script src="<?= url('assets/vendor/marked.min.js') ?>"></script>
<script src="<?= url('assets/vendor/purify.min.js') ?>"></script>
<script>
window.ZBLOG = {
    uploadUrl: <?= json_encode(url('admin/upload.php')) ?>,
    csrf: <?= json_encode(csrf_token()) ?>,
    insertInto: 'editor' // 'editor' 或 'cover'
};
</script>
<script src="<?= url('assets/js/editor.js') ?>"></script>

<?php require __DIR__ . '/tpl_footer.php'; ?>
