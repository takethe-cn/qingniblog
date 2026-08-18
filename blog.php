<?php
/**
 * 博客列表页
 * 支持：分页、分类筛选、关键词搜索
 */
require_once __DIR__ . '/includes/bootstrap.php';

$settings = get_all_settings();
$page = 'blog';

$perPage = max(1, (int)($settings['blog_page_size'] ?? 8));
$current = max(1, (int)($_GET['p'] ?? 1));
$cat     = trim($_GET['cat'] ?? '');
$q       = trim($_GET['q'] ?? '');

// 构建查询
$where  = ['status = 1'];
$params = [];
if ($cat !== '') {
    $where[] = 'category = ?';
    $params[] = $cat;
}
if ($q !== '') {
    $where[] = '(title LIKE ? OR tags LIKE ? OR content_md LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

// 分类列表（用于筛选）
$categories = [];
try {
    $stmt = db()->query('SELECT category, COUNT(*) c FROM posts WHERE status = 1 AND category <> \'\' GROUP BY category ORDER BY c DESC');
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

$total = 0;
$posts = [];
try {
    $stmt = db()->prepare('SELECT COUNT(*) FROM posts WHERE ' . $whereSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $offset = ($current - 1) * $perPage;
    $stmt = db()->prepare(
        'SELECT id, title, slug, summary, cover, category, tags, is_pinned, content_html, created_at, views
         FROM posts WHERE ' . $whereSql . '
         ORDER BY is_pinned DESC, created_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    foreach ($params as $i => $v) {
        $stmt->bindValue($i + 1, $v);
    }
    $stmt->execute();
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    $posts = [];
}

// 评论数（批量查询）
$commentCounts = [];
try {
    $commentCounts = comments_count_map(array_column($posts, 'id'));
} catch (Exception $e) {
    $commentCounts = [];
}

// 分页 URL
$queryParts = [];
if ($cat !== '') { $queryParts[] = 'cat=' . rawurlencode($cat); }
if ($q !== '')   { $queryParts[] = 'q=' . rawurlencode($q); }
$baseUrl = 'blog.php' . ($queryParts ? '?' . implode('&', $queryParts) . '&p={page}' : '?p={page}');

$page_title = '博客';
require __DIR__ . '/includes/tpl_header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Blog</div>
            <h2><?= e($settings['blog_title'] ?: '文章列表') ?></h2>
            <p class="sub"><?= e($settings['blog_subtitle'] ?: '用文字记录走过的路') ?></p>
        </div>

        <div class="toolbar">
            <div class="chips">
                <a class="chip <?= $cat === '' ? 'active' : '' ?>" href="<?= url('blog.php') ?>">全部</a>
                <?php foreach ($categories as $c): ?>
                <a class="chip <?= $cat === $c['category'] ? 'active' : '' ?>"
                   href="<?= url('blog.php?cat=' . rawurlencode($c['category'])) ?>"><?= e($c['category']) ?></a>
                <?php endforeach; ?>
            </div>
            <form class="search-form" method="get" action="<?= url('blog.php') ?>">
                <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="搜索文章…" aria-label="搜索">
                <button type="submit">搜索</button>
            </form>
        </div>

        <?php if ($posts): ?>
        <div class="blog-list">
            <?php foreach ($posts as $p): ?>
            <article class="post-item">
                <?php if (!empty($p['cover'])): ?>
                <a class="cover-link" href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>">
                    <img class="cover" src="<?= e($p['cover']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                </a>
                <?php endif; ?>
                <div class="info">
                    <h3><a href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>"><?= e($p['title']) ?><?= $p['is_pinned'] ? ' <span style="color:var(--lemon)">置顶</span>' : '' ?></a></h3>
                    <p><?= e($p['summary'] ?: excerpt($p['content_html'] ?? '', 100)) ?></p>
                    <div class="meta">
                        <?php if (!empty($p['category'])): ?><span class="cat"><?= e($p['category']) ?></span><?php endif; ?>
                        <span><?= fmt_date($p['created_at']) ?></span>
                        <span><?= (int)($commentCounts[$p['id']] ?? 0) ?> 条评论</span>
                        <span><?= (int)$p['views'] ?> 次浏览</span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?= paginate($total, $perPage, $current, $baseUrl) ?>
        <?php if ((int)ceil($total / $perPage) <= 1): ?>
        <div class="list-end">— 到底了 —</div>
        <?php endif; ?>
        <?php else: ?>
        <div class="empty">
            <h2>暂无文章</h2>
            <?= $q !== '' ? '<p>没有找到与「' . e($q) . '」相关的文章。</p>' : '<p>这里还空空如也，去后台发布第一篇吧。</p>' ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/tpl_footer.php'; ?>
