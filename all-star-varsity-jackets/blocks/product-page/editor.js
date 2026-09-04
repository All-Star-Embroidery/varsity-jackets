(function (wp) {
    'use strict';

    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var ColorPalette = wp.blockEditor.ColorPalette;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var Notice = wp.components.Notice;
    var ServerSideRender = wp.serverSideRender;
    var __ = wp.i18n.__;

    function set(props, key, value) {
        var next = {};
        next[key] = value;
        props.setAttributes(next);
    }

    function colorControl(props, key, label) {
        return el('div', { className: 'asevj-vjpp-editor-color', key: key },
            el('strong', null, label),
            el(ColorPalette, {
                value: props.attributes[key] || '',
                clearable: true,
                enableAlpha: false,
                colors: [
                    { name: 'All Star Navy', color: '#101B31' },
                    { name: 'All Star Gold', color: '#F2B619' },
                    { name: 'Warm Cream', color: '#F6F3EA' },
                    { name: 'White', color: '#FFFFFF' },
                    { name: 'Soft Gray', color: '#F1F2F4' },
                    { name: 'Charcoal', color: '#1F2937' }
                ],
                onChange: function (value) { set(props, key, value || ''); }
            })
        );
    }

    var attributes = {
        previewProductId: { type: 'number', default: 0 },
        eyebrow: { type: 'string', default: 'CUSTOM VARSITY JACKET' },
        styleSwitcherLabel: { type: 'string', default: 'JACKET STYLES' },
        priceLabel: { type: 'string', default: 'STARTING AT' },
        priceNote: { type: 'string', default: 'Base jacket price. Lettering, patches, embroidery, names, numerals, and other customizations are additional.' },
        customizationsHeading: { type: 'string', default: 'Available Customizations' },
        customizationsSubheading: { type: 'string', default: 'Build the jacket around your school, achievements, and style.' },
        customizations: { type: 'string', default: 'Chenille Letters|Classic 3D chenille letters in school colors.\nTackle Twill|One-color or multi-color twill designs.\nEmbroidery|Names, mascots, and custom embroidery.\nPatches|School, achievement, and custom patches.\nNames & Numerals|Graduation year, jersey numbers, and personalization.' },
        orderHeading: { type: 'string', default: 'Ready to order your jacket?' },
        orderText: { type: 'string', default: 'Every varsity jacket is custom-built. Call the store to finalize sizing, lettering, patches, and your complete price.' },
        buttonLabel: { type: 'string', default: 'Call the Store to Order' },
        phoneNumber: { type: 'string', default: '' },
        contactUrl: { type: 'string', default: '' },
        showDescription: { type: 'boolean', default: true },
        showFeatures: { type: 'boolean', default: true },
        showCustomizations: { type: 'boolean', default: true },
        showProcess: { type: 'boolean', default: true },
        showSchoolMeta: { type: 'boolean', default: true },
        showStyleSwitcher: { type: 'boolean', default: true },
        fullBleed: { type: 'boolean', default: true },
        maxWidth: { type: 'number', default: 1380 },
        sectionPadding: { type: 'number', default: 42 },
        infoPadding: { type: 'number', default: 38 },
        columnGap: { type: 'number', default: 34 },
        borderRadius: { type: 'number', default: 4 },
        galleryHeight: { type: 'number', default: 560 },
        mobileGalleryHeight: { type: 'number', default: 360 },
        imageScale: { type: 'number', default: 100 },
        backgroundColor: { type: 'string', default: '#F6F3EA' },
        surfaceColor: { type: 'string', default: '#FFFFFF' },
        imageBackgroundColor: { type: 'string', default: '#F1F2F4' },
        navyColor: { type: 'string', default: '#101B31' },
        accentColor: { type: 'string', default: '#F2B619' },
        textColor: { type: 'string', default: '#1F2937' },
        mutedColor: { type: 'string', default: '#687385' },
        priceBackgroundColor: { type: 'string', default: '#FFF8DF' }
    };

    registerBlockType('all-star-varsity-jackets/product-page', {
        apiVersion: 3,
        title: __('Varsity Jacket Product Page', 'all-star-varsity-jackets'),
        icon: 'products',
        category: 'all-star-embroidery',
        attributes: attributes,

        edit: function (props) {
            var a = props.attributes;

            return el(wp.element.Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Product Page Content', 'all-star-varsity-jackets'), initialOpen: true },
                        el(Notice, { status: 'info', isDismissible: false }, __('On a real product page this block automatically uses the linked varsity jacket product. In the editor it previews the first linked jacket unless you enter a WooCommerce product ID below.', 'all-star-varsity-jackets')),
                        el(TextControl, { label: __('Preview WooCommerce product ID', 'all-star-varsity-jackets'), type: 'number', value: a.previewProductId || '', onChange: function (value) { set(props, 'previewProductId', parseInt(value || '0', 10) || 0); } }),
                        el(TextControl, { label: __('Eyebrow', 'all-star-varsity-jackets'), value: a.eyebrow || '', onChange: function (value) { set(props, 'eyebrow', value); } }),
                        el(TextControl, { label: __('Style switcher label', 'all-star-varsity-jackets'), value: a.styleSwitcherLabel || 'JACKET STYLES', onChange: function (value) { set(props, 'styleSwitcherLabel', value); } }),
                        el(TextControl, { label: __('Price label', 'all-star-varsity-jackets'), value: a.priceLabel || '', onChange: function (value) { set(props, 'priceLabel', value); } }),
                        el(TextareaControl, { label: __('Price clarification', 'all-star-varsity-jackets'), help: __('Use this to make it unmistakable that customization costs extra.', 'all-star-varsity-jackets'), value: a.priceNote || '', onChange: function (value) { set(props, 'priceNote', value); } }),
                        el(TextControl, { label: __('Order heading', 'all-star-varsity-jackets'), value: a.orderHeading || '', onChange: function (value) { set(props, 'orderHeading', value); } }),
                        el(TextareaControl, { label: __('Order message', 'all-star-varsity-jackets'), value: a.orderText || '', onChange: function (value) { set(props, 'orderText', value); } }),
                        el(TextControl, { label: __('Button label', 'all-star-varsity-jackets'), value: a.buttonLabel || '', onChange: function (value) { set(props, 'buttonLabel', value); } }),
                        el(TextControl, { label: __('Store phone number', 'all-star-varsity-jackets'), help: __('When set, the order button becomes a tap-to-call link.', 'all-star-varsity-jackets'), value: a.phoneNumber || '', onChange: function (value) { set(props, 'phoneNumber', value); } }),
                        el(TextControl, { label: __('Contact URL fallback', 'all-star-varsity-jackets'), help: __('Used only when no phone number is entered.', 'all-star-varsity-jackets'), value: a.contactUrl || '', onChange: function (value) { set(props, 'contactUrl', value); } })
                    ),

                    el(PanelBody, { title: __('Customizations', 'all-star-varsity-jackets'), initialOpen: false },
                        el(ToggleControl, { label: __('Show customizations section', 'all-star-varsity-jackets'), checked: a.showCustomizations !== false, onChange: function (value) { set(props, 'showCustomizations', value); } }),
                        el(TextControl, { label: __('Section heading', 'all-star-varsity-jackets'), value: a.customizationsHeading || '', onChange: function (value) { set(props, 'customizationsHeading', value); } }),
                        el(TextControl, { label: __('Section subheading', 'all-star-varsity-jackets'), value: a.customizationsSubheading || '', onChange: function (value) { set(props, 'customizationsSubheading', value); } }),
                        el(TextareaControl, { label: __('Customization items', 'all-star-varsity-jackets'), help: __('One per line using: Title|Description', 'all-star-varsity-jackets'), rows: 8, value: a.customizations || '', onChange: function (value) { set(props, 'customizations', value); } })
                    ),

                    el(PanelBody, { title: __('Visibility', 'all-star-varsity-jackets'), initialOpen: false },
                        el(ToggleControl, { label: __('Show school / mascot / location', 'all-star-varsity-jackets'), checked: a.showSchoolMeta !== false, onChange: function (value) { set(props, 'showSchoolMeta', value); } }),
                        el(ToggleControl, { label: __('Show other styles for this school', 'all-star-varsity-jackets'), checked: a.showStyleSwitcher !== false, onChange: function (value) { set(props, 'showStyleSwitcher', value); } }),
                        el(ToggleControl, { label: __('Show jacket description', 'all-star-varsity-jackets'), checked: a.showDescription !== false, onChange: function (value) { set(props, 'showDescription', value); } }),
                        el(ToggleControl, { label: __('Show jacket features', 'all-star-varsity-jackets'), checked: a.showFeatures !== false, onChange: function (value) { set(props, 'showFeatures', value); } }),
                        el(ToggleControl, { label: __('Show 3-step order process', 'all-star-varsity-jackets'), checked: a.showProcess !== false, onChange: function (value) { set(props, 'showProcess', value); } }),
                        el(ToggleControl, { label: __('Full-bleed background', 'all-star-varsity-jackets'), checked: a.fullBleed !== false, onChange: function (value) { set(props, 'fullBleed', value); } })
                    ),

                    el(PanelBody, { title: __('Layout', 'all-star-varsity-jackets'), initialOpen: false },
                        el(RangeControl, { label: __('Maximum content width', 'all-star-varsity-jackets'), value: a.maxWidth || 1380, min: 900, max: 1680, step: 20, onChange: function (value) { set(props, 'maxWidth', value); } }),
                        el(RangeControl, { label: __('Section padding', 'all-star-varsity-jackets'), value: a.sectionPadding || 42, min: 18, max: 110, step: 2, onChange: function (value) { set(props, 'sectionPadding', value); } }),
                        el(RangeControl, { label: __('Info panel padding', 'all-star-varsity-jackets'), value: a.infoPadding || 38, min: 18, max: 80, step: 2, onChange: function (value) { set(props, 'infoPadding', value); } }),
                        el(RangeControl, { label: __('Column gap', 'all-star-varsity-jackets'), value: a.columnGap || 34, min: 12, max: 72, step: 2, onChange: function (value) { set(props, 'columnGap', value); } }),
                        el(RangeControl, { label: __('Corner radius', 'all-star-varsity-jackets'), value: (typeof a.borderRadius === 'number' ? a.borderRadius : 4), min: 0, max: 4, step: 1, onChange: function (value) { set(props, 'borderRadius', value); } }),
                        el(Notice, { status: 'info', isDismissible: false }, __('Gallery sizing: responsive medium / scale to fit. The whole jacket stays visible, but the image is capped to the viewport so the product section does not dominate the page.', 'all-star-varsity-jackets')),
                        el(RangeControl, { label: __('Jacket image size', 'all-star-varsity-jackets'), help: __('Use this to make the jacket smaller inside the responsive medium gallery. 100% still keeps the full jacket visible.', 'all-star-varsity-jackets'), value: a.imageScale || 100, min: 70, max: 100, step: 5, onChange: function (value) { set(props, 'imageScale', value); } })
                    ),

                    el(PanelBody, { title: __('Colors', 'all-star-varsity-jackets'), initialOpen: false },
                        colorControl(props, 'backgroundColor', __('Page background', 'all-star-varsity-jackets')),
                        colorControl(props, 'surfaceColor', __('Main card surface', 'all-star-varsity-jackets')),
                        colorControl(props, 'imageBackgroundColor', __('Image background', 'all-star-varsity-jackets')),
                        colorControl(props, 'navyColor', __('Primary / navy', 'all-star-varsity-jackets')),
                        colorControl(props, 'accentColor', __('Accent / gold', 'all-star-varsity-jackets')),
                        colorControl(props, 'textColor', __('Body text', 'all-star-varsity-jackets')),
                        colorControl(props, 'mutedColor', __('Muted text', 'all-star-varsity-jackets')),
                        colorControl(props, 'priceBackgroundColor', __('Price notice background', 'all-star-varsity-jackets'))
                    )
                ),
                el('div', { className: 'asevj-vjpp-editor-preview' },
                    el(ServerSideRender, {
                        block: 'all-star-varsity-jackets/product-page',
                        attributes: a
                    })
                )
            );
        },

        save: function () {
            return null;
        }
    });
})(window.wp);
