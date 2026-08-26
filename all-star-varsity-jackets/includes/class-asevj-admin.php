<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Admin {
    private static ?ASEVJ_Admin $instance = null;

    public static function instance(): ASEVJ_Admin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ], 5 );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_asevj_school', [ $this, 'save_school' ] );
        add_action( 'save_post_asevj_style', [ $this, 'save_style' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_asevj_reorder_styles', [ $this, 'ajax_reorder_styles' ] );
        add_action( 'wp_ajax_asevj_reorder_schools', [ $this, 'ajax_reorder_schools' ] );
        add_filter( 'manage_asevj_school_posts_columns', [ $this, 'school_columns' ] );
        add_action( 'manage_asevj_school_posts_custom_column', [ $this, 'school_column_content' ], 10, 2 );
        add_filter( 'manage_asevj_style_posts_columns', [ $this, 'style_columns' ] );
        add_action( 'manage_asevj_style_posts_custom_column', [ $this, 'style_column_content' ], 10, 2 );
        add_filter( 'redirect_post_location', [ $this, 'keep_data_editor_redirect' ], 99, 2 );
    }

    public static function default_design_settings(): array {
        return [
            'navy'          => '#101B31',
            'gold'          => '#D6A83A',
            'accent'        => '#F2B619',
            'cream'         => '#F6F3EA',
            'white'         => '#FFFFFF',
            'light'         => '#F7F8FA',
            'text'          => '#111827',
            'muted'         => '#667085',
            'radius'        => 8,
            'button_radius' => 4,
            'max_width'     => 1360,
            'hero_height'   => 390,
            'section_gap'   => 24,
            'styles_visible'=> 3,
            'school_tile_width' => 104,
            'heading_font'  => 'inherit',
            'body_font'     => 'inherit',
            'card_shadow'   => 1,
            'show_prices'   => 1,
            'show_filters'  => 1,
            'show_search'   => 1,
            'full_bleed'    => 1,
            'hero_title'    => 'VARSITY JACKETS',
            'hero_kicker'   => 'BUILT WITH PRIDE. MADE TO REPRESENT.',
            'hero_body'     => 'Premium 24-ounce melton wool, genuine leather sleeves, expert embroidery, and craftsmanship built to represent your school with pride.',
            'signature_image_id' => 0,
        ];
    }

    public function admin_menu(): void {
        $cap = 'edit_posts';

        add_menu_page(
            'All Star Varsity Jackets',
            'All Star Jackets',
            $cap,
            'asevj-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-admin-customizer',
            57
        );

        add_submenu_page( 'asevj-dashboard', 'Dashboard', 'Dashboard', $cap, 'asevj-dashboard', [ $this, 'render_dashboard' ] );
        add_submenu_page( 'asevj-dashboard', 'Style Organizer', 'Style Organizer', $cap, 'asevj-organizer', [ $this, 'render_organizer' ] );
        add_submenu_page( 'asevj-dashboard', 'Design Settings', 'Design Settings', 'manage_options', 'asevj-design', [ $this, 'render_design' ] );
        add_submenu_page( 'asevj-dashboard', 'WooCommerce', 'WooCommerce', $cap, 'asevj-woocommerce', [ $this, 'render_woocommerce' ] );
        add_submenu_page( 'asevj-dashboard', 'Import / Export', 'Import / Export', 'manage_options', 'asevj-import', [ $this, 'render_import' ] );
        add_submenu_page( 'asevj-dashboard', 'Tools & Updates', 'Tools & Updates', 'manage_options', 'asevj-tools', [ $this, 'render_tools' ] );
    }

    public function enqueue_admin_assets( string $hook ): void {
        $screen = get_current_screen();
        $is_asevj = $screen && (
            in_array( $screen->post_type, [ 'asevj_school', 'asevj_style' ], true ) ||
            str_contains( (string) $screen->id, 'asevj' )
        );

        if ( ! $is_asevj ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'asevj-admin', ASEVJ_URL . 'assets/admin.css', [], ASEVJ_VERSION );
        wp_enqueue_script( 'asevj-admin', ASEVJ_URL . 'assets/admin.js', [ 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ], ASEVJ_VERSION, true );

        wp_localize_script( 'asevj-admin', 'ASEVJ_ADMIN', [
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'adminPostUrl' => admin_url( 'admin-post.php' ),
            'nonce'        => wp_create_nonce( 'asevj_admin' ),
        ] );

        if ( class_exists( 'WooCommerce' ) ) {
            wp_enqueue_script( 'wc-enhanced-select' );
            wp_enqueue_style( 'woocommerce_admin_styles' );
        }
    }

    public function add_meta_boxes(): void {
        remove_meta_box( 'postimagediv', 'asevj_style', 'side' );
        add_meta_box( 'asevj_school_details', 'School Details', [ $this, 'school_details_box' ], 'asevj_school', 'normal', 'high' );
        add_meta_box( 'asevj_school_brand', 'School Branding', [ $this, 'school_brand_box' ], 'asevj_school', 'normal', 'default' );
        add_meta_box( 'asevj_school_styles', 'Jacket Styles', [ $this, 'school_styles_box' ], 'asevj_school', 'normal', 'default' );
        add_meta_box( 'asevj_school_help', 'Quick Guide', [ $this, 'school_help_box' ], 'asevj_school', 'side', 'high' );

        add_meta_box( 'asevj_style_details', 'Style Details', [ $this, 'style_details_box' ], 'asevj_style', 'normal', 'high' );
        add_meta_box( 'asevj_style_gallery', 'Style Gallery', [ $this, 'style_gallery_box' ], 'asevj_style', 'normal', 'default' );
        add_meta_box( 'asevj_style_features', 'Features', [ $this, 'style_features_box' ], 'asevj_style', 'normal', 'default' );
        add_meta_box( 'asevj_style_woo', 'WooCommerce', [ $this, 'style_woo_box' ], 'asevj_style', 'side', 'high' );
        add_meta_box( 'asevj_style_help', 'Quick Guide', [ $this, 'style_help_box' ], 'asevj_style', 'side', 'default' );
    }

    private function field( string $label, string $name, string $value, string $help = '', string $type = 'text' ): void {
        echo '<div class="asevj-field">';
        echo '<label for="' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
        if ( 'textarea' === $type ) {
            echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="4">' . esc_textarea( $value ) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        }
        if ( $help ) {
            echo '<p class="description">' . esc_html( $help ) . '</p>';
        }
        echo '</div>';
    }

    public function school_details_box( WP_Post $post ): void {
        wp_nonce_field( 'asevj_save_school', 'asevj_school_nonce' );
        $enabled     = metadata_exists( 'post', $post->ID, '_asevj_enabled' ) ? (int) get_post_meta( $post->ID, '_asevj_enabled', true ) : 1;
        $mascot      = (string) get_post_meta( $post->ID, '_asevj_mascot', true );
        $location    = (string) get_post_meta( $post->ID, '_asevj_location', true );
        $district    = (string) get_post_meta( $post->ID, '_asevj_district', true );
        $description = (string) get_post_meta( $post->ID, '_asevj_description', true );

        echo '<div class="asevj-intro"><strong>Start here.</strong> Add the basic school information visitors should see before choosing a jacket style.</div>';
        echo '<label class="asevj-toggle-row"><input type="checkbox" name="asevj_enabled" value="1" ' . checked( $enabled, 1, false ) . '> <span><strong>Show this school on the website</strong><small>Turn this off to prepare a school without publishing it in the browser.</small></span></label>';
        echo '<div class="asevj-grid asevj-grid-2">';
        $this->field( 'School Name', 'asevj_school_name', $post->post_title, 'This is the school name shown throughout the Varsity Jackets experience.' );
        $this->field( 'Mascot', 'asevj_mascot', $mascot, 'Example: Wildcats, Warriors, Ceramics.' );
        $this->field( 'Location', 'asevj_location', $location, 'Example: Newark, Ohio.' );
        $this->field( 'District / Group', 'asevj_district', $district, 'Optional. Useful for the Browse by School filter.' );
        echo '</div>';
        $this->field( 'School Description', 'asevj_description', $description, 'Keep this short. Two or three sentences works best on the frontend.', 'textarea' );
    }

    public function school_brand_box( WP_Post $post ): void {
        $logo_id   = absint( get_post_meta( $post->ID, '_asevj_logo_id', true ) );
        $primary   = (string) get_post_meta( $post->ID, '_asevj_primary', true ) ?: '#D6A83A';
        $secondary = (string) get_post_meta( $post->ID, '_asevj_secondary', true ) ?: '#101B31';
        $accent    = (string) get_post_meta( $post->ID, '_asevj_accent', true ) ?: '#F2B619';
        $logo_url  = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

        echo '<div class="asevj-grid asevj-grid-brand">';
        echo '<div class="asevj-media-field">';
        echo '<strong>School Logo</strong><p class="description">Optional. If no logo is uploaded, the frontend creates a clean text/monogram fallback.</p>';
        echo '<div class="asevj-media-preview">' . ( $logo_url ? '<img src="' . esc_url( $logo_url ) . '" alt="">' : '<span>No logo selected</span>' ) . '</div>';
        echo '<input type="hidden" class="asevj-media-id" name="asevj_logo_id" value="' . esc_attr( $logo_id ) . '">';
        echo '<button type="button" class="button asevj-pick-media">Choose Logo</button> <button type="button" class="button-link-delete asevj-remove-media">Remove</button>';
        echo '</div>';
        echo '<div class="asevj-color-fields">';
        echo '<div class="asevj-field"><label><strong>Primary</strong></label><input class="asevj-color" name="asevj_primary" value="' . esc_attr( $primary ) . '"></div>';
        echo '<div class="asevj-field"><label><strong>Secondary</strong></label><input class="asevj-color" name="asevj_secondary" value="' . esc_attr( $secondary ) . '"></div>';
        echo '<div class="asevj-field"><label><strong>Accent</strong></label><input class="asevj-color" name="asevj_accent" value="' . esc_attr( $accent ) . '"></div>';
        echo '<p class="description">These colors are available to blocks as optional school-specific accents. The global All Star design remains the default.</p>';
        echo '</div></div>';
    }

    public function school_styles_box( WP_Post $post ): void {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<div class="asevj-empty"><strong>Save this school first.</strong><p>Once the school exists, you can add Style 1, Style 2, Style 3, and as many additional jacket styles as needed.</p></div>';
            return;
        }

        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => -1,
            'meta_key'       => '_asevj_school_id',
            'meta_value'     => $post->ID,
            'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
        ] );

        $add_url = add_query_arg( [
            'post_type'       => 'asevj_style',
            'asevj_school_id' => $post->ID,
        ], admin_url( 'post-new.php' ) );

        $organize_url = add_query_arg( [ 'page' => 'asevj-organizer', 'school_id' => $post->ID ], admin_url( 'admin.php' ) );
        echo '<div class="asevj-panel-heading"><div><strong>Styles for ' . esc_html( get_the_title( $post ) ) . '</strong><p>Visitors will see these as Style 1, Style 2, Style 3, etc. Drag to change the order.</p></div><div class="asevj-panel-actions"><a class="button" href="' . esc_url( $organize_url ) . '">Visual Style Organizer</a><a class="button button-primary" href="' . esc_url( $add_url ) . '">+ Add New Style</a></div></div>';

        if ( ! $styles ) {
            echo '<div class="asevj-empty"><strong>No jacket styles yet.</strong><p>Add the first style to begin building this school.</p><a class="button button-primary" href="' . esc_url( $add_url ) . '">Add Style 1</a></div>';
            return;
        }

        echo '<div class="asevj-style-sorter" data-school="' . esc_attr( $post->ID ) . '">';
        foreach ( $styles as $index => $style ) {
            $thumb = get_the_post_thumbnail_url( $style->ID, 'thumbnail' );
            $woo_id = absint( get_post_meta( $style->ID, '_asevj_woo_product_id', true ) );
            $enabled = (int) get_post_meta( $style->ID, '_asevj_enabled', true );
            echo '<div class="asevj-style-row" data-style="' . esc_attr( $style->ID ) . '">';
            echo '<span class="dashicons dashicons-menu asevj-drag"></span>';
            echo '<div class="asevj-style-thumb">' . ( $thumb ? '<img src="' . esc_url( $thumb ) . '" alt="">' : '<span class="dashicons dashicons-format-image"></span>' ) . '</div>';
            echo '<div class="asevj-style-main"><strong>Style ' . esc_html( $index + 1 ) . ' — ' . esc_html( get_the_title( $style ) ) . '</strong><small>' . ( $enabled ? 'Visible on frontend' : 'Hidden on frontend' ) . '</small></div>';
            echo '<div class="asevj-style-status">' . ( $woo_id ? '<span class="asevj-badge is-green">Woo linked</span>' : '<span class="asevj-badge">Showcase only</span>' ) . '</div>';
            echo '<a class="button" href="' . esc_url( get_edit_post_link( $style->ID ) ) . '">Edit Style</a>';
            echo '<button type="button" class="button-link asevj-post-action" data-action="asevj_duplicate_style" data-field="style_id" data-id="' . esc_attr( $style->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'asevj_duplicate_style_' . $style->ID ) ) . '">Duplicate</button>';
            echo '</div>';
        }
        echo '</div><p class="description asevj-sort-status">Drag a row and drop it where you want it. The order saves automatically.</p>';
    }

    public function school_help_box( WP_Post $post ): void {
        echo '<div class="asevj-guide">';
        echo '<h3>School setup</h3><ol><li>Add school details.</li><li>Add or upload the logo.</li><li>Save the school.</li><li>Add one or more jacket styles.</li><li>Optionally link each style to WooCommerce.</li><li>Use the Visual Style Organizer to drag imported photos into the right styles.</li><li>Preview the Varsity Jackets block on a page.</li></ol>';
        echo '<p><strong>Tip:</strong> A school can have any number of styles. Their drag-and-drop order determines Style 1, Style 2, Style 3, and so on.</p>';
        if ( 'auto-draft' !== $post->post_status ) {
            $organize_url = add_query_arg( [ 'page' => 'asevj-organizer', 'school_id' => $post->ID ], admin_url( 'admin.php' ) );
            echo '<p><a class="button button-primary" href="' . esc_url( $organize_url ) . '">Open Style Organizer</a></p>';
            echo '<button type="button" class="button asevj-post-action" data-action="asevj_duplicate_school" data-field="school_id" data-id="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'asevj_duplicate_school_' . $post->ID ) ) . '">Duplicate School + Styles</button>';
        }
        echo '</div>';
    }

    public function style_details_box( WP_Post $post ): void {
        wp_nonce_field( 'asevj_save_style', 'asevj_style_nonce' );

        $school_id = absint( get_post_meta( $post->ID, '_asevj_school_id', true ) );
        if ( ! $school_id && isset( $_GET['asevj_school_id'] ) ) {
            $school_id = absint( $_GET['asevj_school_id'] );
        }
        $enabled     = metadata_exists( 'post', $post->ID, '_asevj_enabled' ) ? (int) get_post_meta( $post->ID, '_asevj_enabled', true ) : 1;
        $subtitle    = (string) get_post_meta( $post->ID, '_asevj_subtitle', true );
        $description = (string) get_post_meta( $post->ID, '_asevj_description', true );
        $fallback    = (string) get_post_meta( $post->ID, '_asevj_fallback_price', true );
        $cta         = (string) get_post_meta( $post->ID, '_asevj_cta', true ) ?: 'Customize This Jacket';

        $schools = get_posts( [ 'post_type' => 'asevj_school', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );

        echo '<div class="asevj-intro"><strong>One style = one jacket choice.</strong> Give it a clear name, image, description, and optional WooCommerce product.</div>';
        echo '<label class="asevj-toggle-row"><input type="checkbox" name="asevj_style_enabled" value="1" ' . checked( $enabled, 1, false ) . '> <span><strong>Show this style on the website</strong><small>Hide unfinished styles without deleting them.</small></span></label>';
        echo '<div class="asevj-grid asevj-grid-2">';
        $this->field( 'Style Name', 'asevj_style_name', $post->post_title, 'This replaces generic imported names such as “Imported Jacket Gallery”.' );
        echo '<div class="asevj-field"><label><strong>School</strong></label><select name="asevj_school_id" required><option value="">Select a school…</option>';
        foreach ( $schools as $school ) {
            echo '<option value="' . esc_attr( $school->ID ) . '" ' . selected( $school_id, $school->ID, false ) . '>' . esc_html( get_the_title( $school ) ) . '</option>';
        }
        echo '</select><p class="description">This determines which school displays this style.</p></div>';
        $this->field( 'Style Subtitle', 'asevj_subtitle', $subtitle, 'Optional. Example: Classic wool body / leather sleeves.' );
        $default_base = ASEVJ_Tools::woo_settings()['default_base_price'] ?? '400';
        $this->field( 'Base Price Override', 'asevj_fallback_price', $fallback, 'Optional. Leave blank to use the global varsity jacket base price of $' . $default_base . '. This price is pushed to the linked WooCommerce product.', 'number' );
        $this->field( 'Button Label', 'asevj_cta', $cta, 'Example: Customize This Jacket.' );
        echo '</div>';
        $this->field( 'Short Description', 'asevj_style_description', $description, 'Two short sentences works best on a style card.', 'textarea' );
        $image_id = get_post_thumbnail_id( $post->ID );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
        echo '<div class="asevj-media-field asevj-main-style-image"><strong>Main Jacket Image</strong><p class="description">This is the large image used on the Style card. You can also change it by dragging a different photo to the first position in Style Organizer.</p><div class="asevj-media-preview">' . ( $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span>No main jacket image selected</span>' ) . '</div><input type="hidden" class="asevj-media-id" name="asevj_style_image_id" value="' . esc_attr( $image_id ) . '"><button type="button" class="button asevj-pick-media">Choose Main Jacket Image</button> <button type="button" class="button-link-delete asevj-remove-media">Remove</button></div>';
    }

    public function style_gallery_box( WP_Post $post ): void {
        $ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_asevj_gallery_ids', true ) ) ) );
        echo '<p>Optional supporting photos for this style: back view, sleeve details, patches, lining, alternate examples, etc. The frontend loads these on demand.</p>';
        echo '<input type="hidden" class="asevj-gallery-ids" name="asevj_gallery_ids" value="' . esc_attr( implode( ',', $ids ) ) . '">';
        echo '<div class="asevj-gallery-preview">';
        foreach ( $ids as $id ) {
            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( $url ) {
                echo '<div class="asevj-gallery-item" data-id="' . esc_attr( $id ) . '"><img src="' . esc_url( $url ) . '" alt=""><button type="button" class="asevj-gallery-remove" aria-label="Remove">×</button></div>';
            }
        }
        echo '</div>';
        echo '<button type="button" class="button asevj-pick-gallery">Add / Reorder Gallery Images</button>';
    }

    public function style_features_box( WP_Post $post ): void {
        $features = (string) get_post_meta( $post->ID, '_asevj_features', true );
        echo '<p>Enter one feature per line. Keep them customer-friendly.</p>';
        echo '<textarea name="asevj_features" rows="7" style="width:100%" placeholder="Wool Body&#10;Leather Sleeves&#10;Chenille Letter&#10;Quilted Lining&#10;Striped Knit Trim">' . esc_textarea( $features ) . '</textarea>';
    }

    public function style_woo_box( WP_Post $post ): void {
        $product_id = absint( get_post_meta( $post->ID, '_asevj_woo_product_id', true ) );

        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="asevj-empty"><strong>WooCommerce is not active.</strong><p>This style still works as a showcase item. Activate WooCommerce later to connect it to a product.</p></div>';
            return;
        }

        echo '<p><strong>Optional.</strong> Link this style to a product when you want pricing and the CTA to come from WooCommerce.</p>';
        echo '<select class="wc-product-search" style="width:100%" name="asevj_woo_product_id" data-placeholder="Search for a product…" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true">';
        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                echo '<option value="' . esc_attr( $product_id ) . '" selected>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
            }
        }
        echo '</select>';

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                echo '<div class="asevj-woo-summary"><span class="asevj-badge is-green">Linked Product</span><strong>' . esc_html( $product->get_name() ) . '</strong><span>' . wp_kses_post( $product->get_price_html() ) . '</span><a class="button" href="' . esc_url( get_edit_post_link( $product_id ) ) . '">Edit Woo Product</a></div>';
                echo '<button type="button" class="button asevj-post-action" data-action="asevj_sync_woo_product" data-field="style_id" data-id="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'asevj_sync_woo_' . $post->ID ) ) . '">Sync Style → Woo</button>';
            }
        } else {
            echo '<div class="asevj-callout"><strong>Showcase-only is okay.</strong> You do not need a WooCommerce product for every style.</div>';
            if ( 'auto-draft' !== $post->post_status ) {
                echo '<button type="button" class="button button-primary asevj-post-action" data-action="asevj_create_woo_product" data-field="style_id" data-id="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'asevj_create_woo_' . $post->ID ) ) . '">Create Draft Woo Product</button>';
            }
        }
    }

    public function style_help_box(): void {
        echo '<div class="asevj-guide"><h3>Style setup</h3><ol><li>Name the style.</li><li>Choose its school.</li><li>Choose the Main Jacket Image.</li><li>Add optional gallery details.</li><li>Add the key features.</li><li>Optionally link a WooCommerce product.</li></ol><p>The plugin automatically labels the cards Style 1, Style 2, Style 3 based on the order inside the school.</p></div>';
    }

    public function save_school( int $post_id ): void {
        if ( ! isset( $_POST['asevj_school_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['asevj_school_nonce'] ) ), 'asevj_save_school' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['asevj_school_name'] ) ) {
            $school_name = sanitize_text_field( wp_unslash( $_POST['asevj_school_name'] ) );
            if ( '' !== $school_name && $school_name !== get_the_title( $post_id ) ) {
                remove_action( 'save_post_asevj_school', [ $this, 'save_school' ] );
                wp_update_post( [ 'ID' => $post_id, 'post_title' => $school_name ] );
                add_action( 'save_post_asevj_school', [ $this, 'save_school' ] );
            }
        }

        $map = [
            '_asevj_enabled'     => isset( $_POST['asevj_enabled'] ) ? 1 : 0,
            '_asevj_mascot'      => isset( $_POST['asevj_mascot'] ) ? sanitize_text_field( wp_unslash( $_POST['asevj_mascot'] ) ) : '',
            '_asevj_location'    => isset( $_POST['asevj_location'] ) ? sanitize_text_field( wp_unslash( $_POST['asevj_location'] ) ) : '',
            '_asevj_district'    => isset( $_POST['asevj_district'] ) ? sanitize_text_field( wp_unslash( $_POST['asevj_district'] ) ) : '',
            '_asevj_description' => isset( $_POST['asevj_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['asevj_description'] ) ) : '',
            '_asevj_logo_id'     => isset( $_POST['asevj_logo_id'] ) ? absint( $_POST['asevj_logo_id'] ) : 0,
            '_asevj_primary'     => isset( $_POST['asevj_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['asevj_primary'] ) ) : '',
            '_asevj_secondary'   => isset( $_POST['asevj_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['asevj_secondary'] ) ) : '',
            '_asevj_accent'      => isset( $_POST['asevj_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['asevj_accent'] ) ) : '',
        ];

        foreach ( $map as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
    }

    public function save_style( int $post_id ): void {
        if ( ! isset( $_POST['asevj_style_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['asevj_style_nonce'] ) ), 'asevj_save_style' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['asevj_style_name'] ) ) {
            $style_name = sanitize_text_field( wp_unslash( $_POST['asevj_style_name'] ) );
            if ( '' !== $style_name && $style_name !== get_the_title( $post_id ) ) {
                remove_action( 'save_post_asevj_style', [ $this, 'save_style' ] );
                wp_update_post( [ 'ID' => $post_id, 'post_title' => $style_name ] );
                add_action( 'save_post_asevj_style', [ $this, 'save_style' ] );
            }
        }

        $gallery_ids = [];
        if ( isset( $_POST['asevj_gallery_ids'] ) ) {
            $gallery_ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['asevj_gallery_ids'] ) ) ) ) );
        }

        $raw_fallback = isset( $_POST['asevj_fallback_price'] ) ? (string) wp_unslash( $_POST['asevj_fallback_price'] ) : '';
        $fallback_price = function_exists( 'wc_format_decimal' )
            ? wc_format_decimal( $raw_fallback )
            : preg_replace( '/[^0-9.]/', '', $raw_fallback );

        $map = [
            '_asevj_school_id'      => isset( $_POST['asevj_school_id'] ) ? absint( $_POST['asevj_school_id'] ) : 0,
            '_asevj_enabled'        => isset( $_POST['asevj_style_enabled'] ) ? 1 : 0,
            '_asevj_subtitle'       => isset( $_POST['asevj_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['asevj_subtitle'] ) ) : '',
            '_asevj_description'    => isset( $_POST['asevj_style_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['asevj_style_description'] ) ) : '',
            '_asevj_fallback_price' => $fallback_price,
            '_asevj_cta'            => isset( $_POST['asevj_cta'] ) ? sanitize_text_field( wp_unslash( $_POST['asevj_cta'] ) ) : 'Customize This Jacket',
            '_asevj_gallery_ids'    => implode( ',', $gallery_ids ),
            '_asevj_features'       => isset( $_POST['asevj_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['asevj_features'] ) ) : '',
            '_asevj_woo_product_id' => isset( $_POST['asevj_woo_product_id'] ) ? absint( $_POST['asevj_woo_product_id'] ) : 0,
        ];

        foreach ( $map as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
        if ( isset( $_POST['asevj_style_image_id'] ) ) {
            $image_id = absint( $_POST['asevj_style_image_id'] );
            if ( $image_id ) {
                set_post_thumbnail( $post_id, $image_id );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }
    }

    public function keep_data_editor_redirect( string $location, int $post_id ): string {
        $post_type = get_post_type( $post_id );
        if ( ! in_array( $post_type, [ 'asevj_school', 'asevj_style' ], true ) ) {
            return $location;
        }

        // WordPress should normally return to post.php after an update. Force
        // the data editor URL if another admin hook/theme/plugin tries to send
        // these custom post types to the normal Blog Posts screen instead.
        if ( false === strpos( $location, 'post.php' ) ) {
            return add_query_arg( [
                'post'    => $post_id,
                'action'  => 'edit',
                'message' => 1,
            ], admin_url( 'post.php' ) );
        }
        return $location;
    }

    public function ajax_reorder_styles(): void {
        check_ajax_referer( 'asevj_admin', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : [];
        foreach ( $ids as $order => $id ) {
            wp_update_post( [ 'ID' => $id, 'menu_order' => $order ] );
        }
        wp_send_json_success( [ 'message' => 'Style order saved.' ] );
    }

    public function ajax_reorder_schools(): void {
        check_ajax_referer( 'asevj_admin', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }
        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : [];
        foreach ( $ids as $order => $id ) {
            if ( 'asevj_school' === get_post_type( $id ) ) {
                wp_update_post( [ 'ID' => $id, 'menu_order' => $order ] );
            }
        }
        wp_send_json_success( [ 'message' => 'School order saved.' ] );
    }

    public function register_settings(): void {
        register_setting( 'asevj_design_group', 'asevj_design_settings', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_design_settings' ],
            'default'           => self::default_design_settings(),
        ] );
        register_setting( 'asevj_update_group', 'asevj_auto_update', [ 'type' => 'boolean', 'sanitize_callback' => static fn( $v ) => empty( $v ) ? 0 : 1, 'default' => 0 ] );
        register_setting( 'asevj_update_group', 'asevj_update_channel', [
            'type' => 'string',
            'sanitize_callback' => static function ( $value ) {
                return in_array( $value, [ 'beta', 'stable' ], true ) ? $value : 'stable';
            },
            'default' => 'stable',
        ] );
    }

    public function sanitize_design_settings( array $input ): array {
        $defaults = self::default_design_settings();
        $out = $defaults;
        foreach ( [ 'navy', 'gold', 'accent', 'cream', 'white', 'light', 'text', 'muted' ] as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $out[ $key ] = sanitize_hex_color( $input[ $key ] ) ?: $defaults[ $key ];
            }
        }
        $out['radius']       = isset( $input['radius'] ) ? min( 40, max( 0, absint( $input['radius'] ) ) ) : $defaults['radius'];
        $out['button_radius']= isset( $input['button_radius'] ) ? min( 30, max( 0, absint( $input['button_radius'] ) ) ) : $defaults['button_radius'];
        $out['max_width']    = isset( $input['max_width'] ) ? min( 1800, max( 900, absint( $input['max_width'] ) ) ) : $defaults['max_width'];
        $out['hero_height']  = isset( $input['hero_height'] ) ? min( 720, max( 280, absint( $input['hero_height'] ) ) ) : $defaults['hero_height'];
        $out['section_gap']  = isset( $input['section_gap'] ) ? min( 100, max( 0, absint( $input['section_gap'] ) ) ) : $defaults['section_gap'];
        $out['styles_visible']= isset( $input['styles_visible'] ) ? min( 4, max( 1, absint( $input['styles_visible'] ) ) ) : $defaults['styles_visible'];
        $out['school_tile_width']= isset( $input['school_tile_width'] ) ? min( 160, max( 82, absint( $input['school_tile_width'] ) ) ) : $defaults['school_tile_width'];
        $out['heading_font'] = isset( $input['heading_font'] ) ? preg_replace( '/[;{}<>]/', '', sanitize_text_field( $input['heading_font'] ) ) : $defaults['heading_font'];
        $out['body_font']    = isset( $input['body_font'] ) ? preg_replace( '/[;{}<>]/', '', sanitize_text_field( $input['body_font'] ) ) : $defaults['body_font'];
        $out['card_shadow']  = empty( $input['card_shadow'] ) ? 0 : 1;
        $out['show_prices']  = empty( $input['show_prices'] ) ? 0 : 1;
        $out['show_filters'] = empty( $input['show_filters'] ) ? 0 : 1;
        $out['show_search']  = empty( $input['show_search'] ) ? 0 : 1;
        $out['full_bleed']    = empty( $input['full_bleed'] ) ? 0 : 1;
        $out['hero_title']   = isset( $input['hero_title'] ) ? sanitize_text_field( $input['hero_title'] ) : $defaults['hero_title'];
        $out['hero_kicker']  = isset( $input['hero_kicker'] ) ? sanitize_text_field( $input['hero_kicker'] ) : $defaults['hero_kicker'];
        $out['hero_body']    = isset( $input['hero_body'] ) ? sanitize_textarea_field( $input['hero_body'] ) : $defaults['hero_body'];
        $out['signature_image_id'] = isset( $input['signature_image_id'] ) ? absint( $input['signature_image_id'] ) : 0;
        return $out;
    }

    private function admin_header( string $title, string $subtitle = '' ): void {
        echo '<div class="wrap asevj-wrap">';
        echo '<div class="asevj-admin-hero"><div class="asevj-admin-title"><h1>' . esc_html( $title ) . '</h1>' . ( $subtitle ? '<p>' . esc_html( $subtitle ) . '</p>' : '' ) . '</div><span class="asevj-version">' . esc_html( ASEVJ_VERSION ) . '</span></div>';
    }

    private function admin_footer(): void {
        echo '</div>';
    }

    public function render_dashboard(): void {
        $schools = wp_count_posts( 'asevj_school' );
        $styles  = wp_count_posts( 'asevj_style' );
        $school_count = (int) ( $schools->publish ?? 0 ) + (int) ( $schools->draft ?? 0 ) + (int) ( $schools->private ?? 0 );
        $style_count  = (int) ( $styles->publish ?? 0 ) + (int) ( $styles->draft ?? 0 ) + (int) ( $styles->private ?? 0 );

        $this->admin_header( 'All Star Varsity Jackets', 'Build school-based jacket collections that can optionally connect to WooCommerce.' );
        echo '<div class="asevj-beta-banner"><strong>Version 1.0</strong><span>Production release: school/style management, structured imports, live storefront blocks, WooCommerce tools, backups, and GitHub updating are ready for the live Varsity Jackets page.</span></div>';
        echo '<div class="asevj-stats"><div><strong>' . esc_html( $school_count ) . '</strong><span>Schools</span></div><div><strong>' . esc_html( $style_count ) . '</strong><span>Jacket Styles</span></div><div><strong>' . ( class_exists( 'WooCommerce' ) ? 'Connected' : 'Optional' ) . '</strong><span>WooCommerce</span></div></div>';
        echo '<div class="asevj-dashboard-grid">';
        echo '<section class="asevj-admin-card"><h2>Recommended workflow</h2><div class="asevj-steps"><div><b>1</b><span><strong>Import the old gallery</strong><small>School folders and jacket images come in automatically.</small></span></div><div><b>2</b><span><strong>Organize jacket styles</strong><small>Drag photos into Style 1, Style 2, Style 3…</small></span></div><div><b>3</b><span><strong>Link WooCommerce if needed</strong><small>Showcase-only styles are allowed too.</small></span></div><div><b>4</b><span><strong>Build the page visually</strong><small>The Gutenberg block renders the real frontend design while you edit.</small></span></div></div><div class="asevj-dashboard-actions"><a class="button button-primary button-hero" href="' . esc_url( admin_url( 'admin.php?page=asevj-import' ) ) . '">Import Old Gallery ZIP</a> <a class="button button-hero" href="' . esc_url( admin_url( 'admin.php?page=asevj-organizer' ) ) . '">Style Organizer</a> <form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="asevj-inline-form">';
        wp_nonce_field( 'asevj_create_page' );
        echo '<input type="hidden" name="action" value="asevj_create_page"><button class="button button-hero">Create / Open Varsity Page</button></form></div></section>';
        echo '<section class="asevj-admin-card"><h2>Version 1.0 includes</h2><ul class="asevj-check-list"><li>Multiple styles per school with drag-and-drop ordering</li><li>Gallery-first Browse by School experience</li><li>School search + district/mascot filtering</li><li>Dedicated responsive desktop, tablet, and mobile hero controls</li><li>Hero, Browser, Benefits, and Full Experience Gutenberg blocks with live editor previews</li><li>Structured CSV + ZIP school/style/image importer</li><li>School logos, mascot artwork, school details, style features, and galleries</li><li>Optional WooCommerce product linking and product tools</li><li>JSON backup / restore</li><li>GitHub-powered WordPress updating with checksum verification</li></ul><p><strong>Ready for production.</strong> Future releases can focus on incremental improvements instead of workflow changes.</p></section>';
        echo '</div>';
        $ordered_schools = get_posts( [ 'post_type' => 'asevj_school', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ] ] );
        if ( $ordered_schools ) {
            echo '<section class="asevj-admin-card asevj-school-order-card"><div class="asevj-panel-heading"><div><h2>School Browser Order</h2><p>Drag schools into the order visitors should see in the horizontal school selector. Saves automatically.</p></div></div><div class="asevj-school-sorter">';
            foreach ( $ordered_schools as $school ) {
                echo '<div class="asevj-school-order-row" data-school="' . esc_attr( $school->ID ) . '"><span class="dashicons dashicons-menu"></span><strong>' . esc_html( get_the_title( $school ) ) . '</strong><small>' . esc_html( (string) get_post_meta( $school->ID, '_asevj_mascot', true ) ) . '</small><a href="' . esc_url( get_edit_post_link( $school->ID ) ) . '">Edit</a></div>';
            }
            echo '</div><p class="description asevj-school-sort-status">Drag a school to change its storefront order.</p></section>';
        }
        $this->admin_footer();
    }

    public function render_design(): void {
        $s = wp_parse_args( get_option( 'asevj_design_settings', [] ), self::default_design_settings() );
        $image_url = $s['signature_image_id'] ? wp_get_attachment_image_url( $s['signature_image_id'], 'large' ) : '';
        $this->admin_header( 'Design Settings', 'Global defaults for all Varsity Jackets blocks. Keep it simple here; fine-tuning can come later.' );
        echo '<form method="post" action="options.php" class="asevj-design-form">';
        settings_fields( 'asevj_design_group' );
        echo '<div class="asevj-dashboard-grid">';
        echo '<section class="asevj-admin-card"><h2>Site Colors</h2><p class="description">The storefront defaults to the navy, gold, white, and cream used on the current All Star site. School colors only affect each school’s own logo/monogram accents.</p><div class="asevj-grid asevj-grid-2">';
        foreach ( [ 'navy' => 'Navy', 'gold' => 'Gold', 'accent' => 'Button / Highlight Gold', 'cream' => 'Warm Cream', 'white' => 'White', 'light' => 'Light Background', 'text' => 'Text', 'muted' => 'Muted Text' ] as $key => $label ) {
            echo '<div class="asevj-field"><label><strong>' . esc_html( $label ) . '</strong></label><input class="asevj-color" name="asevj_design_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[ $key ] ) . '"></div>';
        }
        echo '</div></section>';
        echo '<section class="asevj-admin-card"><h2>Hero Content</h2>';
        echo '<div class="asevj-field"><label><strong>Title</strong></label><input name="asevj_design_settings[hero_title]" value="' . esc_attr( $s['hero_title'] ) . '"></div>';
        echo '<div class="asevj-field"><label><strong>Kicker</strong></label><input name="asevj_design_settings[hero_kicker]" value="' . esc_attr( $s['hero_kicker'] ) . '"></div>';
        echo '<div class="asevj-field"><label><strong>Description</strong></label><textarea name="asevj_design_settings[hero_body]" rows="4">' . esc_textarea( $s['hero_body'] ) . '</textarea></div>';
        echo '<div class="asevj-media-field"><strong>All Star Signature Jacket</strong><p class="description">Use an All Star navy / white / gold signature jacket here. School-specific photos remain inside each style.</p><div class="asevj-media-preview">' . ( $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span>No image selected</span>' ) . '</div><input type="hidden" class="asevj-media-id" name="asevj_design_settings[signature_image_id]" value="' . esc_attr( $s['signature_image_id'] ) . '"><button type="button" class="button asevj-pick-media">Choose Signature Jacket</button> <button type="button" class="button-link-delete asevj-remove-media">Remove</button></div>';
        echo '</section>';
        echo '<section class="asevj-admin-card"><h2>Layout Controls</h2><p class="description">The defaults are tuned to the approved mockup. Most sites can leave these alone.</p><div class="asevj-grid asevj-grid-2">';
        foreach ( [ 'radius' => [ 'Card Radius', 0, 40 ], 'button_radius' => [ 'Button Radius', 0, 30 ], 'max_width' => [ 'Content Max Width', 900, 1800 ], 'hero_height' => [ 'Hero Height', 280, 720 ], 'section_gap' => [ 'Section Spacing', 0, 100 ], 'styles_visible' => [ 'Styles Visible', 1, 4 ], 'school_tile_width' => [ 'School Tile Width', 82, 160 ] ] as $key => $config ) {
            echo '<div class="asevj-field"><label><strong>' . esc_html( $config[0] ) . '</strong></label><input type="number" min="' . esc_attr( $config[1] ) . '" max="' . esc_attr( $config[2] ) . '" name="asevj_design_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[ $key ] ) . '"></div>';
        }
        echo '</div><details class="asevj-advanced-settings"><summary>Typography overrides</summary><p class="description">Use <code>inherit</code> to match the WordPress theme, or enter a safe CSS font stack.</p><div class="asevj-field"><label><strong>Heading font</strong></label><input name="asevj_design_settings[heading_font]" value="' . esc_attr( $s['heading_font'] ) . '"></div><div class="asevj-field"><label><strong>Body font</strong></label><input name="asevj_design_settings[body_font]" value="' . esc_attr( $s['body_font'] ) . '"></div></details>';
        foreach ( [ 'card_shadow' => 'Card shadows', 'show_prices' => 'Show prices when available', 'show_search' => 'Show school search', 'show_filters' => 'Show district / mascot filters', 'full_bleed' => 'Break the full experience out to the full browser width' ] as $key => $label ) {
            echo '<label class="asevj-toggle-row"><input type="checkbox" name="asevj_design_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( $s[ $key ], 1, false ) . '><span><strong>' . esc_html( $label ) . '</strong></span></label>';
        }
        echo '</section>';
        echo '</div>';
        submit_button( 'Save Design Settings' );
        echo '</form>';
        $this->admin_footer();
    }

    public function render_woocommerce(): void {
        $this->admin_header( 'WooCommerce', 'Create and keep WooCommerce products synchronized with every varsity jacket style from one screen.' );
        ASEVJ_Tools::render_woo_manager();
        $this->admin_footer();
    }

    public function render_organizer(): void {
        $this->admin_header( 'Style Organizer', 'Split imported jacket photos into Style 1, Style 2, Style 3, and beyond by dragging them visually.' );
        ASEVJ_Tools::render_organizer();
        $this->admin_footer();
    }

    public function render_import(): void {
        $this->admin_header( 'Import / Export', 'Bring the old varsity jacket gallery into the new school/style system, and keep a portable backup of the finished setup.' );
        ASEVJ_Tools::notice();
        ASEVJ_Importer::render_importer();
        ASEVJ_Tools::render_data_backup();
        $this->admin_footer();
    }

    public function render_tools(): void {
        $this->admin_header( 'Tools & Updates', 'GitHub-powered updates, version status, and maintenance information.' );
        ASEVJ_Tools::render_update_manager();
        $this->admin_footer();
    }

    public function beta_notice(): void {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->post_type, [ 'asevj_school', 'asevj_style' ], true ) ) {
            return;
        }
        echo '<div class="notice notice-info asevj-native-notice"><p><strong>All Star Varsity Jackets ' . esc_html( ASEVJ_VERSION ) . '</strong> — Production workflow: School → one or more Styles → optional WooCommerce product.</p></div>';
    }

    public function school_columns( array $columns ): array {
        $columns['asevj_status'] = 'Frontend';
        $columns['asevj_styles'] = 'Styles';
        $columns['asevj_mascot'] = 'Mascot';
        return $columns;
    }

    public function school_column_content( string $column, int $post_id ): void {
        if ( 'asevj_status' === $column ) {
            echo get_post_meta( $post_id, '_asevj_enabled', true ) ? '<span class="asevj-badge is-green">Visible</span>' : '<span class="asevj-badge">Hidden</span>';
        } elseif ( 'asevj_styles' === $column ) {
            $q = new WP_Query( [ 'post_type' => 'asevj_style', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => 1, 'meta_key' => '_asevj_school_id', 'meta_value' => $post_id ] );
            echo esc_html( $q->found_posts );
        } elseif ( 'asevj_mascot' === $column ) {
            echo esc_html( (string) get_post_meta( $post_id, '_asevj_mascot', true ) );
        }
    }

    public function style_columns( array $columns ): array {
        $columns['asevj_school'] = 'School';
        $columns['asevj_woo']    = 'WooCommerce';
        $columns['asevj_visible']= 'Frontend';
        return $columns;
    }

    public function style_column_content( string $column, int $post_id ): void {
        if ( 'asevj_school' === $column ) {
            $school_id = absint( get_post_meta( $post_id, '_asevj_school_id', true ) );
            echo $school_id ? '<a href="' . esc_url( get_edit_post_link( $school_id ) ) . '">' . esc_html( get_the_title( $school_id ) ) . '</a>' : '—';
        } elseif ( 'asevj_woo' === $column ) {
            $product_id = absint( get_post_meta( $post_id, '_asevj_woo_product_id', true ) );
            echo $product_id ? '<span class="asevj-badge is-green">Linked</span>' : '<span class="asevj-badge">Showcase only</span>';
        } elseif ( 'asevj_visible' === $column ) {
            echo get_post_meta( $post_id, '_asevj_enabled', true ) ? '<span class="asevj-badge is-green">Visible</span>' : '<span class="asevj-badge">Hidden</span>';
        }
    }
}
