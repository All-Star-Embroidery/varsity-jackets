<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_WooCommerce {
    private static ?ASEVJ_WooCommerce $instance = null;

    public static function instance(): ASEVJ_WooCommerce {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'ase_varsity_jackets', [ $this, 'shortcode_full' ] );
        add_shortcode( 'ase_varsity_browser', [ $this, 'shortcode_browser' ] );
        add_shortcode( 'ase_varsity_hero', [ $this, 'shortcode_hero' ] );
        add_shortcode( 'ase_varsity_benefits', [ $this, 'shortcode_benefits' ] );

        add_action( 'init', [ $this, 'ensure_product_template' ], 30 );
        add_filter( 'template_include', [ $this, 'varsity_product_template' ], 99 );
        add_filter( 'woocommerce_is_purchasable', [ $this, 'disable_online_purchase' ], 20, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_product_page_assets' ], 30 );
        add_action( 'add_meta_boxes_product', [ $this, 'product_template_meta_box' ] );
        add_filter( 'body_class', [ $this, 'body_class' ] );
    }

    public function shortcode_full( array $atts = [] ): string {
        return ASEVJ_Render::render_full( $atts );
    }

    public function shortcode_browser( array $atts = [] ): string {
        return ASEVJ_Render::render_browser( $atts );
    }

    public function shortcode_hero( array $atts = [] ): string {
        return ASEVJ_Render::render_hero( $atts );
    }

    public function shortcode_benefits( array $atts = [] ): string {
        return ASEVJ_Render::render_benefits( $atts );
    }

    public static function linked_style_id( int $product_id ): int {
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

    public static function is_varsity_product( int $product_id ): bool {
        return 0 < self::linked_style_id( $product_id );
    }

    public function ensure_product_template(): void {
        $template_id = absint( get_option( 'asevj_product_template_id', 0 ) );
        if ( $template_id && 'asevj_template' === get_post_type( $template_id ) ) {
            return;
        }

        $existing = get_posts( [
            'post_type'      => 'asevj_template',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_asevj_product_template',
            'meta_value'     => '1',
        ] );

        if ( ! empty( $existing[0] ) ) {
            update_option( 'asevj_product_template_id', absint( $existing[0] ) );
            return;
        }

        $template_id = wp_insert_post( [
            'post_type'    => 'asevj_template',
            'post_status'  => 'publish',
            'post_title'   => 'Varsity Jacket Product Page',
            'post_name'    => 'varsity-jacket-product-page',
            'post_content' => '<!-- wp:all-star-varsity-jackets/product-page /-->',
            'meta_input'   => [
                '_asevj_product_template' => '1',
            ],
        ] );

        if ( ! is_wp_error( $template_id ) && $template_id ) {
            update_option( 'asevj_product_template_id', absint( $template_id ) );
        }
    }

    public static function product_template_id(): int {
        $template_id = absint( get_option( 'asevj_product_template_id', 0 ) );
        return $template_id && 'asevj_template' === get_post_type( $template_id ) ? $template_id : 0;
    }

    public function varsity_product_template( string $template ): string {
        if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
            return $template;
        }

        $product_id = absint( get_queried_object_id() );
        if ( ! self::is_varsity_product( $product_id ) ) {
            return $template;
        }

        $plugin_template = ASEVJ_DIR . 'templates/single-product-varsity-jacket.php';
        return file_exists( $plugin_template ) ? $plugin_template : $template;
    }

    public function disable_online_purchase( bool $purchasable, $product ): bool {
        if ( is_object( $product ) && is_a( $product, 'WC_Product' ) && self::is_varsity_product( absint( $product->get_id() ) ) ) {
            return false;
        }
        return $purchasable;
    }

    public function enqueue_product_page_assets(): void {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return;
        }

        $product_id = absint( get_queried_object_id() );
        if ( ! self::is_varsity_product( $product_id ) ) {
            return;
        }

        wp_enqueue_style( 'asevj-frontend' );
        wp_enqueue_style(
            'asevj-product-page',
            ASEVJ_URL . 'blocks/product-page/style.css',
            [ 'asevj-frontend' ],
            ASEVJ_VERSION
        );
        wp_enqueue_script(
            'asevj-product-page',
            ASEVJ_URL . 'blocks/product-page/view.js',
            [],
            ASEVJ_VERSION,
            true
        );
    }

    public function product_template_meta_box(): void {
        add_meta_box(
            'asevj-product-template-status',
            'Varsity Jacket Product Page',
            [ $this, 'render_product_template_meta_box' ],
            'product',
            'side',
            'high'
        );
    }

    public function render_product_template_meta_box( $post ): void {
        $product_id = absint( $post->ID ?? 0 );
        $linked = self::is_varsity_product( $product_id );
        $layout_id = self::product_template_id();

        if ( $linked ) {
            echo '<p><strong style="color:#0a7a35;">✓ Varsity Jacket Template Active</strong></p>';
            echo '<p>This product is linked to a jacket style, so its storefront page automatically uses the dedicated Varsity Jacket layout.</p>';
            echo '<p><strong>Product type:</strong> Simple product is correct.</p>';
            echo '<p><strong>Online purchase:</strong> Disabled. The jacket page uses the call-to-order experience instead.</p>';
            if ( $layout_id ) {
                echo '<p><a class="button button-primary" href="' . esc_url( get_edit_post_link( $layout_id ) ) . '">Edit Jacket Product Layout</a></p>';
            }
        } else {
            echo '<p><strong>Normal WooCommerce Template</strong></p>';
            echo '<p>This product is not linked to a Varsity Jacket style, so your normal Single Product template remains unchanged.</p>';
        }
    }

    public function body_class( array $classes ): array {
        if ( function_exists( 'is_product' ) && is_product() && self::is_varsity_product( absint( get_queried_object_id() ) ) ) {
            $classes[] = 'asevj-varsity-product-page';
        }
        return $classes;
    }
}
