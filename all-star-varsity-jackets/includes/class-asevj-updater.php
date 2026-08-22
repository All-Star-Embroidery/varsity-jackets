<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Native WordPress update client for GitHub Release assets.
 *
 * Release contract:
 * - Public manifest: https://raw.githubusercontent.com/rolejarczyk/ASE.VarsityJackets/main/latest.json
 * - Package URL: genuine GitHub Release asset under /releases/download/...
 * - Stable plugin basename: all-star-varsity-jackets/all-star-varsity-jackets.php
 */
final class ASEVJ_Updater {
    private static ?ASEVJ_Updater $instance = null;

    private const PLUGIN_NAME = 'All Star Varsity Jackets';
    private const PLUGIN_SLUG = 'all-star-varsity-jackets';
    private const RELEASE_REPO = 'rolejarczyk/ASE.VarsityJackets';
    private const UPDATE_MANIFEST_URL = 'https://raw.githubusercontent.com/rolejarczyk/ASE.VarsityJackets/main/latest.json';
    private const UPDATE_CACHE_KEY = 'all-star-varsity-jackets_github_update_manifest';
    private const UPDATE_CACHE_TTL = 1800;

    public static function instance(): ASEVJ_Updater {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'inject_github_update' ] );
        add_filter( 'plugins_api', [ __CLASS__, 'github_plugin_information' ], 20, 3 );
        add_filter( 'auto_update_plugin', [ __CLASS__, 'enable_github_auto_update' ], 20, 2 );
        add_action( 'upgrader_process_complete', [ __CLASS__, 'clear_update_cache_after_upgrade' ], 10, 2 );
    }

    public static function repo_url(): string {
        return 'https://github.com/' . self::RELEASE_REPO;
    }

    private static function plugin_file(): string {
        return ASEVJ_FILE;
    }

    private static function plugin_basename(): string {
        return plugin_basename( self::plugin_file() );
    }

    public static function clear_cache(): void {
        delete_site_transient( self::UPDATE_CACHE_KEY );
    }

    /**
     * Fetch and sanitize the public release manifest.
     */
    private static function fetch_update_manifest( bool $force = false ): ?array {
        if ( ! $force ) {
            $cached = get_site_transient( self::UPDATE_CACHE_KEY );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $response = wp_remote_get(
            self::UPDATE_MANIFEST_URL,
            [
                'timeout'   => 8,
                'sslverify' => true,
                'headers'   => [
                    'Accept'     => 'application/json',
                    'User-Agent' => 'All-Star-Varsity-Jackets/' . ASEVJ_VERSION,
                ],
            ]
        );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $manifest = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $manifest ) || empty( $manifest['version'] ) || empty( $manifest['download_url'] ) ) {
            return null;
        }

        $manifest['name'] = sanitize_text_field( (string) ( $manifest['name'] ?? self::PLUGIN_NAME ) );
        $manifest['slug'] = sanitize_key( (string) ( $manifest['slug'] ?? self::PLUGIN_SLUG ) );
        $manifest['version'] = sanitize_text_field( (string) $manifest['version'] );
        $manifest['download_url'] = esc_url_raw( (string) $manifest['download_url'] );
        $manifest['homepage'] = isset( $manifest['homepage'] ) ? esc_url_raw( (string) $manifest['homepage'] ) : self::repo_url();
        $manifest['requires'] = sanitize_text_field( (string) ( $manifest['requires'] ?? '6.4' ) );
        $manifest['tested'] = sanitize_text_field( (string) ( $manifest['tested'] ?? '' ) );
        $manifest['requires_php'] = sanitize_text_field( (string) ( $manifest['requires_php'] ?? '8.0' ) );
        $manifest['last_updated'] = sanitize_text_field( (string) ( $manifest['last_updated'] ?? '' ) );
        $manifest['description'] = wp_kses_post( (string) ( $manifest['description'] ?? '' ) );
        $manifest['changelog'] = wp_kses_post( (string) ( $manifest['changelog'] ?? '' ) );

        if ( self::PLUGIN_SLUG !== $manifest['slug'] || ! self::is_release_asset_url( $manifest['download_url'] ) ) {
            return null;
        }

        set_site_transient( self::UPDATE_CACHE_KEY, $manifest, self::UPDATE_CACHE_TTL );
        return $manifest;
    }

    /**
     * Exposed for the plugin's Tools & Updates status screen.
     */
    public static function latest_release( bool $force = false ) {
        $manifest = self::fetch_update_manifest( $force );
        if ( ! $manifest ) {
            return [];
        }

        return [
            'version'      => $manifest['version'],
            'name'         => $manifest['name'],
            'body'         => wp_strip_all_tags( (string) $manifest['changelog'] ),
            'html_url'     => $manifest['homepage'],
            'package'      => $manifest['download_url'],
            'download_url' => $manifest['download_url'],
            'published_at' => $manifest['last_updated'],
        ];
    }

    private static function is_release_asset_url( string $url ): bool {
        if ( '' === $url || 0 !== strpos( $url, 'https://' ) ) {
            return false;
        }

        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || 'github.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
            return false;
        }

        $path = (string) ( $parts['path'] ?? '' );
        return false !== strpos( $path, '/rolejarczyk/ASE.VarsityJackets/releases/download/' )
            && str_ends_with( strtolower( $path ), '.zip' );
    }

    public static function inject_github_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }

        $manifest = self::fetch_update_manifest();
        if ( ! $manifest || version_compare( ASEVJ_VERSION, $manifest['version'], '>=' ) ) {
            return $transient;
        }

        $update = (object) [
            'id'            => self::repo_url(),
            'slug'          => self::PLUGIN_SLUG,
            'plugin'        => self::plugin_basename(),
            'new_version'   => $manifest['version'],
            'url'           => $manifest['homepage'] ?? '',
            'package'       => $manifest['download_url'],
            'icons'         => [],
            'banners'       => [],
            'tested'        => $manifest['tested'] ?? '',
            'requires'      => $manifest['requires'] ?? '',
            'requires_php'  => $manifest['requires_php'] ?? '',
            'compatibility' => new stdClass(),
        ];

        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = [];
        }

        $transient->response[ self::plugin_basename() ] = $update;
        return $transient;
    }

    public static function github_plugin_information( $result, string $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
            return $result;
        }

        $manifest = self::fetch_update_manifest();
        if ( ! $manifest ) {
            return $result;
        }

        return (object) [
            'name'          => self::PLUGIN_NAME,
            'slug'          => self::PLUGIN_SLUG,
            'version'       => $manifest['version'],
            'author'        => 'All Star Embroidery',
            'homepage'      => $manifest['homepage'] ?? '',
            'requires'      => $manifest['requires'] ?? '',
            'tested'        => $manifest['tested'] ?? '',
            'requires_php'  => $manifest['requires_php'] ?? '',
            'last_updated'  => $manifest['last_updated'] ?? '',
            'download_link' => $manifest['download_url'],
            'sections'      => [
                'description' => $manifest['description'] ?? '',
                'changelog'   => $manifest['changelog'] ?? '',
            ],
        ];
    }

    /**
     * Varsity Jackets releases are intentionally permitted to auto-install.
     */
    public static function enable_github_auto_update( bool $update, $item ): bool {
        $plugin = is_object( $item ) && isset( $item->plugin ) ? (string) $item->plugin : '';
        return self::plugin_basename() === $plugin ? true : $update;
    }

    public static function clear_update_cache_after_upgrade( $upgrader, array $hook_extra ): void {
        if ( 'plugin' !== ( $hook_extra['type'] ?? '' ) ) {
            return;
        }

        $plugins = $hook_extra['plugins'] ?? [];
        if ( isset( $hook_extra['plugin'] ) ) {
            $plugins[] = $hook_extra['plugin'];
        }

        if ( in_array( self::plugin_basename(), (array) $plugins, true ) ) {
            self::clear_cache();
        }
    }
}
