<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_WooCommerce {
    private static ?ASEVJ_WooCommerce $instance = null;

    private const BLOCK_TEMPLATE_SLUG = 'single-product-varsity-jacket';
    private const BLOCK_TEMPLATE_NAME = 'all-star-varsity-jackets//single-product-varsity-jacket';

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

        add_action( 'init', [ $this, 'register_product_block_template' ], 30 );
        add_filter( 'single_template_hierarchy', [ $this, 'varsity_block_template_hierarchy' ], 20 );
        add_filter( 'template_include', [ $this, 'classic_theme_template_fallback' ], 99 );
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

    private static function legacy_layout_content(): string {
        $template_id = absint( get_option( 'asevj_product_template_id', 0 ) );
        if ( $template_id && 'asevj_template' === get_post_type( $template_id ) ) {
            $post = get_post( $template_id );
            if ( $post && trim( (string) $post->post_content ) ) {
                return (string) $post->post_content;
            }
        }

        return '<!-- wp:all-star-varsity-jackets/product-page /-->';
    }

    private static function default_block_template_content(): string {
        $content = self::legacy_layout_content();

        return '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->' . "\n\n" .
            '<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->' . "\n" .
            '<main class="wp-block-group asevj-varsity-product-template">' . "\n" .
            $content . "\n" .
            '</main>' . "\n" .
            '<!-- /wp:group -->' . "\n\n" .
            '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->';
    }

    public function register_product_block_template(): void {
        if ( ! function_exists( 'register_block_template' ) ) {
            return;
        }

        register_block_template(
            self::BLOCK_TEMPLATE_NAME,
            [
                'title'       => __( 'Varsity Jacket Product', 'all-star-varsity-jackets' ),
                'description' => __( 'Theme-compatible product template used only for linked varsity jacket products. Uses the active theme header and footer.', 'all-star-varsity-jackets' ),
                'post_types'  => [ 'product' ],
                'content'     => self::default_block_template_content(),
            ]
        );
    }

    public function varsity_block_template_hierarchy( array $templates ): array {
        if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
            return $templates;
        }

        $product_id = absint( get_queried_object_id() );
        if ( ! self::is_varsity_product( $product_id ) ) {
            return $templates;
        }

        array_unshift( $templates, self::BLOCK_TEMPLATE_SLUG );
        return array_values( array_unique( $templates ) );
    }

    /**
     * Block themes resolve the registered Site Editor template through the normal
     * WordPress template hierarchy. Classic themes keep a PHP fallback so they
     * still receive their own theme header/footer instead of a blank canvas.
     */
    public function classic_theme_template_fallback( string $template ): string {
        if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
            return $template;
        }

        $product_id = absint( get_queried_object_id() );
        if ( ! self::is_varsity_product( $product_id ) ) {
            return $template;
        }

        if ( current_theme_supports( 'block-templates' ) && function_exists( 'register_block_template' ) ) {
            return $template;
        }

        $plugin_template = ASEVJ_DIR . 'templates/single-product-varsity-jacket.php';
        return file_exists( $plugin_template ) ? $plugin_template : $template;
    }

    public static function product_template_editor_url(): string {
        if ( ! current_theme_supports( 'block-templates' ) || ! function_exists( 'register_block_template' ) ) {
            return admin_url( 'edit.php?post_type=asevj_template' );
        }

        $template_id = get_stylesheet() . '//' . self::BLOCK_TEMPLATE_SLUG;
        return admin_url( 'site-editor.php?postType=wp_template&postId=' . rawurlencode( $template_id ) . '&canvas=edit' );
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

        if ( $linked ) {
            echo '<p><strong style="color:#0a7a35;">✓ Varsity Jacket Template Active</strong></p>';
            echo '<p>This linked jacket automatically uses the dedicated Varsity Jacket template while keeping your active theme header, footer, and Site Editor template parts.</p>';
            echo '<p><strong>Product type:</strong> Simple product is correct.</p>';
            echo '<p><strong>Online purchase:</strong> Disabled. The page uses the call-to-order experience.</p>';
            echo '<p><a class="button button-primary" href="' . esc_url( self::product_template_editor_url() ) . '">Edit Jacket Template</a></p>';
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
