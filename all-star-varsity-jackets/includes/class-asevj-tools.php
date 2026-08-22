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
        if ( 'asevj_style' !== get_post_type( $style_id ) ) {
            wp_die( 'Invalid jacket style.' );
        }
        $existing = self::style_product( $style_id );
        if ( $existing ) {
            self::set_notice( true, 'Already linked', 'That style already has a WooCommerce product.' );
            self::admin_redirect( 'asevj-woocommerce' );
        }
        $school_id = absint( get_post_meta( $style_id, '_asevj_school_id', true ) );
        $product = new WC_Product_Simple();
        $product->set_name( trim( get_the_title( $school_id ) . ' ' . get_the_title( $style_id ) ) );
        $product->set_status( 'draft' );
        $product->set_catalog_visibility( 'visible' );
        $price = (string) get_post_meta( $style_id, '_asevj_fallback_price', true );
        if ( '' !== $price ) {
            $product->set_regular_price( wc_format_decimal( $price ) );
        }
        $description = (string) get_post_meta( $style_id, '_asevj_description', true );
        $product->set_short_description( $description );
        $thumb = get_post_thumbnail_id( $style_id );
        if ( $thumb ) {
            $product->set_image_id( $thumb );
        }
        $gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $style_id, '_asevj_gallery_ids', true ) ) ) );
        $product->set_gallery_image_ids( $gallery );
        $product_id = $product->save();
        update_post_meta( $product_id, '_asevj_style_id', $style_id );
        update_post_meta( $style_id, '_asevj_woo_product_id', $product_id );
        self::set_notice( true, 'WooCommerce product created', 'A draft product was created and linked. Review its pricing, variations, and purchasing options before publishing.' );
        self::admin_redirect( 'asevj-woocommerce' );
    }

    public function sync_woo_product(): void {
        if ( ! current_user_can( 'edit_products' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_die( 'WooCommerce permission denied or WooCommerce is inactive.' );
        }
        $style_id = isset( $_POST['style_id'] ) ? absint( $_POST['style_id'] ) : 0;
        check_admin_referer( 'asevj_sync_woo_' . $style_id );
        $product = self::style_product( $style_id );
        if ( ! $product ) {
            self::set_notice( false, 'Sync failed', 'No WooCommerce product is linked to that style.' );
            self::admin_redirect( 'asevj-woocommerce' );
        }
        $description = (string) get_post_meta( $style_id, '_asevj_description', true );
        $product->set_short_description( $description );
        $thumb = get_post_thumbnail_id( $style_id );
        if ( $thumb ) {
            $product->set_image_id( $thumb );
        }
        $gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $style_id, '_asevj_gallery_ids', true ) ) ) );
        $product->set_gallery_image_ids( $gallery );
        if ( $product instanceof WC_Product_Simple ) {
            $price = (string) get_post_meta( $style_id, '_asevj_fallback_price', true );
            if ( '' !== $price ) {
                $product->set_regular_price( wc_format_decimal( $price ) );
            }
        }
        $product->save();
        self::set_notice( true, 'Product synced', 'Style images, gallery, description, and simple-product fallback price were pushed to WooCommerce.' );
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
            self::set_notice( true, 'No published releases found', 'The updater is ready, but the GitHub repository does not have a release yet.' );
        } elseif ( version_compare( $release['version'], ASEVJ_VERSION, '>' ) ) {
            self::set_notice( true, 'Update available', 'Version ' . $release['version'] . ' is available through the normal WordPress Plugins updater.' );
            delete_site_transient( 'update_plugins' );
        } else {
            self::set_notice( true, 'You are current', 'Installed version ' . ASEVJ_VERSION . ' is at least as new as the latest GitHub release.' );
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
            echo '<div class="asevj-status-card"><strong>WooCommerce is not active</strong><span>The varsity gallery works without it. Activate WooCommerce when you are ready to sell jacket styles as products.</span></div>';
            return;
        }
        $styles = get_posts( [ 'post_type' => 'asevj_style', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
        echo '<div class="asevj-status-card is-good"><strong>WooCommerce connected</strong><span>Each style can remain showcase-only, link to an existing product, or generate a new draft product from its current style data.</span></div>';
        echo '<section class="asevj-admin-card"><div class="asevj-panel-heading"><div><h2>Style → Product Manager</h2><p>Creating a product here is intentionally safe: it starts as a <strong>draft</strong> so you can add sizes, variations, pricing, and purchasing rules before publishing.</p></div></div><div class="asevj-woo-manager">';
        foreach ( $styles as $style ) {
            $school_id = absint( get_post_meta( $style->ID, '_asevj_school_id', true ) );
            $product_id = absint( get_post_meta( $style->ID, '_asevj_woo_product_id', true ) );
            $product = $product_id ? wc_get_product( $product_id ) : null;
            $thumb = get_the_post_thumbnail_url( $style->ID, 'thumbnail' );
            echo '<article class="asevj-woo-row"><div class="asevj-style-thumb">' . ( $thumb ? '<img src="' . esc_url( $thumb ) . '" alt="">' : '<span class="dashicons dashicons-format-image"></span>' ) . '</div><div class="asevj-woo-row__main"><small>' . esc_html( get_the_title( $school_id ) ) . '</small><strong>' . esc_html( get_the_title( $style ) ) . '</strong>';
            if ( $product ) {
                echo '<span class="asevj-badge is-green">Linked: #' . esc_html( $product_id ) . '</span><span>' . wp_kses_post( $product->get_price_html() ?: 'No Woo price yet' ) . '</span>';
            } else {
                echo '<span class="asevj-badge">Showcase only</span>';
            }
            echo '</div><div class="asevj-woo-row__actions">';
            if ( $product ) {
                echo '<a class="button" href="' . esc_url( get_edit_post_link( $product_id ) ) . '">Edit Product</a><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'asevj_sync_woo_' . $style->ID );
                echo '<input type="hidden" name="action" value="asevj_sync_woo_product"><input type="hidden" name="style_id" value="' . esc_attr( $style->ID ) . '"><button class="button">Sync Style → Woo</button></form>';
            } else {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'asevj_create_woo_' . $style->ID );
                echo '<input type="hidden" name="action" value="asevj_create_woo_product"><input type="hidden" name="style_id" value="' . esc_attr( $style->ID ) . '"><button class="button button-primary">Create Draft Product</button></form>';
            }
            echo '<a class="button-link" href="' . esc_url( get_edit_post_link( $style->ID ) ) . '">Edit style</a></div></article>';
        }
        echo '</div></section>';
    }

    public static function render_update_manager(): void {
        self::notice();
        echo '<div class="asevj-dashboard-grid"><section class="asevj-admin-card"><h2>Installed Build</h2>';
        echo '<p><strong>Version:</strong> ' . esc_html( ASEVJ_VERSION ) . '</p>';
        echo '<p><strong>Updater:</strong> Native WordPress + GitHub Release asset</p>';
        echo '<p><strong>Repository:</strong> <code>rolejarczyk/ASE.VarsityJackets</code></p>';
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
