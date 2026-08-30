from pathlib import Path
import json, re

root = Path('all-star-varsity-jackets')

main = root / 'all-star-varsity-jackets.php'
s = main.read_text(encoding='utf-8')
s = s.replace('Version: 1.0.6', 'Version: 1.0.7')
s = s.replace("define( 'ASEVJ_VERSION', '1.0.6' );", "define( 'ASEVJ_VERSION', '1.0.7' );")
main.write_text(s, encoding='utf-8')

for block_json in (root / 'blocks').glob('*/block.json'):
    data = json.loads(block_json.read_text(encoding='utf-8'))
    data['version'] = '1.0.7'
    block_json.write_text(json.dumps(data, indent=2) + '\n', encoding='utf-8')

css = root / 'assets/frontend.css'
c = css.read_text(encoding='utf-8')
c += r'''

/* v1.0.7 — slightly smaller inline Browse by School gallery */
.asevj-style-showcase [data-asevj-selected-gallery]{max-width:100%}
.asevj-style-showcase .asevj-selected-gallery{min-height:0}
.asevj-style-showcase .asevj-gallery-tile{min-height:0;height:clamp(118px,10.5vw,164px)}
.asevj-style-showcase .asevj-gallery-tile.is-main{height:clamp(270px,27vw,365px)}
.asevj-style-showcase .asevj-gallery-tile img{width:100%;height:100%;object-fit:contain;object-position:center}
@media(max-width:1180px){
  .asevj-style-showcase .asevj-gallery-tile{height:clamp(110px,12vw,150px)}
  .asevj-style-showcase .asevj-gallery-tile.is-main{height:clamp(245px,31vw,330px)}
}
@media(max-width:760px){
  .asevj-style-showcase .asevj-gallery-tile{height:clamp(105px,30vw,145px)}
  .asevj-style-showcase .asevj-gallery-tile.is-main{height:clamp(230px,68vw,315px)}
}
'''
css.write_text(c, encoding='utf-8')

changelog = root / 'CHANGELOG.md'
entry = '''## 1.0.7\n\n- Reduced the inline Browse by School gallery height to keep more of the selector in one viewport.\n- Slightly reduced supporting gallery tiles while preserving image containment.\n- Added responsive gallery caps for laptop, tablet, and mobile widths.\n- Left the jacket-detail popup sizing unchanged.\n\n'''
changelog.write_text(entry + changelog.read_text(encoding='utf-8'), encoding='utf-8')

readme = root / 'readme.txt'
rt = readme.read_text(encoding='utf-8')
rt = re.sub(r'Stable tag:\s*[^\n]+', 'Stable tag: 1.0.7', rt)
rt += '''\n= 1.0.7 =\n* Reduced Browse by School gallery height to minimize scrolling.\n* Preserved uncropped jacket images with responsive sizing.\n'''
readme.write_text(rt, encoding='utf-8')
