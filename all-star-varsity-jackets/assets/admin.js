(function ($) {
    'use strict';

    function initColors() {
        $('.asevj-color').wpColorPicker();
    }

    function openSingleMedia($field) {
        var frame = wp.media({
            title: 'Choose image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $field.find('.asevj-media-id').val(attachment.id);
            var src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            $field.find('.asevj-media-preview').html($('<img>', { src: src, alt: '' }));
        });

        frame.open();
    }

    function initMedia() {
        $(document).on('click', '.asevj-pick-media', function (e) {
            e.preventDefault();
            openSingleMedia($(this).closest('.asevj-media-field'));
        });

        $(document).on('click', '.asevj-remove-media', function (e) {
            e.preventDefault();
            var $field = $(this).closest('.asevj-media-field');
            $field.find('.asevj-media-id').val('');
            $field.find('.asevj-media-preview').html('<span>No image selected</span>');
        });
    }

    function renderGallery($box, selection) {
        var ids = [];
        var $preview = $box.find('.asevj-gallery-preview').empty();
        selection.each(function (attachment) {
            var data = attachment.toJSON();
            ids.push(data.id);
            var src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
            $('<div class="asevj-gallery-item" data-id="' + data.id + '"><img src="' + src + '" alt=""><button type="button" class="asevj-gallery-remove" aria-label="Remove">×</button></div>').appendTo($preview);
        });
        $box.find('.asevj-gallery-ids').val(ids.join(','));
    }

    function syncGalleryIds($preview) {
        var ids = [];
        $preview.find('.asevj-gallery-item').each(function () { ids.push($(this).data('id')); });
        $preview.closest('.inside').find('.asevj-gallery-ids').val(ids.join(','));
    }

    function initGallery() {
        $('.asevj-gallery-preview').sortable({
            items: '.asevj-gallery-item',
            placeholder: 'asevj-gallery-sort-placeholder',
            update: function () { syncGalleryIds($(this)); }
        });
        $(document).on('click', '.asevj-pick-gallery', function (e) {
            e.preventDefault();
            var $box = $(this).closest('.inside');
            var current = ($box.find('.asevj-gallery-ids').val() || '').split(',').filter(Boolean).map(Number);
            var frame = wp.media({
                title: 'Choose style gallery images',
                button: { text: 'Use selected images' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('open', function () {
                var selection = frame.state().get('selection');
                current.forEach(function (id) {
                    var attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment);
                });
            });

            frame.on('select', function () {
                renderGallery($box, frame.state().get('selection'));
            });

            frame.open();
        });

        $(document).on('click', '.asevj-gallery-remove', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.asevj-gallery-item');
            var $box = $item.closest('.inside');
            $item.remove();
            syncGalleryIds($box.find('.asevj-gallery-preview'));
        });
    }


    function initSchoolSort() {
        $('.asevj-school-sorter').each(function () {
            var $sorter = $(this);
            $sorter.sortable({
                handle: '.dashicons-menu',
                axis: 'y',
                placeholder: 'asevj-sort-placeholder',
                update: function () {
                    var ids = $sorter.find('.asevj-school-order-row').map(function () { return $(this).data('school'); }).get();
                    var $status = $('.asevj-school-sort-status');
                    $status.text('Saving school order…');
                    $.post(ASEVJ_ADMIN.ajaxUrl, {
                        action: 'asevj_reorder_schools',
                        nonce: ASEVJ_ADMIN.nonce,
                        ids: ids
                    }).done(function (response) {
                        $status.text(response && response.success ? 'School order saved.' : 'Could not save school order.');
                    }).fail(function () { $status.text('Could not save school order.'); });
                }
            });
        });
    }

    function initStyleSort() {
        $('.asevj-style-sorter').each(function () {
            var $sorter = $(this);
            var school = $sorter.data('school');
            $sorter.sortable({
                handle: '.asevj-drag',
                axis: 'y',
                placeholder: 'asevj-sort-placeholder',
                update: function () {
                    var ids = $sorter.find('.asevj-style-row').map(function () { return $(this).data('style'); }).get();
                    var $status = $sorter.next('.asevj-sort-status');
                    $status.text('Saving order…');
                    $.post(ASEVJ_ADMIN.ajaxUrl, {
                        action: 'asevj_reorder_styles',
                        nonce: ASEVJ_ADMIN.nonce,
                        school: school,
                        ids: ids
                    }).done(function (response) {
                        $status.text(response && response.success ? 'Style order saved.' : 'Could not save style order.');
                        $sorter.find('.asevj-style-main strong').each(function (index) {
                            var text = $(this).text().replace(/^Style\s+\d+\s+—\s+/, '');
                            $(this).text('Style ' + (index + 1) + ' — ' + text);
                        });
                    }).fail(function () {
                        $status.text('Could not save style order.');
                    });
                }
            });
        });
    }


    function refreshOrganizerLabels() {
        $('.asevj-organizer-column').each(function () {
            $(this).find('.asevj-organizer-image').each(function (index) {
                $(this).find('small').text(index === 0 ? 'MAIN IMAGE' : 'Gallery');
            });
        });
    }

    function initOrganizer() {
        var $organizer = $('.asevj-organizer');
        if (!$organizer.length) return;

        $organizer.find('[data-asevj-sortable-images]').sortable({
            connectWith: '[data-asevj-sortable-images]',
            placeholder: 'asevj-organizer-placeholder',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            update: refreshOrganizerLabels,
            receive: refreshOrganizerLabels
        }).disableSelection();

        $(document).on('click', '[data-asevj-save-organizer]', function (e) {
            e.preventDefault();
            var $button = $(this);
            var layout = {};
            $organizer.find('.asevj-organizer-column[data-style]').each(function () {
                var style = String($(this).data('style'));
                layout[style] = $(this).find('.asevj-organizer-image').map(function () {
                    return Number($(this).data('attachment'));
                }).get();
            });
            var $status = $('.asevj-organizer-status');
            $button.prop('disabled', true).text('Saving…');
            $status.text('Saving style image layout…');
            $.post(ASEVJ_ADMIN.ajaxUrl, {
                action: 'asevj_save_organizer',
                nonce: ASEVJ_ADMIN.nonce,
                school_id: $organizer.data('school'),
                layout: JSON.stringify(layout)
            }).done(function (response) {
                if (response && response.success) {
                    $status.text(response.data && response.data.message ? response.data.message : 'Layout saved.');
                    refreshOrganizerLabels();
                } else {
                    $status.text('Could not save the layout.');
                }
            }).fail(function () {
                $status.text('Could not save the layout.');
            }).always(function () {
                $button.prop('disabled', false).text('Save Layout');
            });
        });
    }

    $(function () {
        initColors();
        initMedia();
        initGallery();
        initStyleSort();
        initSchoolSort();
        initOrganizer();
        if ($('.wc-product-search').length) {
            $(document.body).trigger('wc-enhanced-select-init');
        }
    });
})(jQuery);
