<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : '博客' ?> - 安装向导</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root {
        --lime: #84cc16;
        --lime-dark: #4d7c0f;
        --lemon: #facc15;
        --bg: #f7f9f0;
        --card: #ffffff;
        --text: #1f2a18;
        --muted: #6b7280;
        --border: #e5e7eb;
        --danger: #dc2626;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        background: linear-gradient(160deg, #f0f7e3 0%, var(--bg) 40%, #fffbe8 100%);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 40px 16px 60px;
    }
    .wrap { width: 100%; max-width: 640px; }
    .logo {
        font-size: 26px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 6px;
        letter-spacing: 1px;
    }
    .logo .dot { color: var(--lime); }
    .tagline { text-align: center; color: var(--muted); margin-bottom: 26px; font-size: 14px; }
    .steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 22px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 14px 18px;
    }
    .step { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 14px; }
    .step span {
        width: 22px; height: 22px; border-radius: 50%;
        background: #eef2e6; color: var(--muted);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700;
    }
    .step.on { color: var(--lime-dark); font-weight: 600; }
    .step.on span { background: var(--lime); color: #fff; }
    .box {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 26px 28px;
        box-shadow: 0 4px 18px rgba(132, 204, 22, 0.08);
    }
    .box h2 { margin: 0 0 8px; font-size: 20px; }
    .box p { color: var(--muted); line-height: 1.7; }
    .box.warn { border-color: #fde047; background: #fffbeb; }
    .box.error { border-color: #fca5a5; background: #fef2f2; }
    .box.success { border-color: var(--lime); background: #f7fee7; }
    .box.success h2 { color: var(--lime-dark); }
    .box ul { padding-left: 20px; color: #374151; line-height: 1.9; }
    table.req { width: 100%; border-collapse: collapse; margin: 12px 0; }
    table.req td { padding: 9px 6px; border-bottom: 1px dashed var(--border); font-size: 14px; }
    table.req td.st { text-align: right; }
    .pass { color: #16a34a; font-weight: 600; }
    .fail { color: var(--danger); font-weight: 600; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
    .field input {
        width: 100%; padding: 10px 12px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 15px; background: #fcfdf9; transition: border-color .15s, box-shadow .15s;
    }
    .field input:focus { outline: none; border-color: var(--lime); box-shadow: 0 0 0 3px rgba(132,204,22,.18); }
    .field .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
    hr { border: none; border-top: 1px dashed var(--border); margin: 20px 0; }
    .actions { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
    .btn {
        display: inline-block; padding: 10px 20px;
        border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600;
        border: 1px solid transparent; cursor: pointer;
        background: linear-gradient(120deg, var(--lime), #a3e635);
        color: #12320a; transition: transform .12s, box-shadow .12s;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(132,204,22,.35); }
    .btn-ghost { background: transparent; border-color: var(--border); color: var(--text); }
    .btn-ghost:hover { box-shadow: none; border-color: var(--lime); }
    .btn-danger { background: transparent; border-color: #fca5a5; color: var(--danger); }
    .btn-danger:hover { box-shadow: none; background: #fef2f2; }
    code { background: #f1f5e9; padding: 2px 6px; border-radius: 5px; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="logo">青柠<span class="dot">·</span>博客 安装向导</div>
    <div class="tagline">用青柠绿与柠檬黄，记录生活与代码</div>
