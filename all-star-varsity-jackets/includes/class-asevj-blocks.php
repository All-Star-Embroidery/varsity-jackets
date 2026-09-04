<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Blocks {
    private static ?ASEVJ_Blocks $instance = null;

    public static function instance(): ASEVJ_Blocks {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
        add_filter( 'block_categories_all', [ $this, 'block_category' ], 10, 2 );
    }

    public function block_category( array $categories, $editor_context ): array {
        array_unshift( $categories, [
            'slug'  => 'all-star-embroidery',
            'title' => 'All Star Embroidery',
            'icon'  => 'admin-customizer',
        ] );
        return $categories;
    }

    public function register_blocks(): void {
        wp_register_style( 'asevj-frontend', ASEVJ_URL . 'assets/frontend.css', [], ASEVJ_VERSION );
        wp_register_script( 'asevj-frontend', ASEVJ_URL . 'assets/frontend.js', [], ASEVJ_VERSION, true );

        wp_register_script(
            'asevj-editor',
            ASEVJ_URL . 'assets/editor.js',
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ],
            ASEVJ_VERSION,
            true
        );
        wp_register_style( 'asevj-editor-style', ASEVJ_URL . 'assets/editor.css', [ 'asevj-frontend' ], ASEVJ_VERSION );

        $blocks = [
            'hero'            => [ ASEVJ_Render::class, 'render_hero' ],
            'browser'         => [ ASEVJ_Render::class, 'render_browser' ],
            'benefits'        => [ ASEVJ_Render::class, 'render_benefits' ],
            'full-experience' => [ ASEVJ_Render::class, 'render_full' ],
            'product-page'    => [ ASEVJ_Product_Page::class, 'render' ],
        ];

        foreach ( $blocks as $folder => $callback ) {
            register_block_type( ASEVJ_DIR . 'blocks/' . $folder, [ 'render_callback' => $callback ] );
        }
    }

    public function editor_assets(): void {
        wp_enqueue_style( 'asevj-frontend' );
    }
}
