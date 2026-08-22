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

    function createModal(root, school, style) {
        var old = document.querySelector('.asevj-modal');
        if (old) old.remove();

        var modal = document.createElement('div');
        modal.className = 'asevj-modal';
        modal.style.cssText = root.getAttribute('style') || '';
        modal.innerHTML = '<div class="asevj-modal__backdrop" data-close></div><div class="asevj-modal__panel" role="dialog" aria-modal="true"><button class="asevj-modal__close" data-close aria-label="Close">×</button><div class="asevj-modal__content"><div class="asevj-modal__media"><div class="asevj-modal__main"></div><div class="asevj-modal__thumbs"></div></div><div class="asevj-modal__details"></div></div></div>';
        document.body.appendChild(modal);

        var main = modal.querySelector('.asevj-modal__main');
        var thumbs = modal.querySelector('.asevj-modal__thumbs');
        var details = modal.querySelector('.asevj-modal__details');
        var allImages = [];

        if (style.imageFull || style.image) {
            allImages.push({ full: style.imageFull || style.image, thumb: style.image || style.imageFull, alt: style.name });
        }
        (style.gallery || []).forEach(function (image) { allImages.push(image); });

        function showImage(image) {
            main.innerHTML = '';
            if (!image) {
                main.appendChild(text('span', 'No image added yet'));
                return;
            }
            var img = document.createElement('img');
            img.src = image.full || image.thumb;
            img.alt = image.alt || style.name;
            main.appendChild(img);
        }

        if (!allImages.length) showImage(null);
        allImages.forEach(function (image, index) {
            var btn = document.createElement('button');
            var img = document.createElement('img');
            img.src = image.thumb || image.full;
            img.alt = image.alt || '';
            btn.appendChild(img);
            btn.addEventListener('click', function () { showImage(image); });
            thumbs.appendChild(btn);
            if (index === 0) showImage(image);
        });

        details.appendChild(text('div', style.galleryMode ? (school.name + ' • SCHOOL GALLERY') : ('STYLE ' + style.number + ' • ' + school.name), 'asevj-modal__eyebrow'));
        details.appendChild(text('h3', style.name));
        if (style.subtitle) details.appendChild(text('p', style.subtitle));
        if (style.description) details.appendChild(text('p', style.description));

        if (style.features && style.features.length) {
            var ul = document.createElement('ul');
            ul.className = 'asevj-modal__features';
            style.features.forEach(function (feature) { ul.appendChild(text('li', feature)); });
            details.appendChild(ul);
        }

        if (style.priceHtml) {
            var price = document.createElement('div');
            price.className = 'asevj-modal__price';
            price.innerHTML = style.priceHtml;
            details.appendChild(price);
        }

        if (style.url) {
            var link = document.createElement('a');
            link.className = 'asevj-btn asevj-btn-primary';
            link.href = style.url;
            link.textContent = style.cta || 'Customize This Jacket';
            details.appendChild(link);
        }

        function close() {
            modal.classList.remove('is-open');
            document.documentElement.style.overflow = '';
            setTimeout(function () { modal.remove(); }, 160);
        }

        modal.querySelectorAll('[data-close]').forEach(function (el) { el.addEventListener('click', close); });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', esc);
                close();
            }
        });
        requestAnimationFrame(function () {
            modal.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
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
        var grid = root.querySelector('[data-asevj-style-grid]');
        var galleryButton = root.querySelector('[data-asevj-school-gallery]');
        var currentIndex = 0;
        var idleTimer = null;
        var marqueeRaf = null;
        var marqueeDirection = 1;
        var marqueeLastTs = 0;
        var marqueePaused = true;
        var marqueeDelay = 10000;
        var marqueeSpeed = 18;

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
            stopMarquee();
            root.classList.remove('is-school-marquee-active');
            if (idleTimer) clearTimeout(idleTimer);
            idleTimer = setTimeout(startMarquee, marqueeDelay);
        }

        function styleCard(school, style, index) {
            var card = document.createElement('article');
            card.className = 'asevj-style-card';
            card.dataset.styleIndex = index;
            card.appendChild(text('div', 'STYLE ' + style.number, 'asevj-style-card__label'));

            var imageButton = document.createElement('button');
            imageButton.type = 'button';
            imageButton.className = 'asevj-style-card__image';
            imageButton.setAttribute('aria-label', 'View ' + style.name + ' details');
            imageButton.addEventListener('click', function () { createModal(root, school, style); });
            if (style.image) {
                var img = document.createElement('img');
                img.src = style.image;
                img.alt = style.name;
                imageButton.appendChild(img);
            } else {
                imageButton.appendChild(jacketPlaceholder());
            }
            if (style.linkedWoo) imageButton.appendChild(text('span', 'WooCommerce', 'asevj-woo-pill'));
            card.appendChild(imageButton);

            var body = document.createElement('div');
            body.className = 'asevj-style-card__body';

            var titleButton = document.createElement('button');
            titleButton.type = 'button';
            titleButton.className = 'asevj-style-card__title';
            titleButton.textContent = style.name;
            titleButton.addEventListener('click', function () { createModal(root, school, style); });
            body.appendChild(titleButton);

            if (style.subtitle) body.appendChild(text('div', style.subtitle, 'asevj-style-subtitle'));
            body.appendChild(text('p', style.description || 'Premium varsity jacket style with custom decoration options.'));

            if (style.features && style.features.length) {
                var features = document.createElement('div');
                features.className = 'asevj-style-feature-row';
                style.features.slice(0, 3).forEach(function (feature) { features.appendChild(text('span', '✓ ' + feature)); });
                body.appendChild(features);
            }

            if (data.showPrices && style.priceHtml) {
                var price = document.createElement('div');
                price.className = 'asevj-style-price';
                price.appendChild(text('small', 'STARTING AT'));
                var strong = document.createElement('strong');
                strong.innerHTML = style.priceHtml;
                price.appendChild(strong);
                body.appendChild(price);
            }

            if (style.url) {
                var cta = document.createElement('a');
                cta.className = 'asevj-btn asevj-btn-primary';
                cta.href = style.url;
                cta.innerHTML = (style.cta || 'Customize This Jacket') + ' <span>↗</span>';
                body.appendChild(cta);
            } else {
                var ctaButton = document.createElement('button');
                ctaButton.type = 'button';
                ctaButton.className = 'asevj-btn asevj-btn-primary is-showcase';
                ctaButton.innerHTML = (style.cta || 'View Jacket Details') + ' <span>→</span>';
                ctaButton.addEventListener('click', function () { createModal(root, school, style); });
                body.appendChild(ctaButton);
            }

            card.appendChild(body);
            return card;
        }

        function render(index) {
            currentIndex = index;
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
            var description = root.querySelector('[data-asevj-description]');
            if (name) name.textContent = school.name;
            if (mascotText) mascotText.textContent = school.mascot || 'School';
            if (location) location.textContent = school.location || '';
            if (description) description.textContent = school.description || 'Explore the available varsity jacket styles for this school.';

            if (grid) {
                grid.innerHTML = '';
                (school.styles || []).forEach(function (style, styleIndex) {
                    grid.appendChild(styleCard(school, style, styleIndex));
                });
            }
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
        if (galleryButton) galleryButton.addEventListener('click', function () {
            var school = schools[currentIndex];
            if (!school || !school.styles || !school.styles.length) return;
            var images = [];
            school.styles.forEach(function (style) {
                if (style.imageFull || style.image) images.push({ full: style.imageFull || style.image, thumb: style.image || style.imageFull, alt: style.name });
                (style.gallery || []).forEach(function (image) { images.push(image); });
            });
            var first = images.shift() || null;
            createModal(root, school, {
                galleryMode: true,
                number: '',
                name: school.name + ' Jacket Gallery',
                subtitle: school.mascot || '',
                description: school.description || 'Browse varsity jacket examples for this school.',
                image: first ? first.thumb : '',
                imageFull: first ? first.full : '',
                gallery: images,
                features: [],
                priceHtml: '',
                url: '',
                cta: ''
            });
        });

        render(0);
        markInteraction();
    }

    function init() {
        document.querySelectorAll('.asevj-browser').forEach(initBrowser);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
