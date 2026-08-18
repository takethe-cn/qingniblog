/* 青柠博客 - Markdown 编辑器 */
(function () {
    'use strict';

    var editor = document.getElementById('mdEditor');
    var preview = document.getElementById('previewPane');
    if (!editor || !preview) return;

    var cfg = window.ZBLOG || {};
    var insertTarget = 'editor'; // 'editor' | 'cover'
    var lastCursorPos = 0;

    /* ---------- 实时预览 ---------- */
    function renderPreview() {
        var raw = editor.value;
        if (!raw.trim()) {
            preview.innerHTML = '<div class="preview-empty">输入内容后将在此实时预览</div>';
            return;
        }
        try {
            var html = marked.parse(raw);
            html = DOMPurify.sanitize(html, { ADD_ATTR: ['target'] });
            // 代码块高亮类名保留
            preview.innerHTML = '<div class="markdown-body">' + html + '</div>';
        } catch (e) {
            preview.innerHTML = '<div class="preview-empty">预览渲染出错</div>';
        }
    }
    editor.addEventListener('input', renderPreview);
    renderPreview();

    /* ---------- 文本选区工具 ---------- */
    function wrapSelection(before, after, placeholder) {
        var start = editor.selectionStart, end = editor.selectionEnd;
        var sel = editor.value.slice(start, end);
        if (!sel) sel = placeholder || 'text';
        var insert = before + sel + after;
        editor.setRangeText(insert, start, end, 'end');
        editor.focus();
        renderPreview();
    }
    function insertAtCursor(text) {
        var start = editor.selectionStart;
        editor.setRangeText(text, start, editor.selectionEnd, 'end');
        editor.focus();
        renderPreview();
    }

    /* ---------- 工具栏 ---------- */
    var commands = {
        h1:  function () { wrapSelection('\n# ', '\n', '一级标题'); },
        h2:  function () { wrapSelection('\n## ', '\n', '二级标题'); },
        h3:  function () { wrapSelection('\n### ', '\n', '三级标题'); },
        bold:function () { wrapSelection('**', '**', '加粗文字'); },
        italic:function () { wrapSelection('*', '*', '斜体文字'); },
        strike:function () { wrapSelection('~~', '~~', '删除线'); },
        quote:function () { wrapSelection('\n> ', '\n', '引用内容'); },
        code:function () { wrapSelection('`', '`', 'code'); },
        codeblock:function () { wrapSelection('\n```\n', '\n```\n', '代码内容'); },
        link:function () {
            var url = prompt('请输入链接地址：', 'https://');
            if (url) wrapSelection('[', '](' + url + ')', '链接文字');
        },
        image:function () { openImageModal('editor'); },
        ul:  function () { wrapSelection('\n- ', '\n', '列表项'); },
        ol:  function () { wrapSelection('\n1. ', '\n', '列表项'); },
        task:function () { wrapSelection('\n- [ ] ', '\n', '待办事项'); },
        table:function () {
            insertAtCursor('\n| 列一 | 列二 | 列三 |\n| --- | --- | --- |\n| 内容 | 内容 | 内容 |\n');
        },
        hr:  function () { insertAtCursor('\n---\n'); }
    };
    document.getElementById('editorToolbar').addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-cmd]');
        if (!btn) return;
        var cmd = commands[btn.getAttribute('data-cmd')];
        if (cmd) cmd();
    });

    /* 快捷键：Ctrl+B 加粗，Ctrl+Shift+P 全屏预览 */
    editor.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') { e.preventDefault(); commands.bold(); }
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'p') { e.preventDefault(); preview.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });

    /* ---------- 上传 .md 文件 ---------- */
    var mdInput = document.getElementById('mdFileInput');
    var btnImport = document.getElementById('btnImportMd');
    if (btnImport && mdInput) {
        btnImport.addEventListener('click', function () { mdInput.click(); });
        mdInput.addEventListener('change', function () {
            var file = mdInput.files[0];
            if (!file) return;
            if (!/\.(md|markdown)$/i.test(file.name)) { alert('仅支持 .md / .markdown 文件'); mdInput.value = ''; return; }
            var reader = new FileReader();
            reader.onload = function (ev) {
                var text = ev.target.result;
                // 解析 front matter，填充标题/分类/标签
                var m = text.match(/^---\s*\r?\n([\s\S]*?)\r?\n---\s*\r?\n?/);
                var body = text;
                if (m) {
                    var meta = {};
                    m[1].split(/\r?\n/).forEach(function (line) {
                        var i = line.indexOf(':');
                        if (i > -1) meta[line.slice(0, i).trim()] = line.slice(i + 1).trim().replace(/^["']|["']$/g, '');
                    });
                    if (meta.title && !document.getElementById('fTitle').value) document.getElementById('fTitle').value = meta.title;
                    if (meta.category && !document.getElementById('fCategory').value) document.getElementById('fCategory').value = meta.category;
                    if (meta.tags && !document.getElementById('fTags').value) document.getElementById('fTags').value = meta.tags;
                    body = text.slice(m[0].length);
                }
                editor.value = body.replace(/^\r?\n/, '');
                renderPreview();
                editor.focus();
                alert('已导入「' + file.name + '」' + (m ? '，并自动填充了元信息。' : '。'));
                mdInput.value = '';
            };
            reader.readAsText(file);
        });
    }

    /* ---------- 图片弹窗 ---------- */
    var modal = document.getElementById('imgModal');
    function openImageModal(target) {
        insertTarget = target || 'editor';
        lastCursorPos = editor.selectionStart;
        if (modal) modal.style.display = 'flex';
    }
    function closeImageModal() { if (modal) modal.style.display = 'none'; }
    if (document.getElementById('imgModalClose')) {
        document.getElementById('imgModalClose').addEventListener('click', closeImageModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeImageModal(); });
    }

    /* 选项卡 */
    var tabs = document.querySelectorAll('.modal-tabs .tab');
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            tabs.forEach(function (x) { x.classList.remove('on'); });
            t.classList.add('on');
            document.querySelectorAll('.tab-pane').forEach(function (p) { p.style.display = 'none'; });
            var pane = document.querySelector('.tab-pane[data-pane="' + t.getAttribute('data-tab') + '"]');
            if (pane) pane.style.display = '';
        });
    });

    /* 插入图片到目标 */
    function doInsertImage(url) {
        closeImageModal();
        if (insertTarget === 'cover') {
            document.getElementById('fCover').value = url;
        } else {
            var md = '![' + (url.split('/').pop() || '图片') + '](' + url + ')';
            editor.setRangeText(md, lastCursorPos, lastCursorPos, 'end');
            editor.focus();
            renderPreview();
        }
    }

    /* 封面上传选择 */
    var btnPickCover = document.getElementById('btnPickCover');
    if (btnPickCover) {
        btnPickCover.addEventListener('click', function () {
            var tab = document.querySelector('.modal-tabs .tab[data-tab="lib"]');
            if (tab) tab.click();
            openImageModal('cover');
        });
    }

    /* 本地上传 */
    var uploadInput = document.getElementById('modalUploadInput');
    var btnUpload = document.getElementById('btnModalUpload');
    var uploadHint = document.getElementById('uploadHint');
    if (uploadInput && btnUpload) {
        btnUpload.addEventListener('click', function () { uploadInput.click(); });
        uploadInput.addEventListener('change', function () {
            var file = uploadInput.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fd.append('csrf_token', cfg.csrf);
            uploadHint.textContent = '正在上传…';
            fetch(cfg.uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) {
                        uploadHint.textContent = '上传成功：' + res.url;
                        doInsertImage(res.url);
                    } else {
                        uploadHint.textContent = '上传失败：' + (res.msg || '未知错误');
                    }
                    uploadInput.value = '';
                })
                .catch(function () {
                    uploadHint.textContent = '上传失败：网络错误';
                    uploadInput.value = '';
                });
        });
    }

    /* URL 插入 */
    var btnUrl = document.getElementById('btnModalUrlInsert');
    var urlInput = document.getElementById('modalUrlInput');
    if (btnUrl && urlInput) {
        btnUrl.addEventListener('click', function () {
            var v = urlInput.value.trim();
            if (!v) return;
            doInsertImage(v);
            urlInput.value = '';
        });
    }

    /* 图库点击 */
    document.querySelectorAll('#imgPicker .item').forEach(function (item) {
        item.addEventListener('click', function () {
            doInsertImage(item.getAttribute('data-url'));
        });
    });
})();
