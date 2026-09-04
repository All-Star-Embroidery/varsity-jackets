from pathlib import Path
import json, re

root = Path('all-star-varsity-jackets')

main = root / 'all-star-varsity-jackets.php'
s = main.read_text(encoding='utf-8')
s = s.replace('Version: 1.0.13', 'Version: 1.0.14')
s = s.replace("define( 'ASEVJ_VERSION', '1.0.13' );", "define( 'ASEVJ_VERSION', '1.0.14' );")
main.write_text(s, encoding='utf-8')

for p in (root / 'blocks').glob('*/block.json'):
    data = json.loads(p.read_text(encoding='utf-8'))
    data['version'] = '1.0.14'
    if data.get('name') == 'all-star-varsity-jackets/product-page':
        attrs = data.setdefault('attributes', {})
        attrs['styleSwitcherLabel'] = {'type':'string','default':'JACKET STYLES'}
        attrs['showStyleSwitcher'] = {'type':'boolean','default':True}
        styles = data.get('style', [])
        if isinstance(styles, str):
            styles = [styles]
        if 'file:./identity.css' not in styles:
            styles.append('file:./identity.css')
        data['style'] = styles
    p.write_text(json.dumps(data, indent=2) + '\n', encoding='utf-8')

editor = root / 'blocks/product-page/editor.js'
e = editor.read_text(encoding='utf-8')
if "styleSwitcherLabel: { type: 'string', default: 'JACKET STYLES' }" not in e:
    e = e.replace("        eyebrow: { type: 'string', default: 'CUSTOM VARSITY JACKET' },\n", "        eyebrow: { type: 'string', default: 'CUSTOM VARSITY JACKET' },\n        styleSwitcherLabel: { type: 'string', default: 'JACKET STYLES' },\n")
if "showStyleSwitcher: { type: 'boolean', default: true }" not in e:
    e = e.replace("        showSchoolMeta: { type: 'boolean', default: true },\n", "        showSchoolMeta: { type: 'boolean', default: true },\n        showStyleSwitcher: { type: 'boolean', default: true },\n")
if "label: __('Style switcher label'" not in e:
    e = e.replace("                        el(TextControl, { label: __('Eyebrow', 'all-star-varsity-jackets'), value: a.eyebrow || '', onChange: function (value) { set(props, 'eyebrow', value); } }),\n", "                        el(TextControl, { label: __('Eyebrow', 'all-star-varsity-jackets'), value: a.eyebrow || '', onChange: function (value) { set(props, 'eyebrow', value); } }),\n                        el(TextControl, { label: __('Style switcher label', 'all-star-varsity-jackets'), value: a.styleSwitcherLabel || 'JACKET STYLES', onChange: function (value) { set(props, 'styleSwitcherLabel', value); } }),\n")
if "label: __('Show other styles for this school'" not in e:
    e = e.replace("                        el(ToggleControl, { label: __('Show school / mascot / location', 'all-star-varsity-jackets'), checked: a.showSchoolMeta !== false, onChange: function (value) { set(props, 'showSchoolMeta', value); } }),\n", "                        el(ToggleControl, { label: __('Show school / mascot / location', 'all-star-varsity-jackets'), checked: a.showSchoolMeta !== false, onChange: function (value) { set(props, 'showSchoolMeta', value); } }),\n                        el(ToggleControl, { label: __('Show other styles for this school', 'all-star-varsity-jackets'), checked: a.showStyleSwitcher !== false, onChange: function (value) { set(props, 'showStyleSwitcher', value); } }),\n")
editor.write_text(e, encoding='utf-8')

product = root / 'includes/class-asevj-product-page.php'
p = product.read_text(encoding='utf-8')
helper_anchor = "    public static function render( array $attributes = [] ): string {\n"
helper = '''    private static function same_school_styles( int $school_id, int $current_style_id ): array {\n        if ( ! $school_id ) {\n            return [];\n        }\n\n        $style_ids = get_posts( [\n            'post_type'      => 'asevj_style',\n            'post_status'    => 'publish',\n            'posts_per_page' => -1,\n            'fields'         => 'ids',\n            'meta_key'       => '_asevj_school_id',\n            'meta_value'     => $school_id,\n            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],\n            'order'          => 'ASC',\n        ] );\n\n        $styles = [];\n        foreach ( $style_ids as $candidate_id ) {\n            $candidate_id = absint( $candidate_id );\n            $product_id = absint( get_post_meta( $candidate_id, '_asevj_woo_product_id', true ) );\n            if ( ! $product_id || 'product' !== get_post_type( $product_id ) || 'publish' !== get_post_status( $product_id ) ) {\n                continue;\n            }\n\n            $url = get_permalink( $product_id );\n            if ( ! $url ) {\n                continue;\n            }\n\n            $styles[] = [\n                'id'      => $candidate_id,\n                'name'    => get_the_title( $candidate_id ),\n                'url'     => $url,\n                'current' => $candidate_id === $current_style_id,\n            ];\n        }\n\n        return $styles;\n    }\n\n'''
if 'private static function same_school_styles' not in p:
    if helper_anchor not in p:
        raise SystemExit('render() anchor not found')
    p = p.replace(helper_anchor, helper + helper_anchor, 1)

if '$same_school_styles = self::same_school_styles' not in p:
    p = p.replace("        $price_html = self::price_html( $style_id, $product );\n", "        $price_html = self::price_html( $style_id, $product );\n        $same_school_styles = self::same_school_styles( $school_id, $style_id );\n", 1)
if '$style_switcher_label =' not in p:
    p = p.replace("        $price_label = sanitize_text_field( (string) ( $attributes['priceLabel'] ?? 'STARTING AT' ) );\n", "        $price_label = sanitize_text_field( (string) ( $attributes['priceLabel'] ?? 'STARTING AT' ) );\n        $style_switcher_label = sanitize_text_field( (string) ( $attributes['styleSwitcherLabel'] ?? 'JACKET STYLES' ) );\n", 1)
if '$show_style_switcher =' not in p:
    p = p.replace("        $show_school_meta = ! array_key_exists( 'showSchoolMeta', $attributes ) || ! empty( $attributes['showSchoolMeta'] );\n", "        $show_school_meta = ! array_key_exists( 'showSchoolMeta', $attributes ) || ! empty( $attributes['showSchoolMeta'] );\n        $show_style_switcher = ! array_key_exists( 'showStyleSwitcher', $attributes ) || ! empty( $attributes['showStyleSwitcher'] );\n", 1)

old_identity = "                            <div><small><?php echo esc_html( $school_name ); ?></small><h1><?php echo esc_html( $style_name ); ?></h1></div>\n"
new_identity = "                            <div><h1><?php echo esc_html( $school_name ); ?></h1><p class=\"asevj-vjpp__style-name\"><?php echo esc_html( $style_name ); ?></p></div>\n"
if old_identity in p:
    p = p.replace(old_identity, new_identity, 1)
elif 'asevj-vjpp__style-name' not in p:
    raise SystemExit('School/style identity markup not found')

switcher_anchor = "                        <?php if ( $price_html ) : ?>\n"
switcher_markup = '''                        <?php if ( $show_style_switcher && count( $same_school_styles ) > 1 ) : ?>\n                            <nav class=\"asevj-vjpp__style-switcher\" aria-label=\"Other jacket styles for <?php echo esc_attr( $school_name ); ?>\">\n                                <span class=\"asevj-vjpp__style-switcher-label\"><?php echo esc_html( $style_switcher_label ); ?></span>\n                                <div class=\"asevj-vjpp__style-switcher-links\">\n                                    <?php foreach ( $same_school_styles as $school_style ) : ?>\n                                        <?php if ( ! empty( $school_style['current'] ) ) : ?>\n                                            <span class=\"asevj-vjpp__style-choice is-active\" aria-current=\"page\"><?php echo esc_html( $school_style['name'] ); ?></span>\n                                        <?php else : ?>\n                                            <a class=\"asevj-vjpp__style-choice\" href=\"<?php echo esc_url( $school_style['url'] ); ?>\"><?php echo esc_html( $school_style['name'] ); ?></a>\n                                        <?php endif; ?>\n                                    <?php endforeach; ?>\n                                </div>\n                            </nav>\n                        <?php endif; ?>\n\n'''
if 'asevj-vjpp__style-switcher' not in p:
    if switcher_anchor not in p:
        raise SystemExit('Price markup anchor not found')
    p = p.replace(switcher_anchor, switcher_markup + switcher_anchor, 1)
product.write_text(p, encoding='utf-8')

changelog = root / 'CHANGELOG.md'
old = changelog.read_text(encoding='utf-8')
if not old.startswith('## 1.0.14'):
    entry = """## 1.0.14\n\n- Made the school name the primary product-page heading and moved the jacket style name to a smaller subtitle.\n- Added an automatic same-school Jacket Styles switcher below mascot/location metadata when multiple linked styles exist.\n- Current style is clearly marked; alternate styles link directly to their dedicated varsity product pages.\n- Added block controls for the style-switcher label and visibility.\n\n"""
    changelog.write_text(entry + old, encoding='utf-8')

readme = root / 'readme.txt'
if readme.exists():
    rt = readme.read_text(encoding='utf-8')
    rt = re.sub(r'Stable tag:\s*[^\n]+', 'Stable tag: 1.0.14', rt)
    if '= 1.0.14 =' not in rt:
        rt += "\n= 1.0.14 =\n* School name is now the primary product heading with the style as a subtitle.\n* Adds a same-school jacket style switcher to linked product pages.\n"
    readme.write_text(rt, encoding='utf-8')
