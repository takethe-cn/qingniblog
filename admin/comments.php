<?php
/**
 * 评论管理
 * 支持：通过审核 / 删除 / 分页
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();
csrf_guard();

$admin_page = 'comments';
$admin_page_title = '评论管理';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && in_array($action, ['approve', 'delete'], true)) {
        try {
            if ($action === 'approve') {
                db()->prepare('UPDATE comments SET status = 1 WHERE id = ?')->execute([$id]);
                $msg = '评论已通过审核。';
            } else {
                db()->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
                $msg = '评论已删除。';
            }
        } catch (Exception $e) {
            $err = '操作失败：' . $e->getMessage();
        }
    }
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$data = get_all_comments($page, $perPage);
$comments = $data['items'];
$total = $data['total'];

require __DIR__ . '/tpl_header.php';
?>

<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>全部评论（<?= (int)$total ?>）</h3>
    <p style="color:var(--muted);margin-top:0">新评论默认待审核，审核通过后才会在前台显示。</p>
    <?php if ($comments): ?>
    <table class="table">
        <thead>
            <tr>
                <th style="width:56px">头像</th>
                <th>昵称 / 邮箱 / 网站</th>
                <th>评论内容</th>
                <th style="width:120px">所属文章</th>
                <th style="width:150px">时间</th>
                <th style="width:110px">状态</th>
                <th style="width:160px">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
                <td>
                    <?php if (!empty($c['avatar'])): ?>
                    <img src="<?= e($c['avatar']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                    <?php else: ?>
                    <img src="<?= url('assets/img/avatar-placeholder.svg') ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= e($c['name']) ?></strong>
                    <div class="muted"><?= e($c['email']) ?></div>
                    <?php if (!empty($c['website'])): ?><div class="muted"><a href="<?= e($c['website']) ?>" target="_blank" rel="noopener nofollow"><?= e($c['website']) ?></a></div><?php endif; ?>
                </td>
                <td style="max-width:280px"><?= e(mb_strimwidth($c['content'], 0, 80, '…')) ?></td>
                <td>
                    <?php if (!empty($c['post_slug'])): ?>
                    <a href="<?= url('post.php?slug=' . rawurlencode($c['post_slug'])) ?>" target="_blank"><?= e(mb_strimwidth($c['post_title'] ?? '未知', 0, 14, '…')) ?></a>
                    <?php else: ?><span class="muted">文章已删除</span><?php endif; ?>
                </td>
                <td class="muted"><?= fmt_datetime($c['created_at']) ?></td>
                <td>
                    <?php if ((int)$c['status'] === 1): ?>
                    <span class="badge ok">已通过</span>
                    <?php else: ?>
                    <span class="badge">待审核</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((int)$c['status'] !== 1): ?>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="btn btn-sm">通过</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('确定删除该评论吗？');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= paginate($total, $perPage, $page, url('admin/comments.php?p={page}')) ?>
    <?php else: ?>
    <div class="empty">暂无评论</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/tpl_footer.php'; ?>
