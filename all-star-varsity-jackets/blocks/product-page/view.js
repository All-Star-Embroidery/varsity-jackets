(function () {
    'use strict';

    function init(root) {
        var main = root.querySelector('[data-asevj-vjpp-main]');
        if (!main) return;

        var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-asevj-vjpp-thumb]'));
        thumbs.forEach(function (button) {
            button.addEventListener('click', function () {
                var src = button.getAttribute('data-full');
                if (!src) return;
                main.src = src;
                main.alt = button.getAttribute('data-alt') || '';
                thumbs.forEach(function (item) {
                    var active = item === button;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        });
    }

    function boot() {
        document.querySelectorAll('.asevj-vjpp').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
