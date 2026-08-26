(function () {
    'use strict';

    function text(tag, value, className) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        el.textContent = value || '';
        return el;
    }

    function makeLogo(school) {
        var wrap = document.createElement('span');
        wrap.className = 'asevj-school-logo';
        wrap.style.setProperty('--school-primary', school.primary || '#F2B619');
        wrap.style.setProperty('--school-secondary', school.secondary || '#101B31');
        if (school.logo) {
            var img = document.createElement('img');
            img.src = school.logo;
            img.alt = '';
            wrap.appendChild(img);
        } else {
            var b = document.createElement('b');
            b.textContent = school.initials || school.name.charAt(0);
            wrap.appendChild(b);
        }
        return wrap;
    }

    function createModal(root, school, style, startImageIndex) {
        var old = document.querySelector('.asevj-modal');
        if (old) old.remove();

        var modal = document.createElement('div');
        modal.className = 'asevj-modal';
        modal.style.cssText = root.getAttribute('style') || '';
        modal.innerHTML =
            '<div class="asevj-modal__backdrop" data-close></div>' +
            '<div class="asevj-modal__panel" role="dialog" aria-modal="true" aria-label="' + String(style.name || 'Jacket details').replace(/"/g, '&quot;') + '">' +
                '<button class="asevj-modal__close" data-close aria-label="Close">×</button>' +
                '<div class="asevj-modal__content">' +
                    '<div class="asevj-modal__media">' +
                        '<div class="asevj-modal__main"></div>' +
                        '<div class="asevj-modal__thumbs"></div>' +
                    '</div>' +
                    '<div class="asevj-modal__details"></div>' +
                '</div>' +
                '<div class="asevj-modal__sticky" hidden>' +
                    '<div class="asevj-modal__sticky-price"></div>' +
                    '<a class="asevj-btn asevj-btn-primary asevj-modal__sticky-cta" href="#">CUSTOMIZE JACKET</a>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);

        var main = modal.querySelector('.asevj-modal__main');
        var thumbs = modal.querySelector('.asevj-modal__thumbs');
        var details = modal.querySelector('.asevj-modal__details');
        var sticky = modal.querySelector('.asevj-modal__sticky');
        var stickyPrice = modal.querySelector('.asevj-modal__sticky-price');
        var stickyCta = modal.querySelector('.asevj-modal__sticky-cta');
        var allImages = [];

        if (style.imageFull || style.image) {
            allImages.push({
                full: style.imageFull || style.image,
                thumb: style.image || style.imageFull,
                alt: style.name,
                role: 'front'
            });
        }
        (style.gallery || []).forEach(function (image) { allImages.push(image); });

        var thumbButtons = [];

        function showImage(image, activeIndex) {
            main.innerHTML = '';
            if (!image) {
                main.appendChild(text('span', 'No image added yet'));
                return;
            }

            var img = document.createElement('img');
            img.src = image.full || image.thumb;
            img.alt = image.alt || style.name;
            img.decoding = 'async';
            main.appendChild(img);

            thumbButtons.forEach(function (button, index) {
                button.classList.toggle('is-active', index === activeIndex);
                button.setAttribute('aria-pressed', index === activeIndex ? 'true' : 'false');
            });
        }

        if (!allImages.length) {
            showImage(null, -1);
        }

        allImages.forEach(function (image, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Show image ' + (index + 1));
            btn.setAttribute('aria-pressed', 'false');

            var img = document.createElement('img');
            img.src = image.thumb || image.full;
            img.alt = image.alt || '';
            btn.appendChild(img);

            btn.addEventListener('click', function () {
                showImage(image, index);
            });

            thumbButtons.push(btn);
            thumbs.appendChild(btn);
        });

        if (allImages.length) {
            var initialIndex = Number.isFinite(Number(startImageIndex)) ? Number(startImageIndex) : 0;
            initialIndex = Math.max(0, Math.min(initialIndex, allImages.length - 1));
            showImage(allImages[initialIndex], initialIndex);
        }

        details.appendChild(text(
            'div',
            style.galleryMode ? (school.name + ' • SCHOOL GALLERY') : ('STYLE ' + style.number + ' • ' + school.name),
            'asevj-modal__eyebrow'
        ));
        details.appendChild(text('h3', style.name));

        if (style.subtitle) details.appendChild(text('p', style.subtitle));
        if (style.description) details.appendChild(text('p', style.description));

        if (style.features && style.features.length) {
            var ul = document.createElement('ul');
            ul.className = 'asevj-modal__features';
            style.features.forEach(function (feature) {
                ul.appendChild(text('li', feature));
            });
            details.appendChild(ul);
        }

        if (style.priceHtml) {
            var price = document.createElement('div');
            price.className = 'asevj-modal__price';
            price.innerHTML = style.priceHtml;
            details.appendChild(price);

            stickyPrice.innerHTML = '<small>STARTING AT</small><strong>' + style.priceHtml + '</strong>';
        }

        if (style.url) {
            sticky.hidden = false;
            stickyCta.href = style.url;
            stickyCta.textContent = 'CUSTOMIZE JACKET';
        }

        function close() {
            modal.classList.remove('is-open');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            setTimeout(function () { modal.remove(); }, 160);
        }

        modal.querySelectorAll('[data-close]').forEach(function (el) {
            el.addEventListener('click', close);
        });

        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', esc);
                close();
            }
        });

        requestAnimationFrame(function () {
            modal.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        });
    }

    function jacketPlaceholder() {
        var wrap = document.createElement('span');
        wrap.className = 'asevj-jacket-placeholder';
        var silhouette = document.createElement('i');
        var label = document.createElement('b');
        label.textContent = 'JACKET IMAGE';
        wrap.appendChild(silhouette);
        wrap.appendChild(label);
        return wrap;
    }

    function initBrowser(root) {
        var dataNode = root.querySelector('.asevj-data');
        if (!dataNode) return;

        var data;
        try { data = JSON.parse(dataNode.textContent); } catch (e) { return; }
        var schools = data.schools || [];
        if (!schools.length) return;

        var tiles = Array.prototype.slice.call(root.querySelectorAll('.asevj-school-tile'));
        var search = root.querySelector('[data-asevj-search]');
        var district = root.querySelector('[data-asevj-district]');
        var mascot = root.querySelector('[data-asevj-mascot]');
        var reset = root.querySelector('[data-asevj-reset]');
        var noResults = root.querySelector('[data-asevj-no-results]');
        var strip = root.querySelector('[data-asevj-school-strip]');
        var stylePicker = root.querySelector('[data-asevj-style-picker]');
        var galleryStage = root.querySelector('[data-asevj-selected-gallery]');
        var fullGalleryButton = root.querySelector('[data-asevj-full-gallery]');
        var detailButton = root.querySelector('[data-asevj-selected-detail]');
        var customizeButton = root.querySelector('[data-asevj-selected-customize]');
        var currentIndex = Number.isFinite(Number(data.defaultIndex)) ? Number(data.defaultIndex) : 0;
        currentIndex = Math.max(0, Math.min(currentIndex, schools.length - 1));
        var currentStyleIndex = 0;
        var idleTimer = null;
        var hintTimer = null;
        var marqueeRaf = null;
        var marqueeDirection = 1;
        var marqueeLastTs = 0;
        var marqueePaused = true;
        var marqueeDelay = 10000;
        var marqueeSpeed = 18;

        function hideScrollHint() {
            root.classList.remove('show-school-scroll-hint');
            if (hintTimer) {
                clearTimeout(hintTimer);
                hintTimer = null;
            }
        }

        function showScrollHint() {
            root.classList.add('show-school-scroll-hint');
            if (hintTimer) clearTimeout(hintTimer);
            hintTimer = setTimeout(hideScrollHint, 7000);
        }

        function stopMarquee() {
            marqueePaused = true;
            marqueeLastTs = 0;
            if (marqueeRaf) {
                cancelAnimationFrame(marqueeRaf);
                marqueeRaf = null;
            }
        }

        function marqueeStep(ts) {
            if (!strip || marqueePaused) return;
            if (!marqueeLastTs) marqueeLastTs = ts;
            var dt = Math.min(40, ts - marqueeLastTs);
            marqueeLastTs = ts;
            var max = Math.max(0, strip.scrollWidth - strip.clientWidth);
            if (max < 8) {
                stopMarquee();
                return;
            }
            strip.scrollLeft += marqueeDirection * marqueeSpeed * (dt / 1000);
            if (strip.scrollLeft >= max - 1) {
                marqueeDirection = -1;
                strip.scrollLeft = max - 1;
            } else if (strip.scrollLeft <= 1) {
                marqueeDirection = 1;
                strip.scrollLeft = 1;
            }
            marqueeRaf = requestAnimationFrame(marqueeStep);
        }

        function startMarquee() {
            if (!strip || strip.scrollWidth <= strip.clientWidth + 8) return;
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            marqueePaused = false;
            marqueeLastTs = 0;
            marqueeRaf = requestAnimationFrame(marqueeStep);
            root.classList.add('is-school-marquee-active');
        }

        function markInteraction() {
            hideScrollHint();
            stopMarquee();
            root.classList.remove('is-school-marquee-active');
            if (idleTimer) clearTimeout(idleTimer);
            idleTimer = setTimeout(startMarquee, marqueeDelay);
        }

        function galleryLabel(image, index) {
            var role = (image && image.role ? image.role : '').toLowerCase();
            var labels = { back: 'BACK VIEW', letter: 'LETTER DETAIL', sleeve: 'SLEEVE DETAIL', detail: 'DETAIL VIEW' };
            if (labels[role]) return labels[role];
            var alt = (image && image.alt ? image.alt : '').toLowerCase();
            if (alt.indexOf('back') !== -1) return 'BACK VIEW';
            if (alt.indexOf('letter') !== -1) return 'LETTER DETAIL';
            if (alt.indexOf('sleeve') !== -1) return 'SLEEVE DETAIL';
            if (alt.indexOf('detail') !== -1) return 'DETAIL VIEW';
            return 'DETAIL ' + (index + 1);
        }

        function allStyleImages(style) {
            var images = [];
            if (style.imageFull || style.image) {
                images.push({ full: style.imageFull || style.image, thumb: style.image || style.imageFull, alt: style.name, role: 'front' });
            }
            (style.gallery || []).forEach(function (image) { images.push(image); });
            return images;
        }

        function renderStylePicker(school) {
            if (!stylePicker) return;
            stylePicker.innerHTML = '';
            (school.styles || []).forEach(function (style, index) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'asevj-style-choice' + (index === currentStyleIndex ? ' is-active' : '');
                btn.dataset.asevjStyleChoice = index;
                btn.appendChild(text('span', 'STYLE ' + style.number));
                btn.appendChild(text('strong', style.name));
                if (style.subtitle) btn.appendChild(text('small', style.subtitle));
                btn.addEventListener('click', function () {
                    markInteraction();
                    selectStyle(index);
                });
                stylePicker.appendChild(btn);
            });
        }

        function renderGallery(style) {
            if (!galleryStage) return;
            galleryStage.innerHTML = '';
            var images = allStyleImages(style);
            if (!images.length) {
                var empty = text('div', 'No jacket photography added yet.', 'asevj-selected-gallery is-empty');
                galleryStage.appendChild(empty);
                return;
            }

            var visible = images.slice(0, 5);
            var extra = Math.max(0, images.length - visible.length);
            var grid = document.createElement('div');
            grid.className = 'asevj-selected-gallery' + (visible.length <= 2 ? ' has-few-images' : '');
            visible.forEach(function (image, index) {
                var tile = document.createElement('button');
                tile.type = 'button';
                tile.className = 'asevj-gallery-tile' + (index === 0 ? ' is-main' : '');
                tile.dataset.asevjGalleryImage = index;
                var label = index === 0 ? 'FEATURED JACKET' : galleryLabel(image, index - 1);
                tile.setAttribute('aria-label', 'Open ' + label);
                var img = document.createElement('img');
                img.src = image.thumb || image.full;
                img.alt = image.alt || style.name;
                tile.appendChild(img);
                tile.appendChild(text('span', label));
                if (extra && index === visible.length - 1) tile.appendChild(text('b', '+' + extra + ' MORE'));
                tile.addEventListener('click', function () {
                    var school = schools[currentIndex];
                    if (school) createModal(root, school, style, index);
                });
                grid.appendChild(tile);
            });
            galleryStage.appendChild(grid);
        }

        function selectStyle(index) {
            var school = schools[currentIndex];
            if (!school || !school.styles || !school.styles.length) return;
            currentStyleIndex = Math.max(0, Math.min(index, school.styles.length - 1));
            var style = school.styles[currentStyleIndex];

            if (stylePicker) {
                Array.prototype.slice.call(stylePicker.querySelectorAll('.asevj-style-choice')).forEach(function (choice, choiceIndex) {
                    choice.classList.toggle('is-active', choiceIndex === currentStyleIndex);
                });
            }

            var number = root.querySelector('[data-asevj-selected-number]');
            var name = root.querySelector('[data-asevj-selected-name]');
            var subtitle = root.querySelector('[data-asevj-selected-subtitle]');
            var features = root.querySelector('[data-asevj-selected-features]');
            var price = root.querySelector('[data-asevj-selected-price]');
            if (number) number.textContent = 'STYLE ' + style.number;
            if (name) name.textContent = style.name;
            if (subtitle) {
                subtitle.textContent = style.subtitle || '';
                subtitle.hidden = !style.subtitle;
            }
            if (features) {
                features.innerHTML = '';
                (style.features || []).slice(0, 4).forEach(function (feature) { features.appendChild(text('span', '✓ ' + feature)); });
                features.hidden = !(style.features && style.features.length);
            }
            if (price) {
                price.innerHTML = '';
                if (data.showPrices && style.priceHtml) {
                    price.hidden = false;
                    price.appendChild(text('small', 'STARTING AT'));
                    var strong = document.createElement('strong');
                    strong.innerHTML = style.priceHtml;
                    price.appendChild(strong);
                } else {
                    price.hidden = true;
                }
            }

            if (customizeButton) {
                if (style.url) {
                    customizeButton.href = style.url;
                    customizeButton.hidden = false;
                } else {
                    customizeButton.href = '#';
                    customizeButton.hidden = true;
                }
            }

            renderGallery(style);
        }

        function render(index) {
            currentIndex = index;
            currentStyleIndex = 0;
            var school = schools[index];
            if (!school) return;

            tiles.forEach(function (tile) {
                tile.classList.toggle('is-active', Number(tile.dataset.schoolIndex) === index);
            });

            var summary = root.querySelector('.asevj-school-summary');
            if (summary) {
                summary.style.setProperty('--school-primary', school.primary || '#F2B619');
                summary.style.setProperty('--school-secondary', school.secondary || '#101B31');
            }

            var activeLogo = root.querySelector('[data-asevj-active-logo]');
            if (activeLogo) {
                activeLogo.innerHTML = '';
                activeLogo.appendChild(makeLogo(school));
            }
            var name = root.querySelector('[data-asevj-school-name]');
            var mascotText = root.querySelector('[data-asevj-mascot-text]');
            var location = root.querySelector('[data-asevj-location]');
            if (name) name.textContent = school.name;
            if (mascotText) mascotText.textContent = school.mascot || 'School';
            if (location) location.textContent = school.location || '';

            renderStylePicker(school);
            selectStyle(0);
        }

        function applyFilters() {
            var term = search ? search.value.trim().toLowerCase() : '';
            var districtValue = district ? district.value : '';
            var mascotValue = mascot ? mascot.value : '';
            var visible = [];

            tiles.forEach(function (tile) {
                var match = (!term || (tile.dataset.name || '').indexOf(term) !== -1) &&
                    (!districtValue || tile.dataset.district === districtValue) &&
                    (!mascotValue || tile.dataset.mascot === mascotValue);
                tile.hidden = !match;
                if (match) visible.push(Number(tile.dataset.schoolIndex));
            });

            if (noResults) noResults.hidden = visible.length > 0;
            if (visible.length && visible.indexOf(currentIndex) === -1) render(visible[0]);
        }

        tiles.forEach(function (tile) {
            tile.addEventListener('click', function () { markInteraction(); render(Number(tile.dataset.schoolIndex)); });
        });
        if (search) search.addEventListener('input', function () { markInteraction(); applyFilters(); });
        if (district) district.addEventListener('change', function () { markInteraction(); applyFilters(); });
        if (mascot) mascot.addEventListener('change', function () { markInteraction(); applyFilters(); });
        if (reset) reset.addEventListener('click', function () {
            markInteraction();
            if (search) search.value = '';
            if (district) district.value = '';
            if (mascot) mascot.value = '';
            applyFilters();
        });

        var prev = root.querySelector('[data-asevj-prev]');
        var next = root.querySelector('[data-asevj-next]');
        if (prev && strip) prev.addEventListener('click', function () { markInteraction(); strip.scrollBy({ left: -360, behavior: 'smooth' }); });
        if (next && strip) next.addEventListener('click', function () { markInteraction(); strip.scrollBy({ left: 360, behavior: 'smooth' }); });
        if (strip) {
            ['pointerdown', 'touchstart', 'wheel', 'keydown', 'focusin'].forEach(function (eventName) {
                strip.addEventListener(eventName, markInteraction, { passive: eventName === 'touchstart' || eventName === 'wheel' });
            });
            strip.addEventListener('mouseenter', function () {
                stopMarquee();
                root.classList.remove('is-school-marquee-active');
                if (idleTimer) clearTimeout(idleTimer);
            });
            strip.addEventListener('mouseleave', markInteraction);
        }
        function openCurrentStyle() {
            var school = schools[currentIndex];
            if (!school || !school.styles || !school.styles.length) return;
            var style = school.styles[currentStyleIndex] || school.styles[0];
            createModal(root, school, style);
        }
        if (fullGalleryButton) fullGalleryButton.addEventListener('click', openCurrentStyle);
        if (detailButton) detailButton.addEventListener('click', openCurrentStyle);


        render(currentIndex);
        if (strip) {
            requestAnimationFrame(function () {
                var active = tiles.find(function (tile) { return Number(tile.dataset.schoolIndex) === currentIndex; });
                if (active) {
                    var target = active.offsetLeft - Math.max(0, (strip.clientWidth - active.offsetWidth) / 2);
                    strip.scrollLeft = Math.max(0, target);
                }
            });
        }
        showScrollHint();
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(startMarquee, marqueeDelay);
    }

    function init() {
        document.querySelectorAll('.asevj-browser').forEach(initBrowser);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
