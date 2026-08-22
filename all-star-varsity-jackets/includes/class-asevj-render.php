<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Render {
    public static function settings(): array {
        return wp_parse_args( get_option( 'asevj_design_settings', [] ), ASEVJ_Admin::default_design_settings() );
    }

    private static function tri_state( array $attributes, string $key, bool $global ): bool {
        $value = isset( $attributes[ $key ] ) ? (string) $attributes[ $key ] : 'global';
        if ( 'show' === $value ) {
            return true;
        }
        if ( 'hide' === $value ) {
            return false;
        }
        return $global;
    }

    public static function css_vars(): string {
        $s = self::settings();
        return sprintf(
            '--asevj-navy:%1$s;--asevj-gold:%2$s;--asevj-accent:%3$s;--asevj-cream:%4$s;--asevj-white:%5$s;--asevj-light:%6$s;--asevj-text:%7$s;--asevj-muted:%8$s;--asevj-radius:%9$dpx;--asevj-button-radius:%10$dpx;--asevj-max-width:%11$dpx;--asevj-hero-height:%12$dpx;--asevj-section-gap:%13$dpx;--asevj-styles-visible:%14$d;--asevj-school-tile-width:%15$dpx;--asevj-heading-font:%16$s;--asevj-body-font:%17$s;',
            esc_attr( $s['navy'] ),
            esc_attr( $s['gold'] ),
            esc_attr( $s['accent'] ),
            esc_attr( $s['cream'] ),
            esc_attr( $s['white'] ),
            esc_attr( $s['light'] ),
            esc_attr( $s['text'] ),
            esc_attr( $s['muted'] ),
            absint( $s['radius'] ),
            absint( $s['button_radius'] ),
            absint( $s['max_width'] ),
            absint( $s['hero_height'] ),
            absint( $s['section_gap'] ),
            absint( $s['styles_visible'] ),
            absint( $s['school_tile_width'] ),
            esc_attr( $s['heading_font'] ?: 'inherit' ),
            esc_attr( $s['body_font'] ?: 'inherit' )
        );
    }

    private static function is_editor_preview(): bool {
        if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
            return false;
        }
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        return str_contains( $uri, '/block-renderer/' );
    }

    public static function get_schools_data(): array {
        $schools = get_posts( [
            'post_type'      => 'asevj_school',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_asevj_enabled',
            'meta_value'     => '1',
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
        ] );

        $out = [];
        foreach ( $schools as $school ) {
            $styles = get_posts( [
                'post_type'      => 'asevj_style',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => '_asevj_school_id',
                'meta_value'     => $school->ID,
                'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
            ] );

            $style_data = [];
            $style_number = 0;
            foreach ( $styles as $style ) {
                if ( ! get_post_meta( $style->ID, '_asevj_enabled', true ) ) {
                    continue;
                }
                $style_number++;

                $product_id = absint( get_post_meta( $style->ID, '_asevj_woo_product_id', true ) );
                $price_html = '';
                $url = '';
                if ( $product_id && function_exists( 'wc_get_product' ) ) {
                    $product = wc_get_product( $product_id );
                    if ( $product ) {
                        $price_html = $product->get_price_html();
                        $url = $product->get_permalink();
                    }
                }

                if ( ! $price_html ) {
                    $fallback = (string) get_post_meta( $style->ID, '_asevj_fallback_price', true );
                    if ( '' !== $fallback ) {
                        $price_html = function_exists( 'wc_price' ) ? wc_price( $fallback ) : '$' . number_format_i18n( (float) $fallback, 2 );
                    }
                }

                $gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $style->ID, '_asevj_gallery_ids', true ) ) ) );
                $gallery = [];
                foreach ( $gallery_ids as $gallery_id ) {
                    $full = wp_get_attachment_image_url( $gallery_id, 'large' );
                    $thumb = wp_get_attachment_image_url( $gallery_id, 'medium' );
                    if ( $full ) {
                        $gallery[] = [
                            'full'  => $full,
                            'thumb' => $thumb ?: $full,
                            'alt'   => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $gallery_id ),
                        ];
                    }
                }

                $features_raw = (string) get_post_meta( $style->ID, '_asevj_features', true );
                $features = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $features_raw ) ) ) );
                $image = get_the_post_thumbnail_url( $style->ID, 'large' );
                $image_full = get_the_post_thumbnail_url( $style->ID, 'full' ) ?: $image;

                $style_data[] = [
                    'id'          => $style->ID,
                    'number'      => $style_number,
                    'name'        => get_the_title( $style ),
                    'subtitle'    => (string) get_post_meta( $style->ID, '_asevj_subtitle', true ),
                    'description' => (string) get_post_meta( $style->ID, '_asevj_description', true ),
                    'image'       => $image ?: '',
                    'imageFull'   => $image_full ?: '',
                    'gallery'     => $gallery,
                    'features'    => $features,
                    'priceHtml'   => wp_kses_post( $price_html ),
                    'url'         => $url,
                    'cta'         => (string) get_post_meta( $style->ID, '_asevj_cta', true ) ?: 'Customize This Jacket',
                    'linkedWoo'   => (bool) $product_id,
                ];
            }

            if ( ! $style_data ) {
                continue;
            }

            $logo_id = absint( get_post_meta( $school->ID, '_asevj_logo_id', true ) );
            $logo = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
            $name = get_the_title( $school );

            $out[] = [
                'id'          => $school->ID,
                'slug'        => $school->post_name ?: sanitize_title( $name ),
                'name'        => $name,
                'mascot'      => (string) get_post_meta( $school->ID, '_asevj_mascot', true ),
                'location'    => (string) get_post_meta( $school->ID, '_asevj_location', true ),
                'district'    => (string) get_post_meta( $school->ID, '_asevj_district', true ),
                'description' => (string) get_post_meta( $school->ID, '_asevj_description', true ),
                'logo'        => $logo ?: '',
                'initials'    => self::initials( $name ),
                'primary'     => (string) get_post_meta( $school->ID, '_asevj_primary', true ) ?: '#F2B619',
                'secondary'   => (string) get_post_meta( $school->ID, '_asevj_secondary', true ) ?: '#101B31',
                'accent'      => (string) get_post_meta( $school->ID, '_asevj_accent', true ) ?: '#FFFFFF',
                'styles'      => $style_data,
            ];
        }
        return $out;
    }

    private static function demo_schools(): array {
        $style = static function ( int $number, string $name, string $description, string $price ): array {
            return [
                'id' => 0,
                'number' => $number,
                'name' => $name,
                'subtitle' => '',
                'description' => $description,
                'image' => '',
                'imageFull' => '',
                'gallery' => [],
                'features' => [ 'Wool Body', 'Leather Sleeves', 'Chenille Letter' ],
                'priceHtml' => $price,
                'url' => '',
                'cta' => 'Customize This Jacket',
                'linkedWoo' => false,
            ];
        };

        return [
            [
                'id' => 0,
                'slug' => 'sample-school',
                'name' => 'Sample School',
                'mascot' => 'Mascot',
                'location' => 'Newark, OH',
                'district' => 'Sample District',
                'description' => 'Select a school to show its available jacket styles, photos, pricing, and customization options.',
                'logo' => '',
                'initials' => 'SS',
                'primary' => '#F2B619',
                'secondary' => '#101B31',
                'accent' => '#FFFFFF',
                'styles' => [
                    $style( 1, 'Classic Wool & Leather', 'Traditional varsity construction with premium materials and custom decoration.', '$169.00' ),
                    $style( 2, 'Modern Letterman', 'A cleaner modern take with embroidery, chenille, and school-specific details.', '$179.00' ),
                    $style( 3, 'Alternate Style', 'A second colorway or construction option for schools with multiple jacket styles.', '$159.00' ),
                ],
            ],
        ];
    }

    private static function initials( string $name ): string {
        $words = preg_split( '/\s+/', trim( $name ) );
        $letters = '';
        foreach ( $words as $word ) {
            if ( '' !== $word ) {
                $letters .= mb_substr( $word, 0, 1 );
            }
            if ( mb_strlen( $letters ) >= 2 ) {
                break;
            }
        }
        return strtoupper( $letters ?: mb_substr( $name, 0, 1 ) );
    }

    private static function icon( string $name ): string {
        $icons = [
            'quality' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="9" r="6"/><path d="M8.5 14.5 7 22l5-3 5 3-1.5-7.5"/><path d="m9.5 9 1.5 1.5 3-3"/></svg>',
            'craft'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20 18.5 5.5a2.1 2.1 0 0 1 3 3L7 23H4v-3Z"/><path d="m14.5 9.5 3 3"/><path d="M3 4h7M6.5 1v7"/></svg>',
            'fit'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3 4 6l2 5 2-1v11h8V10l2 1 2-5-4-3-2 2h-4L8 3Z"/><path d="M9 5c.8 1 1.8 1.5 3 1.5S14.2 6 15 5"/></svg>',
            'scissors'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="7" r="3"/><circle cx="6" cy="17" r="3"/><path d="m8.5 8.5 12 7M8.5 15.5l12-7"/></svg>',
            'measure' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="M7 6v4M10 6v2M13 6v4M16 6v2"/></svg>',
            'thread'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l-1 4 1 10H8L9 7 8 3Z"/><path d="M7 21h10M9 7h6M8 17h8"/></svg>',
            'truck'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h11v11H3zM14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
        ];
        return $icons[ $name ] ?? '';
    }

    private static function render_logo_markup( array $school ): string {
        if ( ! empty( $school['logo'] ) ) {
            return '<span class="asevj-school-logo"><img src="' . esc_url( $school['logo'] ) . '" alt=""></span>';
        }
        return '<span class="asevj-school-logo"><b>' . esc_html( $school['initials'] ) . '</b></span>';
    }

    private static function render_style_card_markup( array $school, array $style, bool $show_prices, int $index ): string {
        ob_start();
        ?>
        <article class="asevj-style-card" data-style-index="<?php echo esc_attr( $index ); ?>">
            <div class="asevj-style-card__label">STYLE <?php echo esc_html( $style['number'] ); ?></div>
            <button type="button" class="asevj-style-card__image" data-asevj-detail="<?php echo esc_attr( $index ); ?>" aria-label="View <?php echo esc_attr( $style['name'] ); ?> details">
                <?php if ( ! empty( $style['image'] ) ) : ?>
                    <img src="<?php echo esc_url( $style['image'] ); ?>" alt="<?php echo esc_attr( $style['name'] ); ?>">
                <?php else : ?>
                    <span class="asevj-jacket-placeholder" aria-hidden="true"><i></i><b>JACKET IMAGE</b></span>
                <?php endif; ?>
                <?php if ( ! empty( $style['linkedWoo'] ) ) : ?><span class="asevj-woo-pill">WooCommerce</span><?php endif; ?>
            </button>
            <div class="asevj-style-card__body">
                <button type="button" class="asevj-style-card__title" data-asevj-detail="<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $style['name'] ); ?></button>
                <?php if ( ! empty( $style['subtitle'] ) ) : ?><div class="asevj-style-subtitle"><?php echo esc_html( $style['subtitle'] ); ?></div><?php endif; ?>
                <p><?php echo esc_html( $style['description'] ?: 'Premium varsity jacket style with custom decoration options.' ); ?></p>
                <?php if ( ! empty( $style['features'] ) ) : ?>
                    <div class="asevj-style-feature-row"><?php foreach ( array_slice( $style['features'], 0, 3 ) as $feature ) : ?><span>✓ <?php echo esc_html( $feature ); ?></span><?php endforeach; ?></div>
                <?php endif; ?>
                <?php if ( $show_prices && ! empty( $style['priceHtml'] ) ) : ?>
                    <div class="asevj-style-price"><small>STARTING AT</small><strong><?php echo wp_kses_post( $style['priceHtml'] ); ?></strong></div>
                <?php endif; ?>
                <?php if ( ! empty( $style['url'] ) ) : ?>
                    <a class="asevj-btn asevj-btn-primary" href="<?php echo esc_url( $style['url'] ); ?>"><?php echo esc_html( $style['cta'] ?: 'Customize This Jacket' ); ?><span>↗</span></a>
                <?php else : ?>
                    <button type="button" class="asevj-btn asevj-btn-primary is-showcase" data-asevj-detail="<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $style['cta'] ?: 'View Jacket Details' ); ?><span>→</span></button>
                <?php endif; ?>
            </div>
        </article>
        <?php
        return (string) ob_get_clean();
    }

    private static function block_color( array $attributes, string $key, string $fallback = '' ): string {
        $value = isset( $attributes[ $key ] ) ? sanitize_hex_color( (string) $attributes[ $key ] ) : '';
        return $value ?: $fallback;
    }

    private static function bleed_class( array $attributes ): string {
        return ( ! array_key_exists( 'fullBleed', $attributes ) || ! empty( $attributes['fullBleed'] ) ) ? ' asevj-block--bleed' : '';
    }

    public static function render_hero( array $attributes = [] ): string {
        $s = self::settings();
        $title  = ! empty( $attributes['title'] ) ? $attributes['title'] : $s['hero_title'];
        $kicker = ! empty( $attributes['kicker'] ) ? $attributes['kicker'] : $s['hero_kicker'];
        $body   = ! empty( $attributes['body'] ) ? $attributes['body'] : $s['hero_body'];
        $image_id = absint( $s['signature_image_id'] );
        $image = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

        if ( ! $image ) {
            $schools = self::get_schools_data();
            if ( ! empty( $schools[0]['styles'][0]['imageFull'] ) ) {
                $image = $schools[0]['styles'][0]['imageFull'];
            }
        }

        wp_enqueue_style( 'asevj-frontend' );
        $hero_style = self::css_vars();
        if ( ! empty( $attributes['heroHeight'] ) ) {
            $hero_style .= '--asevj-hero-height:' . min( 720, max( 280, absint( $attributes['heroHeight'] ) ) ) . 'px;';
        }
        $hero_bg = self::block_color( $attributes, 'backgroundColor', $s['navy'] );
        $fade_color = self::block_color( $attributes, 'topFadeColor', $s['navy'] );
        $fade_height = isset( $attributes['topFadeHeight'] ) ? min( 260, max( 0, absint( $attributes['topFadeHeight'] ) ) ) : 110;
        $hero_style .= '--asevj-hero-bg:' . $hero_bg . ';--asevj-hero-top-fade:' . $fade_color . ';--asevj-hero-top-fade-height:' . $fade_height . 'px;';
        $hero_style .= '--asevj-hero-title-size:' . ( ! empty( $attributes['titleSize'] ) ? min( 110, max( 32, absint( $attributes['titleSize'] ) ) ) . 'px' : 'clamp(52px,5.2vw,82px)' ) . ';';
        $hero_style .= '--asevj-hero-kicker-size:' . ( ! empty( $attributes['kickerSize'] ) ? min( 32, max( 10, absint( $attributes['kickerSize'] ) ) ) . 'px' : 'clamp(14px,1.25vw,19px)' ) . ';';
        $hero_style .= '--asevj-hero-body-size:' . ( ! empty( $attributes['bodySize'] ) ? min( 28, max( 11, absint( $attributes['bodySize'] ) ) ) . 'px' : '15px' ) . ';';
        $hero_style .= '--asevj-hero-pad-x:' . ( ! empty( $attributes['contentPaddingX'] ) ? min( 160, max( 10, absint( $attributes['contentPaddingX'] ) ) ) . 'px' : 'clamp(32px,5.5vw,84px)' ) . ';';
        $hero_style .= '--asevj-hero-pad-y:' . ( ! empty( $attributes['contentPaddingY'] ) ? min( 140, max( 10, absint( $attributes['contentPaddingY'] ) ) ) . 'px' : '38px' ) . ';';
        $hero_style .= '--asevj-hero-feature-scale:' . min( 180, max( 60, absint( $attributes['featureScale'] ?? 100 ) ) ) . ';';
        $hero_style .= '--asevj-hero-jacket-scale:' . min( 145, max( 70, absint( $attributes['jacketScale'] ?? 100 ) ) ) . ';';
        $hero_style .= '--asevj-hero-visual-bg:' . self::block_color( $attributes, 'visualBackgroundColor', '#07111F' ) . ';';
        $hero_style .= '--asevj-hero-overlay:' . self::block_color( $attributes, 'jacketOverlayColor', '#0B162A' ) . ';';
        $hero_style .= '--asevj-hero-overlay-opacity:' . min( 80, max( 0, absint( $attributes['jacketOverlayOpacity'] ?? 18 ) ) ) . '%;';
        $hero_style .= '--asevj-hero-glow:' . self::block_color( $attributes, 'glowColor', '#27456F' ) . ';';
        $hero_style .= '--asevj-hero-glow-opacity:' . min( 85, max( 0, absint( $attributes['glowOpacity'] ?? 32 ) ) ) . '%;';
        $hero_style .= '--asevj-hero-glow-size:' . min( 100, max( 20, absint( $attributes['glowSize'] ?? 68 ) ) ) . '%;';
        $hero_style .= '--asevj-hero-content-x:' . min( 320, max( -320, intval( $attributes['contentOffsetX'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-content-y:' . min( 220, max( -220, intval( $attributes['contentOffsetY'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-feature-x:' . min( 220, max( -220, intval( $attributes['featureOffsetX'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-feature-y:' . min( 180, max( -180, intval( $attributes['featureOffsetY'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-jacket-x:' . min( 360, max( -360, intval( $attributes['jacketOffsetX'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-jacket-y:' . min( 260, max( -260, intval( $attributes['jacketOffsetY'] ?? 0 ) ) ) . 'px;';
        $hero_style .= '--asevj-hero-glow-x:' . min( 40, max( -40, intval( $attributes['glowOffsetX'] ?? 0 ) ) ) . '%;';
        $hero_style .= '--asevj-hero-glow-y:' . min( 40, max( -40, intval( $attributes['glowOffsetY'] ?? 0 ) ) ) . '%;';
        $show_features = ! array_key_exists( 'showFeatures', $attributes ) || ! empty( $attributes['showFeatures'] );
        ob_start();
        ?>
        <section class="asevj-hero<?php echo esc_attr( self::bleed_class( $attributes ) ); ?>" style="<?php echo esc_attr( $hero_style ); ?>">
            <div class="asevj-hero__content">
                <h2><?php echo esc_html( $title ); ?></h2>
                <div class="asevj-hero__kicker"><?php echo esc_html( $kicker ); ?></div>
                <p><?php echo esc_html( $body ); ?></p>
                <?php if ( $show_features ) : ?>
                <div class="asevj-hero__features">
                    <div><?php echo self::icon( 'quality' ); ?><p><b>Premium Quality</b><small>Top materials. Built to last.</small></p></div>
                    <div><?php echo self::icon( 'craft' ); ?><p><b>Expert Craftsmanship</b><small>In-house embroidery & customization.</small></p></div>
                    <div><?php echo self::icon( 'fit' ); ?><p><b>Perfect Fit</b><small>Fittings available. Custom sizing.</small></p></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="asevj-hero__visual<?php echo $image ? '' : ' is-placeholder'; ?>">
                <?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="Varsity jacket showcase"><?php else : ?><div class="asevj-signature-placeholder"><span>ADD SIGNATURE JACKET</span><small>Design Settings</small></div><?php endif; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_browser( array $attributes = [] ): string {
        $schools = self::get_schools_data();
        $s = self::settings();
        wp_enqueue_style( 'asevj-frontend' );
        wp_enqueue_script( 'asevj-frontend' );

        $editor_demo = false;
        if ( ! $schools && self::is_editor_preview() ) {
            $schools = self::demo_schools();
            $editor_demo = true;
        }

        if ( ! $schools ) {
            if ( current_user_can( 'edit_posts' ) ) {
                return '<div class="asevj-empty-front" style="' . esc_attr( self::css_vars() ) . '"><strong>Varsity Jackets is ready for content.</strong><span>Import your old gallery ZIP from <em>All Star Jackets → Import / Export</em>, then this block will populate automatically.</span></div>';
            }
            return '';
        }

        $instance = wp_unique_id( 'asevj-browser-' );
        $districts = array_values( array_unique( array_filter( array_column( $schools, 'district' ) ) ) );
        $mascots = array_values( array_unique( array_filter( array_column( $schools, 'mascot' ) ) ) );
        sort( $districts );
        sort( $mascots );
        $first = $schools[0];
        $show_prices = self::tri_state( $attributes, 'showPrices', ! empty( $s['show_prices'] ) );
        $show_search = self::tri_state( $attributes, 'showSearch', ! empty( $s['show_search'] ) );
        $show_filters = self::tri_state( $attributes, 'showFilters', ! empty( $s['show_filters'] ) );
        $browser_style = self::css_vars();
        if ( ! empty( $attributes['stylesVisible'] ) ) {
            $browser_style .= '--asevj-styles-visible:' . min( 4, max( 1, absint( $attributes['stylesVisible'] ) ) ) . ';';
        }
        $browser_bg = self::block_color( $attributes, 'backgroundColor', '#FFFFFF' );
        $browser_style .= '--asevj-browser-bg:' . $browser_bg . ';';

        ob_start();
        ?>
        <section class="asevj-browser<?php echo esc_attr( self::bleed_class( $attributes ) ); ?><?php echo ! empty( $s['card_shadow'] ) ? ' has-shadow' : ''; ?><?php echo $editor_demo ? ' is-editor-demo' : ''; ?>" id="<?php echo esc_attr( $instance ); ?>" style="<?php echo esc_attr( $browser_style ); ?>">
            <div class="asevj-browser__inner">
                <header class="asevj-browser__heading">
                    <h2><?php echo esc_html( $attributes['heading'] ?? 'BROWSE BY SCHOOL' ); ?></h2>
                    <p><?php echo esc_html( $attributes['subheading'] ?? 'Select your school to view available jacket styles and custom options.' ); ?></p>
                </header>

                <div class="asevj-browser__controls">
                    <?php if ( $show_search ) : ?><label class="asevj-search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Search schools..." data-asevj-search></label><?php endif; ?>
                    <?php if ( $show_filters ) : ?>
                        <select data-asevj-district><option value="">All Districts</option><?php foreach ( $districts as $district ) : ?><option value="<?php echo esc_attr( $district ); ?>"><?php echo esc_html( $district ); ?></option><?php endforeach; ?></select>
                        <select data-asevj-mascot><option value="">All Mascots</option><?php foreach ( $mascots as $mascot ) : ?><option value="<?php echo esc_attr( $mascot ); ?>"><?php echo esc_html( $mascot ); ?></option><?php endforeach; ?></select>
                        <button type="button" class="asevj-reset" data-asevj-reset>↻ <span>Reset</span></button>
                    <?php endif; ?>
                </div>

                <div class="asevj-school-strip-wrap">
                    <button class="asevj-strip-arrow asevj-strip-arrow--prev" type="button" data-asevj-prev aria-label="Previous schools"><span aria-hidden="true">‹</span></button>
                    <div class="asevj-school-strip-shell">
                        <div class="asevj-school-scroll-hint" aria-hidden="true"><span>‹</span><small>Slide to find your school</small><span>›</span></div>
                        <div class="asevj-school-strip" data-asevj-school-strip>
                        <?php foreach ( $schools as $i => $school ) : ?>
                            <button type="button" class="asevj-school-tile<?php echo 0 === $i ? ' is-active' : ''; ?>" style="--school-primary:<?php echo esc_attr( $school['primary'] ); ?>;--school-secondary:<?php echo esc_attr( $school['secondary'] ); ?>;" data-school-index="<?php echo esc_attr( $i ); ?>" data-name="<?php echo esc_attr( strtolower( $school['name'] ) ); ?>" data-district="<?php echo esc_attr( $school['district'] ); ?>" data-mascot="<?php echo esc_attr( $school['mascot'] ); ?>">
                                <?php echo self::render_logo_markup( $school ); ?>
                                <small><?php echo esc_html( $school['name'] ); ?></small>
                            </button>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="asevj-strip-arrow asevj-strip-arrow--next" type="button" data-asevj-next aria-label="Next schools"><span aria-hidden="true">›</span></button>
                </div>

                <div class="asevj-school-stage">
                    <aside class="asevj-school-summary" style="--school-primary:<?php echo esc_attr( $first['primary'] ); ?>;--school-secondary:<?php echo esc_attr( $first['secondary'] ); ?>;">
                        <div class="asevj-school-summary__title"><span data-asevj-active-logo><?php echo self::render_logo_markup( $first ); ?></span><h3 data-asevj-school-name><?php echo esc_html( $first['name'] ); ?></h3></div>
                        <div class="asevj-school-summary__meta"><span data-asevj-mascot-text><?php echo esc_html( $first['mascot'] ?: 'School' ); ?></span><span data-asevj-location><?php echo esc_html( $first['location'] ); ?></span></div>
                        <p data-asevj-description><?php echo esc_html( $first['description'] ?: 'Explore the available varsity jacket styles for this school.' ); ?></p>
                        <button type="button" class="asevj-school-gallery-btn" data-asevj-school-gallery>VIEW SCHOOL GALLERY <span>↗</span></button>
                    </aside>

                    <div class="asevj-style-area">
                        <div class="asevj-style-grid" data-asevj-style-grid>
                            <?php foreach ( $first['styles'] as $style_index => $style ) : echo self::render_style_card_markup( $first, $style, $show_prices, $style_index ); endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="asevj-no-results" data-asevj-no-results hidden>No schools match those filters.</div>
                <?php if ( $editor_demo ) : ?><div class="asevj-editor-demo-note">Editor preview — import your schools to replace this sample data.</div><?php endif; ?>
                <script type="application/json" class="asevj-data"><?php echo wp_json_encode( [ 'schools' => $schools, 'showPrices' => $show_prices ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_benefits( array $attributes = [] ): string {
        wp_enqueue_style( 'asevj-frontend' );
        $s = self::settings();
        $benefits_bg = self::block_color( $attributes, 'backgroundColor', $s['navy'] );
        $benefits_style = self::css_vars() . '--asevj-benefits-bg:' . $benefits_bg . ';';
        ob_start();
        ?>
        <section class="asevj-benefits<?php echo esc_attr( self::bleed_class( $attributes ) ); ?>" style="<?php echo esc_attr( $benefits_style ); ?>">
            <div><?php echo self::icon( 'scissors' ); ?><p><strong>IN-HOUSE CRAFTSMANSHIP</strong><small>All stitching, embroidery, and customization done in-house for faster turnaround.</small></p></div>
            <div><?php echo self::icon( 'measure' ); ?><p><strong>PERFECTLY YOURS</strong><small>Fittings available. We can customize sleeve length and jacket fit.</small></p></div>
            <div><?php echo self::icon( 'thread' ); ?><p><strong>PREMIUM MATERIALS</strong><small>24-ounce melton wool, genuine leather, quilted lining, and rib-knit trim.</small></p></div>
            <div><?php echo self::icon( 'truck' ); ?><p><strong>BUILT TO LAST</strong><small>Made with pride and attention to every detail.</small></p></div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_full( array $attributes = [] ): string {
        $s = self::settings();
        $full_bleed = ! array_key_exists( 'fullBleed', $attributes ) || ! empty( $attributes['fullBleed'] );
        $class = 'asevj-full' . ( $full_bleed ? ' asevj-full--bleed' : '' );
        $hero = $attributes;
        $browser = $attributes;
        $benefits = $attributes;
        $hero['fullBleed'] = false;
        $browser['fullBleed'] = false;
        $benefits['fullBleed'] = false;
        if ( ! empty( $attributes['heroBackgroundColor'] ) ) $hero['backgroundColor'] = $attributes['heroBackgroundColor'];
        if ( ! empty( $attributes['browserBackgroundColor'] ) ) $browser['backgroundColor'] = $attributes['browserBackgroundColor'];
        if ( ! empty( $attributes['benefitsBackgroundColor'] ) ) $benefits['backgroundColor'] = $attributes['benefitsBackgroundColor'];
        return '<div class="' . esc_attr( $class ) . '" style="' . esc_attr( self::css_vars() ) . '">' . self::render_hero( $hero ) . self::render_browser( $browser ) . self::render_benefits( $benefits ) . '</div>';
    }
}
