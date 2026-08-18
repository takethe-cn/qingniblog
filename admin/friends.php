<?php
/**
 * 友情链接管理
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'friends';
$admin_page_title = '友情链接';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add' || $action === 'update') {
            $id   = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $url  = trim($_POST['url'] ?? '');
            $avatar = trim($_POST['avatar'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $sort = (int)($_POST['sort'] ?? 0);
            $status = isset($_POST['status']) ? 1 : 0;

            if ($name === '' || $url === '') {
                $err = '名称和网址为必填项。';
            } else {
                // 校验 URL
                $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
                if (!in_array($scheme, ['http', 'https'], true)) {
                    $err = '网址必须以 http:// 或 https:// 开头。';
                } elseif ($avatar !== '' && !in_array(strtolower((string)parse_url($avatar, PHP_URL_SCHEME)), ['http', 'https', ''], true)) {
                    $err = '头像地址不合法。';
                } else {
                    if ($action === 'add') {
                        db()->prepare('INSERT INTO friends (name, url, avatar, description, sort, status, created_at) VALUES (?,?,?,?,?,?,?)')
                            ->execute([$name, $url, $avatar, $desc, $sort, $status, date('Y-m-d H:i:s')]);
                        $msg = '友链已添加。';
                    } else {
                        db()->prepare('UPDATE friends SET name=?, url=?, avatar=?, description=?, sort=?, status=? WHERE id=?')
                            ->execute([$name, $url, $avatar, $desc, $sort, $status, $id]);
                        $msg = '友链已更新。';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            db()->prepare('DELETE FROM friends WHERE id = ?')->execute([$id]);
            $msg = '友链已删除。';
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            db()->prepare('UPDATE friends SET status = 1 - status WHERE id = ?')->execute([$id]);
            $msg = '状态已更新。';
        }
    } catch (Exception $e) {
        $err = '操作失败：' . e($e->getMessage());
    }
}

$friends = db()->query('SELECT * FROM friends ORDER BY sort ASC, id ASC')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    foreach ($friends as $f) {
        if ((int)$f['id'] === (int)$_GET['edit']) { $edit = $f; break; }
    }
}

require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<div class="grid2" style="align-items:start">
    <div class="card">
        <h3><?= $edit ? '编辑友链' : '添加友链' ?></h3>
        <form method="post" action="friends.php<?= $edit ? '?edit=' . (int)$edit['id'] : '' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
            <div class="field">
                <label>名称</label>
                <input type="text" name="name" value="<?= $edit ? e($edit['name']) : '' ?>" required>
            </div>
            <div class="field">
                <label>网址</label>
                <input type="url" name="url" value="<?= $edit ? e($edit['url']) : '' ?>" placeholder="https://" required>
            </div>
            <div class="field">
                <label>头像 URL <span class="hint">可选</span></label>
                <input type="text" name="avatar" value="<?= $edit ? e($edit['avatar']) : '' ?>" placeholder="https:// 或留空使用默认头像">
            </div>
            <div class="field">
                <label>一句话介绍</label>
                <input type="text" name="description" value="<?= $edit ? e($edit['description']) : '' ?>">
            </div>
            <div class="grid2">
                <div class="field">
                    <label>排序（数字越小越靠前）</label>
                    <input type="number" name="sort" value="<?= $edit ? (int)$edit['sort'] : 0 ?>">
                </div>
                <div class="field">
                    <label>状态</label>
                    <div class="checkline" style="margin-top:10px">
                        <input type="checkbox" name="status" id="st_status" <?= (!$edit || $edit['status']) ? 'checked' : '' ?>>
                        <label for="st_status">显示</label>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn"><?= $edit ? '保存修改' : '添加友链' ?></button>
                <?php if ($edit): ?><a class="btn btn-ghost" href="friends.php">取消</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>全部友链（<?= count($friends) ?>）</h3>
        <?php if ($friends): ?>
        <table class="table">
            <thead><tr><th>名称</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($friends as $f): ?>
            <tr>
                <td>
                    <?= e($f['name']) ?>
                    <?php if ($f['description']): ?><br><span style="color:var(--muted);font-size:12px"><?= e($f['description']) ?></span><?php endif; ?>
                </td>
                <td><?= (int)$f['sort'] ?></td>
                <td><?= $f['status'] ? '<span class="tag">显示</span>' : '<span class="tag off">隐藏</span>' ?></td>
                <td class="ops">
                    <a href="friends.php?edit=<?= (int)$f['id'] ?>">编辑</a>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <button type="submit" class="btn-link" style="background:none;border:none;color:var(--lime-deep);cursor:pointer;font-size:13px;padding:0;font-family:inherit"><?= $f['status'] ? '隐藏' : '显示' ?></button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('确定删除该友链？')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <button type="submit" class="btn-link" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:13px;padding:0;font-family:inherit">删除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:var(--muted)">暂无友链，使用左侧表单添加第一条吧。</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/tpl_footer.php'; ?>
