/* 青柠博客 - 前台脚本 */
(function () {
    'use strict';

    /* 预加载器 */
    var preloader = document.getElementById('preloader');
    if (preloader) {
        var minTime = 700;          // 最短展示时间，避免闪屏
        var started = Date.now();
        function hide() {
            var elapsed = Date.now() - started;
            var delay = Math.max(0, minTime - elapsed);
            setTimeout(function () {
                preloader.classList.add('done');
                setTimeout(function () {
                    if (preloader.parentNode) {
                        preloader.parentNode.removeChild(preloader);
                    }
                }, 500);
            }, delay);
        }
        if (document.readyState === 'complete') {
            hide();
        } else {
            window.addEventListener('load', hide);
            // 兜底：即便个别资源加载缓慢，最迟 4 秒后隐藏
            setTimeout(function () {
                if (preloader.parentNode) { hide(); }
            }, 4000);
        }
    }

    /* 移动端导航 */
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    /* 平滑返回顶部 */
    var backTop = document.getElementById('backTop');
    if (backTop) {
        backTop.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
