<?php
/**
 * 退出登录
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

do_logout();
header('Location: ' . url('admin/login.php'));
exit;
