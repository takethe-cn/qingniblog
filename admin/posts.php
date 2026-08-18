<?php
/**
 * 文章管理列表
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'posts';
$admin_page_title = '文章管理';
$msg = '';
$err = '';

// 处理操作（均在 POST 下，带 CSRF）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'delete' && $id > 0) {
            db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
            $msg = '文章已删除。';
        } elseif ($action === 'toggle_status' && $id > 0) {
            db()->prepare('UPDATE posts SET status = 1 - status WHERE id = ?')->execute([$id]);
            $msg = '文章状态已更新。';
        } elseif ($action === 'toggle_pin' && $id > 0) {
            db()->prepare('UPDATE posts SET is_pinned = 1 - is_pinned WHERE id = ?')->execute([$id]);
            $msg = '置顶状态已更新。';
        }
    } catch (Exception $e) {
        $err = '操作失败：' . e($e->getMessage());
    }
}

// 列表 + 筛选
$q = trim($_GET['q'] ?? '');
$st = $_GET['st'] ?? '';
$perPage = 15;
$current = max(1, (int)($_GET['p'] ?? 1));

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR content_md LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . $q . '%');
}
if ($st === '1' || $st === '0') {
    $where[] = 'status = ?';
    $params[] = (int)$st;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = 0;
try {
    $stmt = db()->prepare('SELECT COUNT(*) FROM posts ' . $whereSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $offset = ($current - 1) * $perPage;
    $sql = 'SELECT id, title, category, status, is_pinned, views, created_at FROM posts ' . $whereSql . ' ORDER BY id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}

$queryParts = [];
if ($q !== '') { $queryParts[] = 'q=' . rawurlencode($q); }
if ($st !== '') { $queryParts[] = 'st=' . rawurlencode($st); }
$baseUrl = 'posts.php' . ($queryParts ? '?' . implode('&', $queryParts) . '&p={page}' : '?p={page}');

require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <div class="card-title-row">
        <h3>全部文章（<?= $total ?>）</h3>
        <a class="btn" href="<?= url('admin/post-edit.php') ?>">+ 写文章</a>
    </div>

    <form class="toolbar" method="get" action="posts.php" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="搜索标题或正文…" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;flex:1;min-width:160px">
        <select name="st" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px">
            <option value="">全部状态</option>
            <option value="1" <?= $st === '1' ? 'selected' : '' ?>>已发布</option>
            <option value="0" <?= $st === '0' ? 'selected' : '' ?>>草稿</option>
        </select>
        <button class="btn btn-sm btn-ghost" type="submit">筛选</button>
    </form>

    <?php if ($rows): ?>
    <table class="table">
        <thead>
        <tr><th>标题</th><th>分类</th><th>状态</th><th>浏览</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
        <tr>
            <td>
                <?= e($p['title']) ?>
                <?= $p['is_pinned'] ? '<span class="tag pin">置顶</span>' : '' ?>
            </td>
            <td><?= $p['category'] ? e($p['category']) : '<span style="color:var(--muted)">—</span>' ?></td>
            <td><?= $p['status'] ? '<span class="tag">已发布</span>' : '<span class="tag off">草稿</span>' ?></td>
            <td><?= (int)$p['views'] ?></td>
            <td><?= e(fmt_datetime($p['created_at'])) ?></td>
            <td class="ops">
                <a href="<?= url('admin/post-edit.php?id=' . (int)$p['id']) ?>">编辑</a>
                <a href="<?= url('post.php?id=' . (int)$p['id']) ?>" target="_blank">查看</a>
                <form method="post" style="display:inline" onsubmit="return confirm('确定切换发布状态？')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn-link" style="background:none;border:none;color:var(--lime-deep);cursor:pointer;font-size:13px;padding:0;font-family:inherit"><?= $p['status'] ? '转草稿' : '发布' ?></button>
                </form>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_pin">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn-link" style="background:none;border:none;color:var(--lime-deep);cursor:pointer;font-size:13px;padding:0;font-family:inherit"><?= $p['is_pinned'] ? '取消置顶' : '置顶' ?></button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('确定删除该文章？此操作不可恢复。')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn-link" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:13px;padding:0;font-family:inherit">删除</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= paginate($total, $perPage, $current, $baseUrl) ?>
    <?php else: ?>
    <p style="color:var(--muted)">没有符合条件的文章。</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/tpl_footer.php'; ?>
