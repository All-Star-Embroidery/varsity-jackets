from pathlib import Path
import json
import re

root = Path('all-star-varsity-jackets')

# Version bump.
main = root / 'all-star-varsity-jackets.php'
s = main.read_text(encoding='utf-8')
s = s.replace('Version: 1.0.5', 'Version: 1.0.6')
s = s.replace("define( 'ASEVJ_VERSION', '1.0.5' );", "define( 'ASEVJ_VERSION', '1.0.6' );")
main.write_text(s, encoding='utf-8')

for block_json in (root / 'blocks').glob('*/block.json'):
    data = json.loads(block_json.read_text(encoding='utf-8'))
    data['version'] = '1.0.6'
    block_json.write_text(json.dumps(data, indent=2) + '\n', encoding='utf-8')

# Move Available Customizations into the school/style sidebar.
render = root / 'includes/class-asevj-render.php'
r = render.read_text(encoding='utf-8')
customization = '''                    <aside class="asevj-customizations" aria-label="Available customizations">
                        <div class="asevj-customizations__heading"><span>AVAILABLE</span><strong>CUSTOMIZATIONS</strong></div>
                        <div class="asevj-customization-list">
                            <div><b>A</b><p><strong>Chenille Letters</strong><small>Classic 3D chenille letters in school colors.</small></p></div>
                            <div><b>T</b><p><strong>Tackle Twill</strong><small>One-color or multi-color twill designs.</small></p></div>
                            <div><b>✦</b><p><strong>Embroidery</strong><small>Names, mascots, and custom embroidery.</small></p></div>
                            <div><b>◇</b><p><strong>Patches</strong><small>School, achievement, and custom patches.</small></p></div>
                            <div><b>23</b><p><strong>Names &amp; Numerals</strong><small>Graduation year, jersey numbers, and personalization.</small></p></div>
                        </div>
                    </aside>
'''
if customization not in r:
    raise SystemExit('Expected customization panel not found in render source')
r = r.replace(customization, '', 1)
pattern = re.compile(r'(\s*<div class="asevj-style-picker" data-asevj-style-picker>.*?</div>)(\s*</aside>)', re.S)
match = pattern.search(r)
if not match:
    raise SystemExit('Could not find style picker/sidebar insertion point')
compact_customization = '''
                        <aside class="asevj-customizations asevj-customizations--sidebar" aria-label="Available customizations">
                            <div class="asevj-customizations__heading"><span>AVAILABLE</span><strong>CUSTOMIZATIONS</strong></div>
                            <div class="asevj-customization-list">
                                <div><b>A</b><p><strong>Chenille Letters</strong><small>Classic 3D chenille letters in school colors.</small></p></div>
                                <div><b>T</b><p><strong>Tackle Twill</strong><small>One-color or multi-color twill designs.</small></p></div>
                                <div><b>✦</b><p><strong>Embroidery</strong><small>Names, mascots, and custom embroidery.</small></p></div>
                                <div><b>◇</b><p><strong>Patches</strong><small>School, achievement, and custom patches.</small></p></div>
                                <div><b>23</b><p><strong>Names &amp; Numerals</strong><small>Graduation year, jersey numbers, and personalization.</small></p></div>
                            </div>
                        </aside>'''
r = r[:match.start()] + match.group(1) + compact_customization + match.group(2) + r[match.end():]
render.write_text(r, encoding='utf-8')

css = root / 'assets/frontend.css'
c = css.read_text(encoding='utf-8')
c += r'''

/* v1.0.6 — compact Browse by School composition */
.asevj-school-strip-wrap{margin-bottom:14px}
.asevj-school-stage{grid-template-columns:minmax(235px,270px) minmax(0,1fr);gap:18px;padding-top:14px}
.asevj-school-summary{min-width:0;padding-top:2px}
.asevj-school-summary__meta{margin:9px 0 10px}
.asevj-style-picker-heading{margin-top:8px}
.asevj-style-picker{gap:7px}
.asevj-style-choice{min-height:0;padding-top:8px;padding-bottom:8px}
.asevj-customizations--sidebar{width:100%;min-width:0;margin:14px 0 0;padding:12px 0 0;border:0;border-top:1px solid #e3e7ed;border-radius:0;background:transparent;box-shadow:none}
.asevj-customizations--sidebar .asevj-customizations__heading{display:flex;align-items:baseline;gap:5px;margin:0 0 7px;padding:0;text-align:left}
.asevj-customizations--sidebar .asevj-customizations__heading span,.asevj-customizations--sidebar .asevj-customizations__heading strong{display:inline;margin:0;font-size:9px;line-height:1.15;letter-spacing:.075em}
.asevj-customizations--sidebar .asevj-customization-list{display:grid;grid-template-columns:1fr;gap:2px;margin:0;padding:0}
.asevj-customizations--sidebar .asevj-customization-list>div{display:grid;grid-template-columns:27px minmax(0,1fr);gap:8px;align-items:center;min-height:36px;margin:0;padding:5px 2px;border:0;border-bottom:1px solid #edf0f3;background:transparent}
.asevj-customizations--sidebar .asevj-customization-list>div:last-child{border-bottom:0}
.asevj-customizations--sidebar .asevj-customization-list>div>b{width:27px;height:27px;display:grid;place-items:center;margin:0;font-size:14px;line-height:1}
.asevj-customizations--sidebar .asevj-customization-list p{min-width:0;margin:0;display:flex;flex-direction:column;gap:1px}
.asevj-customizations--sidebar .asevj-customization-list p strong{font-size:10px;line-height:1.15}
.asevj-customizations--sidebar .asevj-customization-list p small{font-size:8.5px;line-height:1.25;color:#697487}
.asevj-style-showcase{min-width:0}
.asevj-style-showcase__header{margin-bottom:8px}
.asevj-style-showcase__footer{padding-top:9px}
@media (min-width:1181px){.asevj-browser{padding-top:max(18px,calc(var(--asevj-section-gap,24px) - 4px));padding-bottom:max(22px,var(--asevj-section-gap,24px))}}
@media (max-width:980px){.asevj-school-stage{gap:14px}.asevj-customizations--sidebar{margin-top:12px;padding-top:10px}.asevj-customizations--sidebar .asevj-customization-list{display:flex;gap:7px;overflow-x:auto;overscroll-behavior-inline:contain;scrollbar-width:none;padding:1px 1px 5px;scroll-snap-type:x proximity}.asevj-customizations--sidebar .asevj-customization-list::-webkit-scrollbar{display:none}.asevj-customizations--sidebar .asevj-customization-list>div{flex:0 0 min(185px,62vw);min-height:52px;padding:7px 9px;border:1px solid #e1e6ec;border-radius:6px;background:#fff;scroll-snap-align:start}}
@media (max-width:760px){.asevj-school-strip-wrap{margin-bottom:10px}.asevj-school-stage{padding-top:11px}.asevj-customizations--sidebar .asevj-customizations__heading{margin-bottom:6px}.asevj-customizations--sidebar .asevj-customization-list>div{flex-basis:min(170px,70vw)}}
'''
css.write_text(c, encoding='utf-8')

changelog = root / 'CHANGELOG.md'
entry = '''## 1.0.6\n\n- Moved Available Customizations underneath the jacket style selector in the school sidebar.\n- Removed the separate customization grid item that was making Browse by School unnecessarily tall.\n- Gave the selected jacket gallery more horizontal room with a narrower, denser school/style sidebar.\n- Compacted customization typography, spacing, and dividers while retaining the full descriptions.\n- Made customization options horizontally swipeable on tablets/phones to avoid a tall mobile stack.\n- Tightened school-carousel and school-stage vertical spacing.\n\n'''
changelog.write_text(entry + changelog.read_text(encoding='utf-8'), encoding='utf-8')

readme = root / 'readme.txt'
rt = readme.read_text(encoding='utf-8')
rt = re.sub(r'Stable tag:\s*[^\n]+', 'Stable tag: 1.0.6', rt)
rt += '''\n= 1.0.6 =\n* Compacted Browse by School and moved Available Customizations beneath the style selector.\n* Gave the jacket gallery more horizontal room and reduced excess vertical space.\n* Made customization options horizontally swipeable on smaller screens.\n'''
readme.write_text(rt, encoding='utf-8')
