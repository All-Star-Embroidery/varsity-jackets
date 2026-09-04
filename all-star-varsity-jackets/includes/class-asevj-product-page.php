<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Product_Page {
    private static function is_editor_preview(): bool {
        if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
            return false;
        }
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        return str_contains( $uri, '/block-renderer/' );
    }

    private static function linked_style_id( int $product_id ): int {
        if ( ! $product_id ) {
            return 0;
        }

        $style_id = absint( get_post_meta( $product_id, '_asevj_style_id', true ) );
        if ( $style_id && 'asevj_style' === get_post_type( $style_id ) ) {
            return $style_id;
        }

        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_asevj_woo_product_id',
            'meta_value'     => $product_id,
        ] );

        return ! empty( $styles[0] ) ? absint( $styles[0] ) : 0;
    }

    private static function preview_product_id(): int {
        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 30,
            'fields'         => 'ids',
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
        ] );

        foreach ( $styles as $style_id ) {
            $product_id = absint( get_post_meta( $style_id, '_asevj_woo_product_id', true ) );
            if ( $product_id && 'product' === get_post_type( $product_id ) ) {
                return $product_id;
            }
        }
        return 0;
    }

    private static function product_id( array $attributes ): int {
        $preview_id = absint( $attributes['previewProductId'] ?? 0 );
        if ( $preview_id ) {
            return $preview_id;
        }

        if ( function_exists( 'is_product' ) && is_product() ) {
            return absint( get_queried_object_id() );
        }

        global $product;
        if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
            return absint( $product->get_id() );
        }

        return self::is_editor_preview() ? self::preview_product_id() : 0;
    }

    private static function color( array $attributes, string $key, string $fallback ): string {
        $value = isset( $attributes[ $key ] ) ? sanitize_hex_color( (string) $attributes[ $key ] ) : '';
        return $value ?: $fallback;
    }

    private static function gallery( int $style_id, $product ): array {
        $images = [];
        $seen = [];

        $add = static function ( int $attachment_id ) use ( &$images, &$seen ): void {
            if ( ! $attachment_id || isset( $seen[ $attachment_id ] ) ) {
                return;
            }
            $full = wp_get_attachment_image_url( $attachment_id, 'large' );
            if ( ! $full ) {
                return;
            }
            $thumb = wp_get_attachment_image_url( $attachment_id, 'medium' ) ?: $full;
            $alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            $images[] = [
                'id'    => $attachment_id,
                'full'  => $full,
                'thumb' => $thumb,
                'alt'   => $alt ?: get_the_title( $attachment_id ),
            ];
            $seen[ $attachment_id ] = true;
        };

        if ( $style_id ) {
            $add( get_post_thumbnail_id( $style_id ) );
            $gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $style_id, '_asevj_gallery_ids', true ) ) ) );
            foreach ( $gallery_ids as $gallery_id ) {
                $add( $gallery_id );
            }
        }

        if ( $product && is_a( $product, 'WC_Product' ) ) {
            $add( absint( $product->get_image_id() ) );
            foreach ( (array) $product->get_gallery_image_ids() as $gallery_id ) {
                $add( absint( $gallery_id ) );
            }
        }

        return $images;
    }

    private static function customizations( string $raw ): array {
        $items = [];
        foreach ( preg_split( '/\r\n|\r|\n/', $raw ) ?: [] as $line ) {
            $line = trim( $line );
            if ( '' === $line ) {
                continue;
            }
            $parts = array_map( 'trim', explode( '|', $line, 2 ) );
            $items[] = [
                'title' => $parts[0] ?? '',
                'text'  => $parts[1] ?? '',
            ];
        }
        return $items;
    }

    private static function features( int $style_id ): array {
        if ( ! $style_id ) {
            return [];
        }
        $raw = (string) get_post_meta( $style_id, '_asevj_features', true );
        return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ?: [] ) ) );
    }

    private static function price_html( int $style_id, $product ): string {
        if ( $product && is_a( $product, 'WC_Product' ) ) {
            $html = (string) $product->get_price_html();
            if ( '' !== trim( $html ) ) {
                return wp_kses_post( $html );
            }
        }

        if ( $style_id && class_exists( 'ASEVJ_Tools' ) ) {
            $price = ASEVJ_Tools::style_base_price( $style_id );
            if ( '' !== $price ) {
                return function_exists( 'wc_price' ) ? wp_kses_post( wc_price( $price ) ) : '$' . esc_html( number_format_i18n( (float) $price, 2 ) );
            }
        }
        return '';
    }

    private static function same_school_styles( int $school_id, int $current_style_id ): array {
        if ( ! $school_id ) {
            return [];
        }

        $style_ids = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => '_asevj_school_id',
            'meta_value'     => $school_id,
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
            'order'          => 'ASC',
        ] );

        $styles = [];
        foreach ( $style_ids as $candidate_id ) {
            $candidate_id = absint( $candidate_id );
            $product_id = absint( get_post_meta( $candidate_id, '_asevj_woo_product_id', true ) );
            if ( ! $product_id || 'product' !== get_post_type( $product_id ) || 'publish' !== get_post_status( $product_id ) ) {
                continue;
            }

            $url = get_permalink( $product_id );
            if ( ! $url ) {
                continue;
            }

            $styles[] = [
                'id'      => $candidate_id,
                'name'    => get_the_title( $candidate_id ),
                'url'     => $url,
                'current' => $candidate_id === $current_style_id,
            ];
        }

        return $styles;
    }

    public static function render( array $attributes = [] ): string {
        $product_id = self::product_id( $attributes );
        $style_id = self::linked_style_id( $product_id );
        $editor = self::is_editor_preview();

        if ( ! $style_id && ! $editor ) {
            return '';
        }

        $product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        $school_id = $style_id ? absint( get_post_meta( $style_id, '_asevj_school_id', true ) ) : 0;
        $school_name = $school_id ? get_the_title( $school_id ) : 'Your School';
        $style_name = $style_id ? get_the_title( $style_id ) : ( $product ? $product->get_name() : 'Varsity Jacket' );
        $mascot = $school_id ? trim( (string) get_post_meta( $school_id, '_asevj_mascot', true ) ) : '';
        $location = $school_id ? trim( (string) get_post_meta( $school_id, '_asevj_location', true ) ) : '';
        $logo_id = $school_id ? absint( get_post_meta( $school_id, '_asevj_logo_id', true ) ) : 0;
        $logo = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $description = $style_id ? trim( (string) get_post_meta( $style_id, '_asevj_description', true ) ) : '';
        if ( '' === $description && $product ) {
            $description = wp_strip_all_tags( $product->get_short_description() );
        }
        $features = self::features( $style_id );
        $images = self::gallery( $style_id, $product );
        $price_html = self::price_html( $style_id, $product );
        $same_school_styles = self::same_school_styles( $school_id, $style_id );

        $settings = ASEVJ_Render::settings();
        $bg = self::color( $attributes, 'backgroundColor', '#F6F3EA' );
        $surface = self::color( $attributes, 'surfaceColor', '#FFFFFF' );
        $image_bg = self::color( $attributes, 'imageBackgroundColor', '#F1F2F4' );
        $navy = self::color( $attributes, 'navyColor', $settings['navy'] ?? '#101B31' );
        $accent = self::color( $attributes, 'accentColor', $settings['accent'] ?? '#F2B619' );
        $text = self::color( $attributes, 'textColor', '#1F2937' );
        $muted = self::color( $attributes, 'mutedColor', '#687385' );
        $price_bg = self::color( $attributes, 'priceBackgroundColor', '#FFF8DF' );

        $max_width = min( 1680, max( 900, absint( $attributes['maxWidth'] ?? 1380 ) ) );
        $padding = min( 110, max( 18, absint( $attributes['sectionPadding'] ?? 42 ) ) );
        $info_padding = min( 80, max( 18, absint( $attributes['infoPadding'] ?? 38 ) ) );
        $gap = min( 72, max( 12, absint( $attributes['columnGap'] ?? 34 ) ) );
        $radius = min( 4, max( 0, absint( $attributes['borderRadius'] ?? 4 ) ) );
        $gallery_height = min( 760, max( 300, absint( $attributes['galleryHeight'] ?? 560 ) ) );
        $mobile_gallery_height = min( 560, max( 220, absint( $attributes['mobileGalleryHeight'] ?? 360 ) ) );
        $image_scale = min( 100, max( 70, absint( $attributes['imageScale'] ?? 100 ) ) );

        $eyebrow = sanitize_text_field( (string) ( $attributes['eyebrow'] ?? 'CUSTOM VARSITY JACKET' ) );
        $price_label = sanitize_text_field( (string) ( $attributes['priceLabel'] ?? 'STARTING AT' ) );
        $style_switcher_label = sanitize_text_field( (string) ( $attributes['styleSwitcherLabel'] ?? 'JACKET STYLES' ) );
        $price_note = sanitize_textarea_field( (string) ( $attributes['priceNote'] ?? 'Base jacket price. Lettering, patches, embroidery, names, numerals, and other customizations are additional.' ) );
        $customizations_heading = sanitize_text_field( (string) ( $attributes['customizationsHeading'] ?? 'Available Customizations' ) );
        $customizations_subheading = sanitize_text_field( (string) ( $attributes['customizationsSubheading'] ?? 'Build the jacket around your school, achievements, and style.' ) );
        $customizations = self::customizations( (string) ( $attributes['customizations'] ?? "Chenille Letters|Classic 3D chenille letters in school colors.\nTackle Twill|One-color or multi-color twill designs.\nEmbroidery|Names, mascots, and custom embroidery.\nPatches|School, achievement, and custom patches.\nNames & Numerals|Graduation year, jersey numbers, and personalization." ) );
        $order_heading = sanitize_text_field( (string) ( $attributes['orderHeading'] ?? 'Ready to order your jacket?' ) );
        $order_text = sanitize_textarea_field( (string) ( $attributes['orderText'] ?? 'Every varsity jacket is custom-built. Call the store to finalize sizing, lettering, patches, and your complete price.' ) );
        $button_label = sanitize_text_field( (string) ( $attributes['buttonLabel'] ?? 'Call the Store to Order' ) );
        $phone = preg_replace( '/[^0-9+().\-\s]/', '', (string) ( $attributes['phoneNumber'] ?? '' ) );
        $contact_url = esc_url_raw( (string) ( $attributes['contactUrl'] ?? '' ) );
        $cta_url = '' !== trim( $phone ) ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) : $contact_url;

        $style = sprintf(
            '--asevj-vjpp-bg:%1$s;--asevj-vjpp-surface:%2$s;--asevj-vjpp-image-bg:%3$s;--asevj-vjpp-navy:%4$s;--asevj-vjpp-accent:%5$s;--asevj-vjpp-text:%6$s;--asevj-vjpp-muted:%7$s;--asevj-vjpp-price-bg:%8$s;--asevj-vjpp-max:%9$dpx;--asevj-vjpp-pad:%10$dpx;--asevj-vjpp-info-pad:%11$dpx;--asevj-vjpp-gap:%12$dpx;--asevj-vjpp-radius:%13$dpx;--asevj-vjpp-gallery-height:%14$dpx;--asevj-vjpp-mobile-gallery-height:%15$dpx;--asevj-vjpp-image-scale:%16$d;--asevj-heading-font:%17$s;--asevj-body-font:%18$s;',
            esc_attr( $bg ), esc_attr( $surface ), esc_attr( $image_bg ), esc_attr( $navy ), esc_attr( $accent ), esc_attr( $text ), esc_attr( $muted ), esc_attr( $price_bg ),
            $max_width, $padding, $info_padding, $gap, $radius, $gallery_height, $mobile_gallery_height, $image_scale,
            esc_attr( $settings['heading_font'] ?? 'inherit' ), esc_attr( $settings['body_font'] ?? 'inherit' )
        );

        $show_description = ! array_key_exists( 'showDescription', $attributes ) || ! empty( $attributes['showDescription'] );
        $show_features = ! array_key_exists( 'showFeatures', $attributes ) || ! empty( $attributes['showFeatures'] );
        $show_customizations = ! array_key_exists( 'showCustomizations', $attributes ) || ! empty( $attributes['showCustomizations'] );
        $show_process = ! array_key_exists( 'showProcess', $attributes ) || ! empty( $attributes['showProcess'] );
        $show_school_meta = ! array_key_exists( 'showSchoolMeta', $attributes ) || ! empty( $attributes['showSchoolMeta'] );
        $show_style_switcher = ! array_key_exists( 'showStyleSwitcher', $attributes ) || ! empty( $attributes['showStyleSwitcher'] );

        ob_start();
        ?>
        <section class="asevj-vjpp<?php echo ! empty( $attributes['fullBleed'] ) ? ' asevj-vjpp--bleed' : ''; ?>" style="<?php echo esc_attr( $style ); ?>">
            <div class="asevj-vjpp__inner">
                <div class="asevj-vjpp__main">
                    <div class="asevj-vjpp__gallery" data-asevj-vjpp-gallery>
                        <div class="asevj-vjpp__main-image">
                            <?php if ( ! empty( $images[0]['full'] ) ) : ?>
                                <img data-asevj-vjpp-main src="<?php echo esc_url( $images[0]['full'] ); ?>" alt="<?php echo esc_attr( $images[0]['alt'] ?: $style_name ); ?>">
                            <?php else : ?>
                                <div class="asevj-vjpp__placeholder"><span>JACKET IMAGE</span><small>Add jacket photography to the linked style.</small></div>
                            <?php endif; ?>
                        </div>
                        <?php if ( count( $images ) > 1 ) : ?>
                            <div class="asevj-vjpp__thumbs" aria-label="Jacket gallery">
                                <?php foreach ( $images as $index => $image ) : ?>
                                    <button type="button" class="asevj-vjpp__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-asevj-vjpp-thumb data-full="<?php echo esc_url( $image['full'] ); ?>" data-alt="<?php echo esc_attr( $image['alt'] ?: $style_name ); ?>" aria-label="Show jacket image <?php echo esc_attr( $index + 1 ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                                        <img src="<?php echo esc_url( $image['thumb'] ); ?>" alt="">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="asevj-vjpp__info">
                        <div class="asevj-vjpp__eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
                        <div class="asevj-vjpp__school">
                            <?php if ( $logo ) : ?><span class="asevj-vjpp__school-logo"><img src="<?php echo esc_url( $logo ); ?>" alt=""></span><?php endif; ?>
                            <div><h1><?php echo esc_html( $school_name ); ?></h1><p class="asevj-vjpp__style-name"><?php echo esc_html( $style_name ); ?></p></div>
                        </div>
                        <?php if ( $show_school_meta && ( $mascot || $location ) ) : ?>
                            <div class="asevj-vjpp__school-meta"><?php if ( $mascot ) : ?><span><?php echo esc_html( $mascot ); ?></span><?php endif; ?><?php if ( $location ) : ?><span><?php echo esc_html( $location ); ?></span><?php endif; ?></div>
                        <?php endif; ?>

                        <?php if ( $show_style_switcher && count( $same_school_styles ) > 1 ) : ?>
                            <nav class="asevj-vjpp__style-switcher" aria-label="Other jacket styles for <?php echo esc_attr( $school_name ); ?>">
                                <span class="asevj-vjpp__style-switcher-label"><?php echo esc_html( $style_switcher_label ); ?></span>
                                <div class="asevj-vjpp__style-switcher-links">
                                    <?php foreach ( $same_school_styles as $school_style ) : ?>
                                        <?php if ( ! empty( $school_style['current'] ) ) : ?>
                                            <span class="asevj-vjpp__style-choice is-active" aria-current="page"><?php echo esc_html( $school_style['name'] ); ?></span>
                                        <?php else : ?>
                                            <a class="asevj-vjpp__style-choice" href="<?php echo esc_url( $school_style['url'] ); ?>"><?php echo esc_html( $school_style['name'] ); ?></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </nav>
                        <?php endif; ?>

                        <?php if ( $price_html ) : ?>
                            <div class="asevj-vjpp__price">
                                <small><?php echo esc_html( $price_label ); ?></small>
                                <strong><?php echo wp_kses_post( $price_html ); ?></strong>
                                <p><?php echo esc_html( $price_note ); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_description && $description ) : ?><p class="asevj-vjpp__description"><?php echo esc_html( $description ); ?></p><?php endif; ?>

                        <?php if ( $show_features && $features ) : ?>
                            <div class="asevj-vjpp__features">
                                <?php foreach ( $features as $feature ) : ?><div><span>✓</span><?php echo esc_html( $feature ); ?></div><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="asevj-vjpp__order">
                            <small>MADE TO ORDER</small>
                            <h2><?php echo esc_html( $order_heading ); ?></h2>
                            <p><?php echo esc_html( $order_text ); ?></p>
                            <?php if ( $cta_url ) : ?><a href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $button_label ); ?><span>→</span></a><?php elseif ( $editor ) : ?><span class="asevj-vjpp__editor-note">Add the store phone number or contact URL in block settings to activate this button.</span><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ( $show_customizations && $customizations ) : ?>
                    <div class="asevj-vjpp__customizations">
                        <header><span>CUSTOM BUILT</span><h2><?php echo esc_html( $customizations_heading ); ?></h2><p><?php echo esc_html( $customizations_subheading ); ?></p></header>
                        <div class="asevj-vjpp__customization-grid">
                            <?php foreach ( $customizations as $index => $item ) : ?>
                                <article><b><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></b><h3><?php echo esc_html( $item['title'] ); ?></h3><?php if ( $item['text'] ) : ?><p><?php echo esc_html( $item['text'] ); ?></p><?php endif; ?><small>ADDITIONAL</small></article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_process ) : ?>
                    <div class="asevj-vjpp__process">
                        <div><b>01</b><p><strong>Choose your jacket</strong><span>Select the school and jacket style you want.</span></p></div>
                        <div><b>02</b><p><strong>Choose customizations</strong><span>Lettering, patches, embroidery, names, and more.</span></p></div>
                        <div><b>03</b><p><strong>Call to finalize</strong><span>We’ll confirm details and your complete price.</span></p></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
