<?php
/**
 * Plugin Name: All Star Varsity Jackets
 * Description: Modular, highly customizable varsity jacket school/style showcase with optional WooCommerce product linking for All Star Embroidery.
 * Version: 0.2.0-beta.8
 * Author: All Star Embroidery
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: all-star-varsity-jackets
 * Update URI: https://github.com/rolejarczyk/ASE.VarsityJackets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ASEVJ_VERSION', '0.2.0-beta.8' );
define( 'ASEVJ_FILE', __FILE__ );
define( 'ASEVJ_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASEVJ_URL', plugin_dir_url( __FILE__ ) );

require_once ASEVJ_DIR . 'includes/class-asevj-post-types.php';
require_once ASEVJ_DIR . 'includes/class-asevj-admin.php';
require_once ASEVJ_DIR . 'includes/class-asevj-render.php';
require_once ASEVJ_DIR . 'includes/class-asevj-blocks.php';
require_once ASEVJ_DIR . 'includes/class-asevj-woocommerce.php';
require_once ASEVJ_DIR . 'includes/class-asevj-importer.php';
require_once ASEVJ_DIR . 'includes/class-asevj-updater.php';
require_once ASEVJ_DIR . 'includes/class-asevj-tools.php';

final class ASEVJ_Plugin {
    private static ?ASEVJ_Plugin $instance = null;

    public static function instance(): ASEVJ_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'boot' ] );
        register_activation_hook( ASEVJ_FILE, [ $this, 'activate' ] );
    }

    public function boot(): void {
        $this->maybe_upgrade();
        ASEVJ_Post_Types::instance();
        ASEVJ_Admin::instance();
        ASEVJ_Blocks::instance();
        ASEVJ_WooCommerce::instance();
        ASEVJ_Importer::instance();
        ASEVJ_Updater::instance();
        ASEVJ_Tools::instance();

        add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
    }


    private function maybe_upgrade(): void {
        $installed = (string) get_option( 'asevj_version', '' );
        if ( ASEVJ_VERSION === $installed ) {
            return;
        }

        $settings = get_option( 'asevj_design_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        // Beta 1 used a red accent that is not part of the current All Star website palette.
        unset( $settings['red'] );
        if ( empty( $settings['accent'] ) ) {
            $settings['accent'] = '#F2B619';
        }
        if ( empty( $settings['cream'] ) ) {
            $settings['cream'] = '#F6F3EA';
        }
        if ( isset( $settings['navy'] ) && '#071B38' === strtoupper( (string) $settings['navy'] ) ) {
            $settings['navy'] = '#101B31';
        }
        if ( empty( $settings['full_bleed'] ) ) {
            $settings['full_bleed'] = 1;
        }

        update_option( 'asevj_design_settings', wp_parse_args( $settings, ASEVJ_Admin::default_design_settings() ) );
        update_option( 'asevj_version', ASEVJ_VERSION );
    }

    public function register_frontend_assets(): void {
        wp_register_style(
            'asevj-frontend',
            ASEVJ_URL . 'assets/frontend.css',
            [],
            ASEVJ_VERSION
        );

        wp_register_script(
            'asevj-frontend',
            ASEVJ_URL . 'assets/frontend.js',
            [],
            ASEVJ_VERSION,
            true
        );
    }

    public function activate(): void {
        ASEVJ_Post_Types::register_post_types();

        if ( false === get_option( 'asevj_design_settings', false ) ) {
            add_option( 'asevj_design_settings', ASEVJ_Admin::default_design_settings() );
        }

        update_option( 'asevj_version', ASEVJ_VERSION );
        flush_rewrite_rules();
    }
}

ASEVJ_Plugin::instance();
