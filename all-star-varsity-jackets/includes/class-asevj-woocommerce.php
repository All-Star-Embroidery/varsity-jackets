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
}
