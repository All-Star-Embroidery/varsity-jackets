(function (blocks, element, components, blockEditor, i18n, serverSideRender) {
    'use strict';

    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var ColorPalette = blockEditor.ColorPalette;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var ToggleControl = components.ToggleControl;
    var Notice = components.Notice;
    var __ = i18n.__;
    var ServerSideRender = serverSideRender;

    function update(props, key, value) {
        var next = {};
        next[key] = value;
        props.setAttributes(next);
    }

    function inspector(props) {
        var attrs = props.attributes || {};
        var content = [];
        var layout = [];

        if (Object.prototype.hasOwnProperty.call(attrs, 'title')) {
            content.push(el(TextControl, { key: 'title', label: __('Hero title', 'all-star-varsity-jackets'), value: attrs.title || '', placeholder: __('Use global Design Settings', 'all-star-varsity-jackets'), onChange: function (value) { update(props, 'title', value); } }));
            content.push(el(TextControl, { key: 'kicker', label: __('Hero kicker', 'all-star-varsity-jackets'), value: attrs.kicker || '', placeholder: __('Use global Design Settings', 'all-star-varsity-jackets'), onChange: function (value) { update(props, 'kicker', value); } }));
            content.push(el(TextareaControl, { key: 'body', label: __('Hero description', 'all-star-varsity-jackets'), value: attrs.body || '', placeholder: __('Use global Design Settings', 'all-star-varsity-jackets'), onChange: function (value) { update(props, 'body', value); } }));
        }

        if (Object.prototype.hasOwnProperty.call(attrs, 'heading')) {
            content.push(el(TextControl, { key: 'heading', label: __('Browser heading', 'all-star-varsity-jackets'), value: attrs.heading || '', onChange: function (value) { update(props, 'heading', value); } }));
            content.push(el(TextControl, { key: 'subheading', label: __('Browser subheading', 'all-star-varsity-jackets'), value: attrs.subheading || '', onChange: function (value) { update(props, 'subheading', value); } }));
        }

        if (Object.prototype.hasOwnProperty.call(attrs, 'showFeatures')) {
            layout.push(el(ToggleControl, { key: 'showFeatures', label: __('Show hero feature icons', 'all-star-varsity-jackets'), checked: attrs.showFeatures !== false, onChange: function (value) { update(props, 'showFeatures', value); } }));
            layout.push(el(RangeControl, { key: 'heroHeight', label: __('Hero height override', 'all-star-varsity-jackets'), help: attrs.heroHeight ? __('Overrides the global height for this block.', 'all-star-varsity-jackets') : __('0 uses the global Design Setting.', 'all-star-varsity-jackets'), value: attrs.heroHeight || 0, min: 0, max: 720, step: 10, onChange: function (value) { update(props, 'heroHeight', value || 0); } }));
            layout.push(el(RangeControl, { key: 'titleSize', label: __('Hero title size', 'all-star-varsity-jackets'), help: __('0 keeps the responsive default.', 'all-star-varsity-jackets'), value: attrs.titleSize || 0, min: 0, max: 110, step: 2, onChange: function (value) { update(props, 'titleSize', value || 0); } }));
            layout.push(el(RangeControl, { key: 'kickerSize', label: __('Kicker size', 'all-star-varsity-jackets'), help: __('0 keeps the responsive default.', 'all-star-varsity-jackets'), value: attrs.kickerSize || 0, min: 0, max: 32, step: 1, onChange: function (value) { update(props, 'kickerSize', value || 0); } }));
            layout.push(el(RangeControl, { key: 'bodySize', label: __('Description size', 'all-star-varsity-jackets'), help: __('0 keeps the default.', 'all-star-varsity-jackets'), value: attrs.bodySize || 0, min: 0, max: 28, step: 1, onChange: function (value) { update(props, 'bodySize', value || 0); } }));
            layout.push(el(RangeControl, { key: 'contentPaddingX', label: __('Content horizontal padding', 'all-star-varsity-jackets'), help: __('0 keeps the responsive default.', 'all-star-varsity-jackets'), value: attrs.contentPaddingX || 0, min: 0, max: 160, step: 4, onChange: function (value) { update(props, 'contentPaddingX', value || 0); } }));
            layout.push(el(RangeControl, { key: 'contentPaddingY', label: __('Content vertical padding', 'all-star-varsity-jackets'), help: __('0 keeps the default.', 'all-star-varsity-jackets'), value: attrs.contentPaddingY || 0, min: 0, max: 140, step: 4, onChange: function (value) { update(props, 'contentPaddingY', value || 0); } }));
            layout.push(el(RangeControl, { key: 'featureScale', label: __('Three feature cards scale', 'all-star-varsity-jackets'), help: __('Scales the Premium Quality / Craftsmanship / Perfect Fit row together.', 'all-star-varsity-jackets'), value: attrs.featureScale || 100, min: 60, max: 180, step: 5, onChange: function (value) { update(props, 'featureScale', value || 100); } }));
            layout.push(el(RangeControl, { key: 'jacketScale', label: __('Jacket image scale', 'all-star-varsity-jackets'), help: __('Zoom the signature jacket in or out without editing the image.', 'all-star-varsity-jackets'), value: attrs.jacketScale || 100, min: 70, max: 145, step: 5, onChange: function (value) { update(props, 'jacketScale', value || 100); } }));
            layout.push(el('hr', { key: 'transformDivider', className: 'asevj-editor-divider' }));
            layout.push(el('strong', { key: 'transformTitle', className: 'asevj-editor-section-title' }, __('Hero element positions', 'all-star-varsity-jackets')));
            layout.push(el(RangeControl, { key: 'contentOffsetX', label: __('Text group — left / right', 'all-star-varsity-jackets'), value: attrs.contentOffsetX || 0, min: -320, max: 320, step: 5, onChange: function (value) { update(props, 'contentOffsetX', value || 0); } }));
            layout.push(el(RangeControl, { key: 'contentOffsetY', label: __('Text group — up / down', 'all-star-varsity-jackets'), value: attrs.contentOffsetY || 0, min: -220, max: 220, step: 5, onChange: function (value) { update(props, 'contentOffsetY', value || 0); } }));
            layout.push(el(RangeControl, { key: 'featureOffsetX', label: __('Feature row — left / right', 'all-star-varsity-jackets'), value: attrs.featureOffsetX || 0, min: -220, max: 220, step: 5, onChange: function (value) { update(props, 'featureOffsetX', value || 0); } }));
            layout.push(el(RangeControl, { key: 'featureOffsetY', label: __('Feature row — up / down', 'all-star-varsity-jackets'), value: attrs.featureOffsetY || 0, min: -180, max: 180, step: 5, onChange: function (value) { update(props, 'featureOffsetY', value || 0); } }));
            layout.push(el(RangeControl, { key: 'jacketOffsetX', label: __('Jacket — left / right', 'all-star-varsity-jackets'), value: attrs.jacketOffsetX || 0, min: -360, max: 360, step: 5, onChange: function (value) { update(props, 'jacketOffsetX', value || 0); } }));
            layout.push(el(RangeControl, { key: 'jacketOffsetY', label: __('Jacket — up / down', 'all-star-varsity-jackets'), value: attrs.jacketOffsetY || 0, min: -260, max: 260, step: 5, onChange: function (value) { update(props, 'jacketOffsetY', value || 0); } }));

            if (Object.prototype.hasOwnProperty.call(attrs, 'mobileTitleSize')) {
                layout.push(el('hr', { key: 'mobileHeroDivider', className: 'asevj-editor-divider' }));
                layout.push(el('strong', { key: 'mobileHeroTitle', className: 'asevj-editor-section-title' }, __('Mobile hero layout', 'all-star-varsity-jackets')));
                layout.push(el('p', { key: 'mobileHeroHelp', className: 'asevj-editor-help' }, __('These settings only affect screens 760px wide and smaller. 0 keeps the polished mobile default.', 'all-star-varsity-jackets')));
                layout.push(el(ToggleControl, {
                    key: 'mobileUseMainControls',
                    label: __('Use main Hero controls on mobile', 'all-star-varsity-jackets'),
                    help: attrs.mobileUseMainControls
                        ? __('ON: the normal Hero title, padding, scale, and position controls above drive this block on mobile. Ideal for a separate mobile-only copy.', 'all-star-varsity-jackets')
                        : __('OFF: mobile keeps its own independent controls below so one Hero block can stay responsive across devices.', 'all-star-varsity-jackets'),
                    checked: !!attrs.mobileUseMainControls,
                    onChange: function (value) { update(props, 'mobileUseMainControls', !!value); }
                }));
                layout.push(el(RangeControl, { key: 'mobileTitleSize', label: __('Mobile title size', 'all-star-varsity-jackets'), value: attrs.mobileTitleSize || 0, min: 0, max: 64, step: 2, onChange: function (value) { update(props, 'mobileTitleSize', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileContentPaddingX', label: __('Mobile horizontal padding', 'all-star-varsity-jackets'), value: attrs.mobileContentPaddingX || 0, min: 0, max: 72, step: 2, onChange: function (value) { update(props, 'mobileContentPaddingX', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileContentPaddingY', label: __('Mobile vertical padding', 'all-star-varsity-jackets'), value: attrs.mobileContentPaddingY || 0, min: 0, max: 100, step: 2, onChange: function (value) { update(props, 'mobileContentPaddingY', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileVisualHeight', label: __('Mobile jacket area height', 'all-star-varsity-jackets'), value: attrs.mobileVisualHeight || 0, min: 0, max: 520, step: 10, onChange: function (value) { update(props, 'mobileVisualHeight', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileFeatureScale', label: __('Mobile feature scale', 'all-star-varsity-jackets'), value: attrs.mobileFeatureScale || 0, min: 0, max: 130, step: 5, onChange: function (value) { update(props, 'mobileFeatureScale', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileJacketScale', label: __('Mobile jacket scale', 'all-star-varsity-jackets'), value: attrs.mobileJacketScale || 0, min: 0, max: 135, step: 5, onChange: function (value) { update(props, 'mobileJacketScale', value || 0); } }));
                layout.push(el('strong', { key: 'mobileTransformTitle', className: 'asevj-editor-section-title asevj-editor-section-title--sub' }, __('Mobile positions', 'all-star-varsity-jackets')));
                layout.push(el(RangeControl, { key: 'mobileContentOffsetX', label: __('Mobile text — left / right', 'all-star-varsity-jackets'), value: attrs.mobileContentOffsetX || 0, min: -120, max: 120, step: 5, onChange: function (value) { update(props, 'mobileContentOffsetX', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileContentOffsetY', label: __('Mobile text — up / down', 'all-star-varsity-jackets'), value: attrs.mobileContentOffsetY || 0, min: -120, max: 120, step: 5, onChange: function (value) { update(props, 'mobileContentOffsetY', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileFeatureOffsetX', label: __('Mobile features — left / right', 'all-star-varsity-jackets'), value: attrs.mobileFeatureOffsetX || 0, min: -100, max: 100, step: 5, onChange: function (value) { update(props, 'mobileFeatureOffsetX', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileFeatureOffsetY', label: __('Mobile features — up / down', 'all-star-varsity-jackets'), value: attrs.mobileFeatureOffsetY || 0, min: -100, max: 100, step: 5, onChange: function (value) { update(props, 'mobileFeatureOffsetY', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileJacketOffsetX', label: __('Mobile jacket — left / right', 'all-star-varsity-jackets'), value: attrs.mobileJacketOffsetX || 0, min: -140, max: 140, step: 5, onChange: function (value) { update(props, 'mobileJacketOffsetX', value || 0); } }));
                layout.push(el(RangeControl, { key: 'mobileJacketOffsetY', label: __('Mobile jacket — up / down', 'all-star-varsity-jackets'), value: attrs.mobileJacketOffsetY || 0, min: -140, max: 140, step: 5, onChange: function (value) { update(props, 'mobileJacketOffsetY', value || 0); } }));
            }
        }

        function triControl(key, label) {
            if (!Object.prototype.hasOwnProperty.call(attrs, key)) return;
            layout.push(el(SelectControl, {
                key: key,
                label: label,
                value: attrs[key] || 'global',
                options: [
                    { label: __('Use global setting', 'all-star-varsity-jackets'), value: 'global' },
                    { label: __('Show', 'all-star-varsity-jackets'), value: 'show' },
                    { label: __('Hide', 'all-star-varsity-jackets'), value: 'hide' }
                ],
                onChange: function (value) { update(props, key, value); }
            }));
        }
        triControl('showSearch', __('School search', 'all-star-varsity-jackets'));
        triControl('showFilters', __('District / mascot filters', 'all-star-varsity-jackets'));
        triControl('showPrices', __('Prices', 'all-star-varsity-jackets'));

        if (Object.prototype.hasOwnProperty.call(attrs, 'stylesVisible')) {
            layout.push(el(RangeControl, { key: 'stylesVisible', label: __('Style cards per row', 'all-star-varsity-jackets'), help: attrs.stylesVisible ? __('This block overrides the global setting.', 'all-star-varsity-jackets') : __('0 uses the global Design Setting.', 'all-star-varsity-jackets'), value: attrs.stylesVisible || 0, min: 0, max: 4, step: 1, onChange: function (value) { update(props, 'stylesVisible', value || 0); } }));
        }

        var appearance = [];
        if (Object.prototype.hasOwnProperty.call(attrs, 'heroBackgroundColor')) {
            appearance.push(el(TextControl, { key: 'heroBackgroundColor', label: __('Hero background color', 'all-star-varsity-jackets'), value: attrs.heroBackgroundColor || '', placeholder: '#101B31', onChange: function (value) { update(props, 'heroBackgroundColor', value); } }));
            appearance.push(el(TextControl, { key: 'browserBackgroundColor', label: __('Browse section background', 'all-star-varsity-jackets'), value: attrs.browserBackgroundColor || '', placeholder: '#FFFFFF', onChange: function (value) { update(props, 'browserBackgroundColor', value); } }));
            appearance.push(el(TextControl, { key: 'benefitsBackgroundColor', label: __('Benefits background', 'all-star-varsity-jackets'), value: attrs.benefitsBackgroundColor || '', placeholder: '#101B31', onChange: function (value) { update(props, 'benefitsBackgroundColor', value); } }));
        }
        if (Object.prototype.hasOwnProperty.call(attrs, 'backgroundColor') && !Object.prototype.hasOwnProperty.call(attrs, 'heroBackgroundColor')) {
            appearance.push(el('div', { key: 'backgroundColor', className: 'asevj-editor-color-control' },
                el('strong', {}, __('Block background', 'all-star-varsity-jackets')),
                el(ColorPalette, {
                    value: attrs.backgroundColor || undefined,
                    clearable: true,
                    colors: [
                        { name: __('All Star Navy', 'all-star-varsity-jackets'), color: '#101B31' },
                        { name: __('White', 'all-star-varsity-jackets'), color: '#FFFFFF' },
                        { name: __('Warm Cream', 'all-star-varsity-jackets'), color: '#F6F3EA' },
                        { name: __('Light Gray', 'all-star-varsity-jackets'), color: '#F7F8FA' },
                        { name: __('Gold', 'all-star-varsity-jackets'), color: '#D6A83A' }
                    ],
                    onChange: function (value) { update(props, 'backgroundColor', value || ''); }
                }),
                el('small', {}, __('Leave empty to use the plugin default.', 'all-star-varsity-jackets'))
            ));
        }
        if (Object.prototype.hasOwnProperty.call(attrs, 'fullBleed')) {
            appearance.push(el(ToggleControl, { key: 'fullBleed', label: __('Full-width background', 'all-star-varsity-jackets'), help: __('Removes theme content-width whitespace and lets this section reach the viewport edges.', 'all-star-varsity-jackets'), checked: attrs.fullBleed !== false, onChange: function (value) { update(props, 'fullBleed', value); } }));
        }
        if (Object.prototype.hasOwnProperty.call(attrs, 'topFadeColor')) {
            appearance.push(el('div', { key: 'topFadeColor', className: 'asevj-editor-color-control' },
                el('strong', {}, __('Hero top fade / header blend', 'all-star-varsity-jackets')),
                el(ColorPalette, {
                    value: attrs.topFadeColor || '#101B31',
                    clearable: false,
                    colors: [
                        { name: __('All Star Navy', 'all-star-varsity-jackets'), color: '#101B31' },
                        { name: __('Deep Navy', 'all-star-varsity-jackets'), color: '#0B162A' },
                        { name: __('Black Navy', 'all-star-varsity-jackets'), color: '#07111F' }
                    ],
                    onChange: function (value) { update(props, 'topFadeColor', value || '#101B31'); }
                })
            ));
            appearance.push(el(RangeControl, { key: 'topFadeHeight', label: __('Fade depth', 'all-star-varsity-jackets'), value: attrs.topFadeHeight || 110, min: 0, max: 260, step: 10, onChange: function (value) { update(props, 'topFadeHeight', value || 0); } }));
        }

        if (Object.prototype.hasOwnProperty.call(attrs, 'visualBackgroundColor')) {
            appearance.push(el('div', { key: 'visualBackgroundColor', className: 'asevj-editor-color-control' },
                el('strong', {}, __('Area behind jacket', 'all-star-varsity-jackets')),
                el(ColorPalette, { value: attrs.visualBackgroundColor || '#07111F', clearable: false, colors: [
                    { name: __('Black Navy', 'all-star-varsity-jackets'), color: '#07111F' },
                    { name: __('Deep Navy', 'all-star-varsity-jackets'), color: '#0B162A' },
                    { name: __('All Star Navy', 'all-star-varsity-jackets'), color: '#101B31' },
                    { name: __('Slate Navy', 'all-star-varsity-jackets'), color: '#17243A' }
                ], onChange: function (value) { update(props, 'visualBackgroundColor', value || '#07111F'); } })
            ));
            appearance.push(el('div', { key: 'jacketOverlayColor', className: 'asevj-editor-color-control' },
                el('strong', {}, __('Jacket image color blend', 'all-star-varsity-jackets')),
                el(ColorPalette, { value: attrs.jacketOverlayColor || '#0B162A', clearable: false, colors: [
                    { name: __('Deep Navy', 'all-star-varsity-jackets'), color: '#0B162A' },
                    { name: __('All Star Navy', 'all-star-varsity-jackets'), color: '#101B31' },
                    { name: __('Black Navy', 'all-star-varsity-jackets'), color: '#07111F' }
                ], onChange: function (value) { update(props, 'jacketOverlayColor', value || '#0B162A'); } })
            ));
            appearance.push(el(RangeControl, { key: 'jacketOverlayOpacity', label: __('Color blend strength', 'all-star-varsity-jackets'), help: __('Use this to pull a baked-in photo background toward the hero navy.', 'all-star-varsity-jackets'), value: attrs.jacketOverlayOpacity == null ? 18 : attrs.jacketOverlayOpacity, min: 0, max: 80, step: 2, onChange: function (value) { update(props, 'jacketOverlayOpacity', value || 0); } }));
            appearance.push(el('div', { key: 'glowColor', className: 'asevj-editor-color-control' },
                el('strong', {}, __('Glow behind jacket', 'all-star-varsity-jackets')),
                el(ColorPalette, { value: attrs.glowColor || '#27456F', clearable: false, colors: [
                    { name: __('Soft Blue', 'all-star-varsity-jackets'), color: '#27456F' },
                    { name: __('All Star Navy', 'all-star-varsity-jackets'), color: '#101B31' },
                    { name: __('Cool Slate', 'all-star-varsity-jackets'), color: '#3B526F' },
                    { name: __('Gold', 'all-star-varsity-jackets'), color: '#D6A83A' }
                ], onChange: function (value) { update(props, 'glowColor', value || '#27456F'); } })
            ));
            appearance.push(el(RangeControl, { key: 'glowOpacity', label: __('Glow intensity', 'all-star-varsity-jackets'), value: attrs.glowOpacity == null ? 32 : attrs.glowOpacity, min: 0, max: 85, step: 5, onChange: function (value) { update(props, 'glowOpacity', value || 0); } }));
            appearance.push(el(RangeControl, { key: 'glowSize', label: __('Glow spread', 'all-star-varsity-jackets'), value: attrs.glowSize || 68, min: 20, max: 100, step: 5, onChange: function (value) { update(props, 'glowSize', value || 68); } }));
            appearance.push(el(RangeControl, { key: 'glowOffsetX', label: __('Glow horizontal position', 'all-star-varsity-jackets'), value: attrs.glowOffsetX || 0, min: -40, max: 40, step: 2, onChange: function (value) { update(props, 'glowOffsetX', value || 0); } }));
            appearance.push(el(RangeControl, { key: 'glowOffsetY', label: __('Glow vertical position', 'all-star-varsity-jackets'), value: attrs.glowOffsetY || 0, min: -40, max: 40, step: 2, onChange: function (value) { update(props, 'glowOffsetY', value || 0); } }));
        }

        var panels = [];
        if (content.length) panels.push(el(PanelBody, { key: 'content', title: __('Content', 'all-star-varsity-jackets'), initialOpen: true }, content));
        if (layout.length) panels.push(el(PanelBody, { key: 'layout', title: __('Layout overrides', 'all-star-varsity-jackets'), initialOpen: false }, layout));
        if (appearance.length) panels.push(el(PanelBody, { key: 'appearance', title: __('Block appearance', 'all-star-varsity-jackets'), initialOpen: true }, appearance));
        if (!panels.length) return null;
        return el(InspectorControls, {}, panels);
    }

    function livePreview(props) {
        return el('div', { className: 'asevj-editor-live' },
            inspector(props),
            el('div', { className: 'asevj-editor-live__bar' },
                el('strong', {}, __('Live frontend preview', 'all-star-varsity-jackets')),
                el('span', {}, __('This is the actual rendered block. Use the sidebar only when you want to override the global design.', 'all-star-varsity-jackets'))
            ),
            ServerSideRender ? el(ServerSideRender, { block: props.name, attributes: props.attributes, httpMethod: 'POST' }) : el(Notice, { status: 'warning', isDismissible: false }, __('Live preview could not load. Preview the page in a new tab.', 'all-star-varsity-jackets'))
        );
    }

    var commonBrowser = {
        heading: { type: 'string', default: 'BROWSE BY SCHOOL' },
        subheading: { type: 'string', default: 'Select your school to view available jacket styles and custom options.' },
        showSearch: { type: 'string', default: 'global' },
        showFilters: { type: 'string', default: 'global' },
        showPrices: { type: 'string', default: 'global' },
        stylesVisible: { type: 'number', default: 0 },
        backgroundColor: { type: 'string', default: '' },
        fullBleed: { type: 'boolean', default: true }
    };
    var commonHero = {
        title: { type: 'string', default: '' },
        kicker: { type: 'string', default: '' },
        body: { type: 'string', default: '' },
        showFeatures: { type: 'boolean', default: true },
        heroHeight: { type: 'number', default: 0 },
        titleSize: { type: 'number', default: 0 },
        kickerSize: { type: 'number', default: 0 },
        bodySize: { type: 'number', default: 0 },
        contentPaddingX: { type: 'number', default: 0 },
        contentPaddingY: { type: 'number', default: 0 },
        featureScale: { type: 'number', default: 100 },
        jacketScale: { type: 'number', default: 100 },
        visualBackgroundColor: { type: 'string', default: '#07111F' },
        jacketOverlayColor: { type: 'string', default: '#0B162A' },
        jacketOverlayOpacity: { type: 'number', default: 18 },
        glowColor: { type: 'string', default: '#27456F' },
        glowOpacity: { type: 'number', default: 32 },
        glowSize: { type: 'number', default: 68 },
        contentOffsetX: { type: 'number', default: 0 },
        contentOffsetY: { type: 'number', default: 0 },
        featureOffsetX: { type: 'number', default: 0 },
        featureOffsetY: { type: 'number', default: 0 },
        jacketOffsetX: { type: 'number', default: 0 },
        jacketOffsetY: { type: 'number', default: 0 },
        glowOffsetX: { type: 'number', default: 0 },
        glowOffsetY: { type: 'number', default: 0 },
        mobileUseMainControls: { type: 'boolean', default: false },
        mobileTitleSize: { type: 'number', default: 0 },
        mobileContentPaddingX: { type: 'number', default: 0 },
        mobileContentPaddingY: { type: 'number', default: 0 },
        mobileFeatureScale: { type: 'number', default: 0 },
        mobileJacketScale: { type: 'number', default: 0 },
        mobileVisualHeight: { type: 'number', default: 0 },
        mobileContentOffsetX: { type: 'number', default: 0 },
        mobileContentOffsetY: { type: 'number', default: 0 },
        mobileFeatureOffsetX: { type: 'number', default: 0 },
        mobileFeatureOffsetY: { type: 'number', default: 0 },
        mobileJacketOffsetX: { type: 'number', default: 0 },
        mobileJacketOffsetY: { type: 'number', default: 0 },
        backgroundColor: { type: 'string', default: '' },
        fullBleed: { type: 'boolean', default: true },
        topFadeColor: { type: 'string', default: '#101B31' },
        topFadeHeight: { type: 'number', default: 110 }
    };

    var fullAttrs = {};
    Object.keys(commonHero).forEach(function (k) { fullAttrs[k] = commonHero[k]; });
    Object.keys(commonBrowser).forEach(function (k) { fullAttrs[k] = commonBrowser[k]; });
    fullAttrs.heroBackgroundColor = { type: 'string', default: '' };
    fullAttrs.browserBackgroundColor = { type: 'string', default: '' };
    fullAttrs.benefitsBackgroundColor = { type: 'string', default: '' };

    var configs = {
        'asevj/hero': { title: 'Varsity Hero', icon: 'cover-image', description: 'All Star Varsity Jackets hero.', attributes: commonHero },
        'asevj/browser': { title: 'Browse Varsity Jackets', icon: 'grid-view', description: 'School selector with multiple jacket styles.', attributes: commonBrowser },
        'asevj/benefits': { title: 'Varsity Benefits Strip', icon: 'awards', description: 'Craftsmanship, fit, materials, and durability benefits.', attributes: { backgroundColor: { type: 'string', default: '' }, fullBleed: { type: 'boolean', default: true } } },
        'asevj/full-experience': { title: 'Full Varsity Jackets Experience', icon: 'star-filled', description: 'Hero + school browser + benefits in one ready-to-use block.', attributes: fullAttrs }
    };

    Object.keys(configs).forEach(function (name) {
        if (blocks.getBlockType(name)) blocks.unregisterBlockType(name);
        var config = configs[name];
        blocks.registerBlockType(name, {
            apiVersion: 3,
            title: config.title,
            category: 'all-star-embroidery',
            icon: config.icon,
            description: config.description,
            attributes: config.attributes,
            supports: { html: false, align: ['wide', 'full'] },
            edit: livePreview,
            save: function () { return null; }
        });
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n, window.wp.serverSideRender);
