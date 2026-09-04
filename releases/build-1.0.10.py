from pathlib import Path
import json, re

root = Path('all-star-varsity-jackets')

main = root / 'all-star-varsity-jackets.php'
s = main.read_text(encoding='utf-8')
s = s.replace('Version: 1.0.9', 'Version: 1.0.10')
s = s.replace("define( 'ASEVJ_VERSION', '1.0.9' );", "define( 'ASEVJ_VERSION', '1.0.10' );")
main.write_text(s, encoding='utf-8')

for block_json in (root / 'blocks').glob('*/block.json'):
    data = json.loads(block_json.read_text(encoding='utf-8'))
    data['version'] = '1.0.10'
    if data.get('name') == 'all-star-varsity-jackets/product-page':
        data['attributes']['borderRadius']['default'] = 4
    block_json.write_text(json.dumps(data, indent=2) + '\n', encoding='utf-8')

editor = root / 'blocks/product-page/editor.js'
e = editor.read_text(encoding='utf-8')
e = e.replace("borderRadius: { type: 'number', default: 10 }", "borderRadius: { type: 'number', default: 4 }")
e = e.replace("value: a.borderRadius || 10, min: 0, max: 36", "value: (typeof a.borderRadius === 'number' ? a.borderRadius : 4), min: 0, max: 4")
e = e.replace("label: __('Jacket image scale', 'all-star-varsity-jackets'), value: a.imageScale || 100, min: 70, max: 125", "label: __('Jacket image size', 'all-star-varsity-jackets'), help: __('100% is the largest safe size and always keeps the full jacket visible.', 'all-star-varsity-jackets'), value: a.imageScale || 100, min: 70, max: 100")
editor.write_text(e, encoding='utf-8')

product = root / 'includes/class-asevj-product-page.php'
p = product.read_text(encoding='utf-8')
p = p.replace("$radius = min( 36, max( 0, absint( $attributes['borderRadius'] ?? 10 ) ) );", "$radius = min( 4, max( 0, absint( $attributes['borderRadius'] ?? 4 ) ) );")
p = p.replace("$image_scale = min( 125, max( 70, absint( $attributes['imageScale'] ?? 100 ) ) );", "$image_scale = min( 100, max( 70, absint( $attributes['imageScale'] ?? 100 ) ) );")
product.write_text(p, encoding='utf-8')

css = root / 'blocks/product-page/style.css'
c = css.read_text(encoding='utf-8')
if 'v1.0.10 — full-image safety' not in c:
    c += r'''

/* v1.0.10 — full-image safety + sharper retail/document visual language */
.asevj-vjpp__gallery,.asevj-vjpp__info{border-color:#d8dde4;border-radius:var(--asevj-vjpp-radius,4px);box-shadow:none!important}
.asevj-vjpp__main-image{padding:clamp(8px,1vw,14px)}
.asevj-vjpp__main-image img{width:100%!important;height:100%!important;max-width:100%!important;max-height:100%!important;object-fit:contain!important;object-position:center!important;transform:scale(calc(var(--asevj-vjpp-image-scale,100) / 100))!important}
.asevj-vjpp__thumb{border-radius:3px;box-shadow:none!important}
.asevj-vjpp__thumb.is-active{box-shadow:none!important;border-width:2px}
.asevj-vjpp__order{border-top:1px solid rgba(255,255,255,.18);box-shadow:none!important}
.asevj-vjpp__order a{border-radius:3px;box-shadow:none!important}
.asevj-vjpp__customization-grid,.asevj-vjpp__process{border-radius:var(--asevj-vjpp-radius,4px);box-shadow:none!important}
.asevj-vjpp__customization-grid{border-color:#d8dde4}
.asevj-vjpp__price{box-shadow:none!important}
@media(max-width:820px){.asevj-vjpp__customization-grid{border-radius:4px}}
'''
css.write_text(c, encoding='utf-8')

changelog = root / 'CHANGELOG.md'
old = changelog.read_text(encoding='utf-8')
entry = '''## 1.0.10\n\n- Ensured the full jacket image always remains visible at every responsive size.\n- Capped jacket image scaling at 100% to prevent viewport cropping.\n- Sharpened the product-page design to 3–4px radii and removed soft SaaS-style shadows.\n- Replaced floating-card styling with hairline borders and flatter retail/document surfaces.\n\n## 1.0.9\n\n- Added automatic routing of linked varsity WooCommerce products to the dedicated jacket product template.\n- Added the editable shared Product Page Template.\n- Disabled online purchasing for linked varsity jacket products while leaving normal WooCommerce products unchanged.\n\n'''
if not old.startswith('## 1.0.10'):
    changelog.write_text(entry + old, encoding='utf-8')

readme = root / 'readme.txt'
if readme.exists():
    rt = readme.read_text(encoding='utf-8')
    rt = re.sub(r'Stable tag:\s*[^\n]+', 'Stable tag: 1.0.10', rt)
    if '= 1.0.10 =' not in rt:
        rt += '\n= 1.0.10 =\n* Keeps the full jacket image visible across responsive sizes.\n* Sharpens the dedicated product-page visual system and removes soft card shadows.\n'
    readme.write_text(rt, encoding='utf-8')
