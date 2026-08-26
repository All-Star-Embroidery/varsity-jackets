<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Tools {
    private static ?ASEVJ_Tools $instance = null;

    public static function instance(): ASEVJ_Tools {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_post_asevj_export_data', [ $this, 'export_data' ] );
        add_action( 'admin_post_asevj_import_data', [ $this, 'import_data' ] );
        add_action( 'admin_post_asevj_create_woo_product', [ $this, 'create_woo_product' ] );
        add_action( 'admin_post_asevj_sync_woo_product', [ $this, 'sync_woo_product' ] );
        add_action( 'admin_post_asevj_save_woo_settings', [ $this, 'save_woo_settings' ] );
        add_action( 'admin_post_asevj_bulk_create_woo_products', [ $this, 'bulk_create_woo_products' ] );
        add_action( 'admin_post_asevj_bulk_sync_woo_products', [ $this, 'bulk_sync_woo_products' ] );
        add_action( 'admin_post_asevj_bulk_create_sync_woo_products', [ $this, 'bulk_create_sync_woo_products' ] );
        add_action( 'admin_post_asevj_duplicate_style', [ $this, 'duplicate_style' ] );
        add_action( 'admin_post_asevj_duplicate_school', [ $this, 'duplicate_school' ] );
        add_action( 'admin_post_asevj_quick_add_style', [ $this, 'quick_add_style' ] );
        add_action( 'admin_post_asevj_check_updates', [ $this, 'check_updates' ] );
        add_action( 'admin_post_asevj_create_page', [ $this, 'create_page' ] );
        add_action( 'wp_ajax_asevj_save_organizer', [ $this, 'save_organizer' ] );
    }

    private static function admin_redirect( string $page, array $args = [] ): void {
        $url = add_query_arg( array_merge( [ 'page' => $page ], $args ), admin_url( 'admin.php' ) );
        wp_safe_redirect( $url );
        exit;
    }

    public static function notice(): void {
        $notice = get_transient( 'asevj_tools_notice_' . get_current_user_id() );
        if ( ! $notice ) {
            return;
        }
        delete_transient( 'asevj_tools_notice_' . get_current_user_id() );
        $type = ! empty( $notice['ok'] ) ? 'is-good' : '';
        echo '<div class="asevj-status-card ' . esc_attr( $type ) . '"><strong>' . esc_html( $notice['title'] ?? '' ) . '</strong><span>' . esc_html( $notice['message'] ?? '' ) . '</span></div>';
    }

    private static function set_notice( bool $ok, string $title, string $message ): void {
        set_transient( 'asevj_tools_notice_' . get_current_user_id(), compact( 'ok', 'title', 'message' ), 120 );
    }

    public static function render_organizer(): void {
        self::notice();
        $schools = get_posts( [
            'post_type'      => 'asevj_school',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        if ( ! $schools ) {
            echo '<section class="asevj-admin-card"><h2>No schools yet</h2><p>Import the old gallery ZIP first, or add a school manually.</p></section>';
            return;
        }

        $school_id = isset( $_GET['school_id'] ) ? absint( $_GET['school_id'] ) : (int) $schools[0]->ID;
        if ( ! get_post( $school_id ) || 'asevj_school' !== get_post_type( $school_id ) ) {
            $school_id = (int) $schools[0]->ID;
        }
        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => -1,
            'meta_key'       => '_asevj_school_id',
            'meta_value'     => $school_id,
            'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
        ] );

        echo '<section class="asevj-admin-card asevj-organizer-intro">';
        echo '<div><h2>Visual Style Organizer</h2><p>Drag jacket photos between style columns. The <strong>first photo in each column becomes that style’s main card image</strong>; the rest become its gallery. This is the fastest way to split imported legacy photos into Style 1, Style 2, Style 3, and beyond.</p></div>';
        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="asevj-organizer"><label><strong>School</strong><select name="school_id" onchange="this.form.submit()">';
        foreach ( $schools as $school ) {
            echo '<option value="' . esc_attr( $school->ID ) . '" ' . selected( $school_id, $school->ID, false ) . '>' . esc_html( get_the_title( $school ) ) . '</option>';
        }
        echo '</select></label></form></section>';

        echo '<div class="asevj-organizer-toolbar"><strong>' . esc_html( get_the_title( $school_id ) ) . '</strong><span>Drag images, then click Save Layout.</span><button type="button" class="button button-primary" data-asevj-save-organizer>Save Layout</button></div>';
        echo '<div class="asevj-organizer" data-school="' . esc_attr( $school_id ) . '">';

        foreach ( $styles as $index => $style ) {
            $ids = [];
            $thumb = get_post_thumbnail_id( $style->ID );
            if ( $thumb ) {
                $ids[] = $thumb;
            }
            $gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $style->ID, '_asevj_gallery_ids', true ) ) ) );
            foreach ( $gallery as $id ) {
                if ( ! in_array( $id, $ids, true ) ) {
                    $ids[] = $id;
                }
            }
            echo '<section class="asevj-organizer-column" data-style="' . esc_attr( $style->ID ) . '">';
            echo '<header><div><small>STYLE ' . esc_html( $index + 1 ) . '</small><strong>' . esc_html( get_the_title( $style ) ) . '</strong></div><a href="' . esc_url( get_edit_post_link( $style->ID ) ) . '">Edit</a></header>';
            echo '<div class="asevj-organizer-images" data-asevj-sortable-images>';
            foreach ( $ids as $position => $id ) {
                $url = wp_get_attachment_image_url( $id, 'medium' );
                if ( ! $url ) {
                    continue;
                }
                $caption = get_post_field( 'post_excerpt', $id ) ?: get_the_title( $id );
                echo '<div class="asevj-organizer-image" data-attachment="' . esc_attr( $id ) . '"><span class="dashicons dashicons-menu"></span><img src="' . esc_url( $url ) . '" alt=""><small>' . ( 0 === $position ? 'MAIN IMAGE' : 'Gallery' ) . '</small><p>' . esc_html( wp_trim_words( (string) $caption, 8 ) ) . '</p></div>';
            }
            echo '</div></section>';
        }
        echo '<section class="asevj-organizer-column is-add"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_quick_add_style' );
        echo '<input type="hidden" name="action" value="asevj_quick_add_style"><input type="hidden" name="school_id" value="' . esc_attr( $school_id ) . '"><span class="dashicons dashicons-plus-alt2"></span><strong>Add another style</strong><p>Create a new empty style column, then drag photos into it.</p><button class="button">+ Add Style</button></form></section>';
        echo '</div><p class="description asevj-organizer-status">Nothing is changed until you click <strong>Save Layout</strong>.</p>';
    }

    public function save_organizer(): void {
        check_ajax_referer( 'asevj_admin', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }
        $school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
        $raw = isset( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : '';
        $layout = json_decode( (string) $raw, true );
        if ( ! $school_id || ! is_array( $layout ) ) {
            wp_send_json_error( [ 'message' => 'Invalid organizer layout.' ], 400 );
        }
        foreach ( $layout as $style_id_raw => $ids_raw ) {
            $style_id = absint( $style_id_raw );
            if ( 'asevj_style' !== get_post_type( $style_id ) || $school_id !== absint( get_post_meta( $style_id, '_asevj_school_id', true ) ) ) {
                continue;
            }
            $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids_raw ) ) ) );
            if ( $ids ) {
                set_post_thumbnail( $style_id, $ids[0] );
                update_post_meta( $style_id, '_asevj_gallery_ids', implode( ',', array_slice( $ids, 1 ) ) );
            } else {
                delete_post_thumbnail( $style_id );
                update_post_meta( $style_id, '_asevj_gallery_ids', '' );
            }
        }
        wp_send_json_success( [ 'message' => 'Style image layout saved.' ] );
    }

    public function quick_add_style(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_quick_add_style' );
        $school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
        if ( 'asevj_school' !== get_post_type( $school_id ) ) {
            wp_die( 'Invalid school.' );
        }
        $count = (int) ( new WP_Query( [ 'post_type' => 'asevj_style', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => 1, 'meta_key' => '_asevj_school_id', 'meta_value' => $school_id ] ) )->found_posts;
        $style_id = wp_insert_post( [
            'post_type'   => 'asevj_style',
            'post_status' => 'publish',
            'post_title'  => 'Jacket Style ' . ( $count + 1 ),
            'menu_order'  => $count,
        ] );
        if ( $style_id && ! is_wp_error( $style_id ) ) {
            update_post_meta( $style_id, '_asevj_school_id', $school_id );
            update_post_meta( $style_id, '_asevj_enabled', 1 );
            update_post_meta( $style_id, '_asevj_cta', 'Customize This Jacket' );
        }
        self::admin_redirect( 'asevj-organizer', [ 'school_id' => $school_id ] );
    }

    public function export_data(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_export_data' );
        $payload = [
            'schema'   => 1,
            'version'  => ASEVJ_VERSION,
            'exported' => gmdate( 'c' ),
            'settings' => get_option( 'asevj_design_settings', [] ),
            'schools'  => [],
        ];
        $schools = get_posts( [ 'post_type' => 'asevj_school', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ] );
        foreach ( $schools as $school ) {
            $school_row = [
                'title' => $school->post_title,
                'slug'  => $school->post_name,
                'status'=> $school->post_status,
                'order' => (int) $school->menu_order,
                'meta'  => [],
                'styles'=> [],
            ];
            foreach ( [ '_asevj_enabled','_asevj_mascot','_asevj_location','_asevj_district','_asevj_description','_asevj_logo_id','_asevj_primary','_asevj_secondary','_asevj_accent' ] as $key ) {
                $school_row['meta'][ $key ] = get_post_meta( $school->ID, $key, true );
            }
            $styles = get_posts( [ 'post_type' => 'asevj_style', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'meta_key' => '_asevj_school_id', 'meta_value' => $school->ID, 'orderby' => [ 'menu_order' => 'ASC', 'date' => 'ASC' ] ] );
            foreach ( $styles as $style ) {
                $row = [ 'title' => $style->post_title, 'status' => $style->post_status, 'order' => (int) $style->menu_order, 'thumbnail_id' => get_post_thumbnail_id( $style->ID ), 'meta' => [] ];
                foreach ( [ '_asevj_enabled','_asevj_subtitle','_asevj_description','_asevj_fallback_price','_asevj_cta','_asevj_gallery_ids','_asevj_features','_asevj_woo_product_id' ] as $key ) {
                    $row['meta'][ $key ] = get_post_meta( $style->ID, $key, true );
                }
                $school_row['styles'][] = $row;
            }
            $payload['schools'][] = $school_row;
        }
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="all-star-varsity-jackets-backup-' . gmdate( 'Y-m-d-His' ) . '.json"' );
        echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }

    public function import_data(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_import_data' );
        if ( empty( $_FILES['asevj_json']['tmp_name'] ) ) {
            self::set_notice( false, 'Import failed', 'Choose a JSON backup file.' );
            self::admin_redirect( 'asevj-import' );
        }
        $raw = file_get_contents( $_FILES['asevj_json']['tmp_name'] );
        $data = json_decode( (string) $raw, true );
        if ( ! is_array( $data ) || empty( $data['schools'] ) ) {
            self::set_notice( false, 'Import failed', 'That file is not a valid All Star Varsity Jackets backup.' );
            self::admin_redirect( 'asevj-import' );
        }
        if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
            update_option( 'asevj_design_settings', ASEVJ_Admin::instance()->sanitize_design_settings( $data['settings'] ) );
        }
        $created = 0;
        foreach ( (array) $data['schools'] as $school_row ) {
            $title = sanitize_text_field( (string) ( $school_row['title'] ?? '' ) );
            if ( '' === $title ) {
                continue;
            }
            $existing = get_page_by_title( $title, OBJECT, 'asevj_school' );
            $school_id = $existing ? $existing->ID : wp_insert_post( [ 'post_type' => 'asevj_school', 'post_status' => 'publish', 'post_title' => $title ] );
            if ( ! $school_id || is_wp_error( $school_id ) ) {
                continue;
            }
            if ( ! $existing ) {
                $created++;
            }
            foreach ( (array) ( $school_row['meta'] ?? [] ) as $key => $value ) {
                if ( str_starts_with( (string) $key, '_asevj_' ) ) {
                    update_post_meta( $school_id, sanitize_key( $key ), is_scalar( $value ) ? $value : '' );
                }
            }
            foreach ( (array) ( $school_row['styles'] ?? [] ) as $style_row ) {
                $style_title = sanitize_text_field( (string) ( $style_row['title'] ?? 'Jacket Style' ) );
                $style_id = wp_insert_post( [ 'post_type' => 'asevj_style', 'post_status' => 'publish', 'post_title' => $style_title, 'menu_order' => absint( $style_row['order'] ?? 0 ) ] );
                if ( ! $style_id || is_wp_error( $style_id ) ) {
                    continue;
                }
                update_post_meta( $style_id, '_asevj_school_id', $school_id );
                foreach ( (array) ( $style_row['meta'] ?? [] ) as $key => $value ) {
                    if ( str_starts_with( (string) $key, '_asevj_' ) && '_asevj_school_id' !== $key ) {
                        update_post_meta( $style_id, sanitize_key( $key ), is_scalar( $value ) ? $value : '' );
                    }
                }
                $thumb = absint( $style_row['thumbnail_id'] ?? 0 );
                if ( $thumb && get_post( $thumb ) ) {
                    set_post_thumbnail( $style_id, $thumb );
                }
            }
        }
        self::set_notice( true, 'Backup imported', $created . ' new school(s) were created. Existing school names were reused.' );
        self::admin_redirect( 'asevj-import' );
    }


    public static function woo_settings(): array {
        $saved = get_option( 'asevj_woo_settings', [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }

        return wp_parse_args(
            $saved,
            [
                'default_base_price' => '400',
                'new_product_status' => 'draft',
                'product_category'   => 'Varsity Jackets',
                'sku_prefix'         => 'ASE-VJ',
            ]
        );
    }

    public static function style_base_price( int $style_id ): string {
        $override = trim( (string) get_post_meta( $style_id, '_asevj_fallback_price', true ) );
        if ( '' !== $override ) {
            return function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $override ) : $override;
        }

        $settings = self::woo_settings();
        $price = (string) ( $settings['default_base_price'] ?? '400' );
        return function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $price ) : $price;
    }

    private static function ensure_product_category( string $name ): int {
        $name = trim( $name );
        if ( '' === $name || ! taxonomy_exists( 'product_cat' ) ) {
            return 0;
        }

        $existing = term_exists( $name, 'product_cat' );
        if ( is_array( $existing ) && ! empty( $existing['term_id'] ) ) {
            return absint( $existing['term_id'] );
        }
        if ( is_int( $existing ) ) {
            return $existing;
        }

        $created = wp_insert_term( $name, 'product_cat' );
        return is_wp_error( $created ) ? 0 : absint( $created['term_id'] ?? 0 );
    }

    private static function generated_product_name( int $style_id ): string {
        $school_id = absint( get_post_meta( $style_id, '_asevj_school_id', true ) );
        $school = trim( (string) get_the_title( $school_id ) );
        $style = trim( (string) get_the_title( $style_id ) );

        if ( '' === $school ) {
            return $style ?: 'Varsity Jacket';
        }
        if ( '' === $style ) {
            return $school . ' Varsity Jacket';
        }

        return $school . ' – ' . $style;
    }

    private static function generated_product_sku( int $style_id ): string {
        $settings = self::woo_settings();
        $prefix = strtoupper( preg_replace( '/[^A-Za-z0-9-]+/', '-', (string) ( $settings['sku_prefix'] ?? 'ASE-VJ' ) ) );
        $prefix = trim( $prefix, '-' ) ?: 'ASE-VJ';

        $school_id = absint( get_post_meta( $style_id, '_asevj_school_id', true ) );
        $school_slug = sanitize_title( (string) get_the_title( $school_id ) );
        $style_slug = sanitize_title( (string) get_the_title( $style_id ) );

        $body = strtoupper( trim( $school_slug . '-' . $style_slug, '-' ) );
        $body = preg_replace( '/[^A-Z0-9-]+/', '-', $body );
        $body = substr( $body, 0, 48 );

        $sku = trim( $prefix . '-' . $body, '-' );
        if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
            $existing = absint( wc_get_product_id_by_sku( $sku ) );
            $linked = absint( get_post_meta( $style_id, '_asevj_woo_product_id', true ) );
            if ( $existing && $existing !== $linked ) {
                $sku .= '-' . $style_id;
            }
        }

        return $sku;
    }

    private static function generated_long_description( int $style_id ): string {
        $description = trim( (string) get_post_meta( $style_id, '_asevj_description', true ) );
        $features_raw = (string) get_post_meta( $style_id, '_asevj_features', true );
        $features = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $features_raw ) ?: [] ) ) );

        $parts = [];
        if ( '' !== $description ) {
            $parts[] = '<p>' . esc_html( $description ) . '</p>';
        }

        if ( $features ) {
            $items = '';
            foreach ( $features as $feature ) {
                $items .= '<li>' . esc_html( $feature ) . '</li>';
            }
            $parts[] = '<h3>Jacket Details</h3><ul>' . $items . '</ul>';
        }

        return implode( "\n", $parts );
    }

    private static function apply_style_to_product( $product, int $style_id, bool $creating = false ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return new WP_Error( 'asevj_invalid_product', 'WooCommerce could not create or load the product.' );
        }

        $settings = self::woo_settings();
        $school_id = absint( get_post_meta( $style_id, '_asevj_school_id', true ) );

        $product->set_name( self::generated_product_name( $style_id ) );

        if ( $creating ) {
            $status = in_array( (string) ( $settings['new_product_status'] ?? 'draft' ), [ 'draft', 'publish' ], true )
                ? (string) $settings['new_product_status']
                : 'draft';
            $product->set_status( $status );
            $product->set_catalog_visibility( 'visible' );
        }

        $price = self::style_base_price( $style_id );
        if ( '' !== $price && $product instanceof WC_Product_Simple ) {
            $product->set_regular_price( $price );
        }

        $description = trim( (string) get_post_meta( $style_id, '_asevj_description', true ) );
        $product->set_short_description( $description );
        $product->set_description( self::generated_long_description( $style_id ) );

        $thumb = get_post_thumbnail_id( $style_id );
        if ( $thumb ) {
            $product->set_image_id( $thumb );
        }

        $gallery = array_filter(
            array_map(
                'absint',
                explode( ',', (string) get_post_meta( $style_id, '_asevj_gallery_ids', true ) )
            )
        );
        $product->set_gallery_image_ids( array_values( $gallery ) );

        $category_id = self::ensure_product_category( (string) ( $settings['product_category'] ?? 'Varsity Jackets' ) );
        if ( $category_id ) {
            $current = array_map( 'absint', (array) $product->get_category_ids() );
            if ( ! in_array( $category_id, $current, true ) ) {
                $current[] = $category_id;
            }
            $product->set_category_ids( array_values( array_unique( $current ) ) );
        }

        if ( '' === (string) $product->get_sku() ) {
            try {
                $product->set_sku( self::generated_product_sku( $style_id ) );
            } catch ( Exception $e ) {
                // A conflicting SKU should not prevent the product itself from syncing.
            }
        }

        $product_id = $product->save();
        if ( ! $product_id ) {
            return new WP_Error( 'asevj_product_save', 'WooCommerce could not save the product.' );
        }

        update_post_meta( $product_id, '_asevj_style_id', $style_id );
        update_post_meta( $product_id, '_asevj_school_id', $school_id );
        update_post_meta( $style_id, '_asevj_woo_product_id', $product_id );

        return $product;
    }

    private static function create_or_sync_product( int $style_id, bool $create_missing = true ) {
        if ( 'asevj_style' !== get_post_type( $style_id ) ) {
            return new WP_Error( 'asevj_invalid_style', 'Invalid varsity jacket style.' );
        }

        $existing = self::style_product( $style_id );
        if ( $existing ) {
            return self::apply_style_to_product( $existing, $style_id, false );
        }

        if ( ! $create_missing ) {
            return new WP_Error( 'asevj_missing_product', 'No WooCommerce product is linked.' );
        }

        $product = new WC_Product_Simple();
        return self::apply_style_to_product( $product, $style_id, true );
    }

    private static function all_styles(): array {
        return get_posts(
            [
                'post_type'      => 'asevj_style',
                'post_status'    => [ 'publish', 'draft', 'private' ],
                'posts_per_page' => -1,
                'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
                'order'          => 'ASC',
            ]
        );
    }

    public function save_woo_settings(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_save_woo_settings' );

        $raw_price = isset( $_POST['default_base_price'] ) ? (string) wp_unslash( $_POST['default_base_price'] ) : '400';
        $price = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $raw_price ) : preg_replace( '/[^0-9.]/', '', $raw_price );
        if ( '' === $price ) {
            $price = '400';
        }

        $status = isset( $_POST['new_product_status'] ) ? sanitize_key( wp_unslash( $_POST['new_product_status'] ) ) : 'draft';
        if ( ! in_array( $status, [ 'draft', 'publish' ], true ) ) {
            $status = 'draft';
        }

        $settings = [
            'default_base_price' => $price,
            'new_product_status' => $status,
            'product_category'   => isset( $_POST['product_category'] ) ? sanitize_text_field( wp_unslash( $_POST['product_category'] ) ) : 'Varsity Jackets',
            'sku_prefix'         => isset( $_POST['sku_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['sku_prefix'] ) ) : 'ASE-VJ',
        ];

        update_option( 'asevj_woo_settings', $settings );
        self::set_notice( true, 'WooCommerce defaults saved', 'The global varsity jacket product defaults were updated.' );
        self::admin_redirect( 'asevj-woocommerce' );
    }

    private static function run_bulk_woo( string $mode ): array {
        $created = 0;
        $synced = 0;
        $skipped = 0;
        $failed = 0;

        foreach ( self::all_styles() as $style ) {
            $has_product = (bool) self::style_product( (int) $style->ID );

            if ( 'create' === $mode && $has_product ) {
                $skipped++;
                continue;
            }
            if ( 'sync' === $mode && ! $has_product ) {
                $skipped++;
                continue;
            }

            $result = self::create_or_sync_product( (int) $style->ID, 'sync' !== $mode );
            if ( is_wp_error( $result ) ) {
                $failed++;
                continue;
            }

            if ( $has_product ) {
                $synced++;
            } else {
                $created++;
            }
        }

        return compact( 'created', 'synced', 'skipped', 'failed' );
    }

    private static function bulk_result_message( array $result ): string {
        return sprintf(
            '%d created, %d synced, %d skipped, %d failed.',
            absint( $result['created'] ?? 0 ),
            absint( $result['synced'] ?? 0 ),
            absint( $result['skipped'] ?? 0 ),
            absint( $result['failed'] ?? 0 )
        );
    }

    public function bulk_create_woo_products(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }
        check_admin_referer( 'asevj_bulk_create_woo_products' );
        $result = self::run_bulk_woo( 'create' );
        self::set_notice( 0 === $result['failed'], 'Missing products processed', self::bulk_result_message( $result ) );
        self::admin_redirect( 'asevj-woocommerce' );
    }

    public function bulk_sync_woo_products(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }
        check_admin_referer( 'asevj_bulk_sync_woo_products' );
        $result = self::run_bulk_woo( 'sync' );
        self::set_notice( 0 === $result['failed'], 'Linked products synchronized', self::bulk_result_message( $result ) );
        self::admin_redirect( 'asevj-woocommerce' );
    }

    public function bulk_create_sync_woo_products(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }
        check_admin_referer( 'asevj_bulk_create_sync_woo_products' );
        $result = self::run_bulk_woo( 'all' );
        self::set_notice( 0 === $result['failed'], 'All varsity products processed', self::bulk_result_message( $result ) );
        self::admin_redirect( 'asevj-woocommerce' );
    }

    private static function style_product( int $style_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }
        $product_id = absint( get_post_meta( $style_id, '_asevj_woo_product_id', true ) );
        return $product_id ? wc_get_product( $product_id ) : null;
    }

    public function create_woo_product(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }

        $style_id = isset( $_POST['style_id'] ) ? absint( $_POST['style_id'] ) : 0;
        check_admin_referer( 'asevj_create_woo_' . $style_id );

        if ( self::style_product( $style_id ) ) {
            self::set_notice( true, 'Already linked', 'That style already has a WooCommerce product.' );
            self::admin_redirect( 'asevj-woocommerce' );
        }

        $result = self::create_or_sync_product( $style_id, true );
        if ( is_wp_error( $result ) ) {
            self::set_notice( false, 'Product creation failed', $result->get_error_message() );
        } else {
            self::set_notice( true, 'WooCommerce product created', 'A product was created and linked using the global varsity defaults and this style’s images/details.' );
        }

        self::admin_redirect( 'asevj-woocommerce' );
    }

    public function sync_woo_product(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }

        $style_id = isset( $_POST['style_id'] ) ? absint( $_POST['style_id'] ) : 0;
        check_admin_referer( 'asevj_sync_woo_' . $style_id );

        $result = self::create_or_sync_product( $style_id, false );
        if ( is_wp_error( $result ) ) {
            self::set_notice( false, 'Sync failed', $result->get_error_message() );
        } else {
            self::set_notice( true, 'Product synced', 'Name, base price, descriptions, featured image, gallery, category, and varsity relationship were synchronized.' );
        }

        self::admin_redirect( 'asevj-woocommerce' );
    }

    public function duplicate_style(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permission denied.' );
        }
        $style_id = isset( $_POST['style_id'] ) ? absint( $_POST['style_id'] ) : 0;
        check_admin_referer( 'asevj_duplicate_style_' . $style_id );
        $style = get_post( $style_id );
        if ( ! $style || 'asevj_style' !== $style->post_type ) {
            wp_die( 'Invalid style.' );
        }
        $new_id = wp_insert_post( [ 'post_type' => 'asevj_style', 'post_status' => 'draft', 'post_title' => $style->post_title . ' Copy', 'menu_order' => $style->menu_order + 1 ] );
        if ( $new_id && ! is_wp_error( $new_id ) ) {
            foreach ( get_post_meta( $style_id ) as $key => $values ) {
                if ( '_asevj_woo_product_id' === $key ) {
                    continue;
                }
                foreach ( $values as $value ) {
                    add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
                }
            }
            $thumb = get_post_thumbnail_id( $style_id );
            if ( $thumb ) {
                set_post_thumbnail( $new_id, $thumb );
            }
        }
        wp_safe_redirect( get_edit_post_link( $new_id ?: $style_id, 'raw' ) );
        exit;
    }

    public function duplicate_school(): void {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Permission denied.' );
        }
        $school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
        check_admin_referer( 'asevj_duplicate_school_' . $school_id );
        $school = get_post( $school_id );
        if ( ! $school || 'asevj_school' !== $school->post_type ) {
            wp_die( 'Invalid school.' );
        }
        $new_id = wp_insert_post( [ 'post_type' => 'asevj_school', 'post_status' => 'draft', 'post_title' => $school->post_title . ' Copy', 'menu_order' => $school->menu_order + 1 ] );
        if ( $new_id && ! is_wp_error( $new_id ) ) {
            foreach ( get_post_meta( $school_id ) as $key => $values ) {
                foreach ( $values as $value ) {
                    add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
                }
            }
            $styles = get_posts( [ 'post_type' => 'asevj_style', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'meta_key' => '_asevj_school_id', 'meta_value' => $school_id, 'orderby' => [ 'menu_order' => 'ASC', 'date' => 'ASC' ] ] );
            foreach ( $styles as $style ) {
                $copy_id = wp_insert_post( [ 'post_type' => 'asevj_style', 'post_status' => 'draft', 'post_title' => $style->post_title, 'menu_order' => $style->menu_order ] );
                if ( ! $copy_id || is_wp_error( $copy_id ) ) {
                    continue;
                }
                foreach ( get_post_meta( $style->ID ) as $key => $values ) {
                    if ( in_array( $key, [ '_asevj_school_id', '_asevj_woo_product_id' ], true ) ) {
                        continue;
                    }
                    foreach ( $values as $value ) {
                        add_post_meta( $copy_id, $key, maybe_unserialize( $value ) );
                    }
                }
                update_post_meta( $copy_id, '_asevj_school_id', $new_id );
                $thumb = get_post_thumbnail_id( $style->ID );
                if ( $thumb ) {
                    set_post_thumbnail( $copy_id, $thumb );
                }
            }
        }
        wp_safe_redirect( get_edit_post_link( $new_id ?: $school_id, 'raw' ) );
        exit;
    }


    public function create_page(): void {
        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_create_page' );
        $existing = get_posts( [
            'post_type'      => 'page',
            'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_asevj_generated_page',
            'meta_value'     => 1,
        ] );
        if ( $existing ) {
            wp_safe_redirect( get_edit_post_link( (int) $existing[0], 'raw' ) );
            exit;
        }
        $page_id = wp_insert_post( [
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => 'Varsity Jackets',
            'post_content' => '<!-- wp:asevj/full-experience {"align":"full"} /-->',
        ], true );
        if ( is_wp_error( $page_id ) ) {
            wp_die( esc_html( $page_id->get_error_message() ) );
        }
        update_post_meta( $page_id, '_asevj_generated_page', 1 );
        wp_safe_redirect( get_edit_post_link( $page_id, 'raw' ) );
        exit;
    }

    public function check_updates(): void {
        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_die( 'Permission denied.' );
        }
        check_admin_referer( 'asevj_check_updates' );
        ASEVJ_Updater::clear_cache();
        $release = ASEVJ_Updater::latest_release( true );
        if ( is_wp_error( $release ) ) {
            self::set_notice( false, 'GitHub check could not complete', $release->get_error_message() );
        } elseif ( empty( $release['version'] ) ) {
            self::set_notice( true, 'No published update found', 'The updater is ready, but the GitHub repository does not have a published update package yet.' );
        } elseif ( version_compare( $release['version'], ASEVJ_VERSION, '>' ) ) {
            self::set_notice( true, 'Update available', 'Version ' . $release['version'] . ' is available through the normal WordPress Plugins updater.' );
            delete_site_transient( 'update_plugins' );
        } else {
            self::set_notice( true, 'You are current', 'Installed version ' . ASEVJ_VERSION . ' is at least as new as the latest published GitHub update.' );
        }
        self::admin_redirect( 'asevj-tools' );
    }

    public static function render_data_backup(): void {
        echo '<section class="asevj-admin-card"><h2>Portable Backup</h2><p>Export the plugin settings, schools, style definitions, image IDs, and WooCommerce links as JSON. This complements the old-gallery ZIP importer.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_export_data' );
        echo '<input type="hidden" name="action" value="asevj_export_data"><button class="button button-secondary">Download JSON Backup</button></form><hr><h3>Restore a JSON backup</h3><form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_import_data' );
        echo '<input type="hidden" name="action" value="asevj_import_data"><input type="file" name="asevj_json" accept="application/json,.json" required> <button class="button">Import JSON Backup</button></form></section>';
    }

    public static function render_woo_manager(): void {
        self::notice();

        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="asevj-status-card"><strong>WooCommerce is not active</strong><span>Activate WooCommerce to turn varsity jacket styles into sellable products.</span></div>';
            return;
        }

        $settings = self::woo_settings();
        $styles = self::all_styles();
        $linked = 0;
        foreach ( $styles as $style ) {
            if ( self::style_product( (int) $style->ID ) ) {
                $linked++;
            }
        }
        $missing = max( 0, count( $styles ) - $linked );

        echo '<div class="asevj-status-card is-good"><strong>WooCommerce connected</strong><span>' . esc_html( $linked ) . ' of ' . esc_html( count( $styles ) ) . ' jacket styles are linked to products. ' . esc_html( $missing ) . ' still need products.</span></div>';

        echo '<div class="asevj-dashboard-grid asevj-woo-bulk-grid">';

        echo '<section class="asevj-admin-card"><div class="asevj-panel-heading"><div><h2>Varsity Product Defaults</h2><p>These defaults make bulk setup fast. A style can override only its base price when needed.</p></div></div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_save_woo_settings' );
        echo '<input type="hidden" name="action" value="asevj_save_woo_settings">';
        echo '<div class="asevj-grid asevj-grid-2">';
        echo '<div class="asevj-field"><label><strong>Default Base Price</strong></label><div class="asevj-price-input"><span>$</span><input type="number" min="0" step="0.01" name="default_base_price" value="' . esc_attr( $settings['default_base_price'] ) . '"></div><p class="description">Used for every jacket style unless that style has a Base Price Override. Default: $400.</p></div>';
        echo '<div class="asevj-field"><label><strong>New Product Status</strong></label><select name="new_product_status"><option value="draft" ' . selected( $settings['new_product_status'], 'draft', false ) . '>Draft — review before publishing</option><option value="publish" ' . selected( $settings['new_product_status'], 'publish', false ) . '>Published immediately</option></select><p class="description">This only affects newly generated products.</p></div>';
        echo '<div class="asevj-field"><label><strong>Product Category</strong></label><input name="product_category" value="' . esc_attr( $settings['product_category'] ) . '"><p class="description">Created automatically if it does not already exist.</p></div>';
        echo '<div class="asevj-field"><label><strong>SKU Prefix</strong></label><input name="sku_prefix" value="' . esc_attr( $settings['sku_prefix'] ) . '"><p class="description">Example: ASE-VJ-CROOKSVILLE-CLASSIC-VARSITY-JACKET.</p></div>';
        echo '</div><button class="button button-primary">Save Product Defaults</button></form></section>';

        echo '<section class="asevj-admin-card"><div class="asevj-panel-heading"><div><h2>Bulk Product Setup</h2><p>Create the missing WooCommerce products and synchronize the products that are already linked.</p></div></div>';
        echo '<div class="asevj-stats asevj-mini-stats"><div><strong>' . esc_html( count( $styles ) ) . '</strong><span>Total Styles</span></div><div><strong>' . esc_html( $linked ) . '</strong><span>Linked</span></div><div><strong>' . esc_html( $missing ) . '</strong><span>Missing</span></div></div>';
        echo '<div class="asevj-callout"><strong>What gets synchronized?</strong><span>Product name, effective base price, short/full description, featured image, gallery, Varsity Jackets category, SKU when missing, and the style ↔ product link.</span></div>';
        echo '<p class="description"><strong>Price rule:</strong> style Base Price Override → otherwise global $' . esc_html( $settings['default_base_price'] ) . '. Syncing intentionally pushes that effective price to simple WooCommerce products.</p>';
        echo '<div class="asevj-bulk-actions">';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_bulk_create_sync_woo_products' );
        echo '<input type="hidden" name="action" value="asevj_bulk_create_sync_woo_products"><button class="button button-primary button-hero">Create / Sync All Styles</button></form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_bulk_create_woo_products' );
        echo '<input type="hidden" name="action" value="asevj_bulk_create_woo_products"><button class="button">Create Missing Products</button></form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_bulk_sync_woo_products' );
        echo '<input type="hidden" name="action" value="asevj_bulk_sync_woo_products"><button class="button">Sync Linked Products</button></form>';

        echo '</div></section></div>';

        echo '<section class="asevj-admin-card"><div class="asevj-panel-heading"><div><h2>Style → Product Manager</h2><p>Review individual jacket styles, pricing overrides, and WooCommerce links. Products generated here use the same bulk defaults above.</p></div></div><div class="asevj-woo-manager">';
        foreach ( $styles as $style ) {
            $school_id = absint( get_post_meta( $style->ID, '_asevj_school_id', true ) );
            $product_id = absint( get_post_meta( $style->ID, '_asevj_woo_product_id', true ) );
            $product = $product_id ? wc_get_product( $product_id ) : null;
            $thumb = get_the_post_thumbnail_url( $style->ID, 'thumbnail' );
            $override = trim( (string) get_post_meta( $style->ID, '_asevj_fallback_price', true ) );
            $effective = self::style_base_price( (int) $style->ID );

            echo '<article class="asevj-woo-row"><div class="asevj-style-thumb">' . ( $thumb ? '<img src="' . esc_url( $thumb ) . '" alt="">' : '<span class="dashicons dashicons-format-image"></span>' ) . '</div><div class="asevj-woo-row__main"><small>' . esc_html( get_the_title( $school_id ) ) . '</small><strong>' . esc_html( get_the_title( $style ) ) . '</strong>';
            echo '<span class="asevj-price-source"><b>$' . esc_html( $effective ) . '</b> ' . ( '' !== $override ? 'style override' : 'global default' ) . '</span>';
            if ( $product ) {
                echo '<span class="asevj-badge is-green">Linked: #' . esc_html( $product_id ) . '</span><span>' . wp_kses_post( $product->get_price_html() ?: 'No Woo price yet' ) . '</span>';
            } else {
                echo '<span class="asevj-badge">Product not created</span>';
            }
            echo '</div><div class="asevj-woo-row__actions">';
            if ( $product ) {
                echo '<a class="button" href="' . esc_url( get_edit_post_link( $product_id ) ) . '">Edit Product</a><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'asevj_sync_woo_' . $style->ID );
                echo '<input type="hidden" name="action" value="asevj_sync_woo_product"><input type="hidden" name="style_id" value="' . esc_attr( $style->ID ) . '"><button class="button">Sync Style → Woo</button></form>';
            } else {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'asevj_create_woo_' . $style->ID );
                echo '<input type="hidden" name="action" value="asevj_create_woo_product"><input type="hidden" name="style_id" value="' . esc_attr( $style->ID ) . '"><button class="button button-primary">Create Product</button></form>';
            }
            echo '<a class="button-link" href="' . esc_url( get_edit_post_link( $style->ID ) ) . '">Edit style / price override</a></div></article>';
        }
        echo '</div></section>';
    }

    public static function render_update_manager(): void {
        self::notice();
        echo '<div class="asevj-dashboard-grid"><section class="asevj-admin-card"><h2>Installed Build</h2>';
        echo '<p><strong>Version:</strong> ' . esc_html( ASEVJ_VERSION ) . '</p>';
        echo '<p><strong>Updater:</strong> Native WordPress + GitHub Release asset</p>';
        echo '<p><strong>Repository:</strong> <code>All-Star-Embroidery/varsity-jackets</code></p>';
        echo '<p><strong>Manifest:</strong> <code>latest.json</code> (30-minute cache)</p>';
        echo '<p><span class="asevj-badge is-green">Automatic updates enabled</span></p>';
        echo '<p class="description">Varsity Jackets is explicitly allowed to auto-install newer versions after WordPress detects them. Packages must be genuine GitHub Release assets; repository/raw ZIPs are rejected by the updater.</p>';
        echo '<hr><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'asevj_check_updates' );
        echo '<input type="hidden" name="action" value="asevj_check_updates"><button class="button button-primary">Check GitHub Now</button></form></section>';
        echo '<section class="asevj-admin-card"><h2>Release Pipeline</h2><ul class="asevj-check-list">';
        echo '<li>Source is committed under the permanent <code>all-star-varsity-jackets/</code> folder</li>';
        echo '<li>GitHub Actions validates PHP, JavaScript, block JSON, and ZIP structure</li>';
        echo '<li>Every version is published as a versioned GitHub Release</li>';
        echo '<li>The installable ZIP is uploaded as a real GitHub Release asset</li>';
        echo '<li><code>latest.json</code> is changed only after the Release asset exists</li>';
        echo '<li>WordPress reads <code>latest.json</code>, shows version details, and installs through the normal upgrader</li>';
        echo '</ul></section></div>';
    }}
