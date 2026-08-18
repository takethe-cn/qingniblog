<?php
/**
 * 图片上传接口（后台）
 * POST：上传图片，返回 JSON
 * 需登录 + CSRF
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => '仅支持 POST 请求']);
    exit;
}
if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => '请求校验失败（CSRF）']);
    exit;
}

$res = handle_image_upload('image');
echo json_encode($res);
