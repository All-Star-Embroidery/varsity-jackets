<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Post_Types {
    private static ?ASEVJ_Post_Types $instance = null;

    public static function instance(): ASEVJ_Post_Types {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ __CLASS__, 'register_post_types' ] );
        add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_block_editor_for_data_types' ], 10, 2 );
    }

    public static function register_post_types(): void {
        register_post_type( 'asevj_school', [
            'labels' => [
                'name'               => 'Schools',
                'singular_name'      => 'School',
                'add_new'            => 'Add School',
                'add_new_item'       => 'Add New School',
                'edit_item'          => 'Edit School',
                'new_item'           => 'New School',
                'view_item'          => 'Preview School',
                'search_items'       => 'Search Schools',
                'not_found'          => 'No schools found',
                'menu_name'          => 'Schools',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'asevj-dashboard',
            'show_in_rest'        => true,
            'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
            'hierarchical'        => false,
            'menu_position'       => 58,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ] );

        register_post_type( 'asevj_style', [
            'labels' => [
                'name'               => 'Jacket Styles',
                'singular_name'      => 'Jacket Style',
                'add_new'            => 'Add Style',
                'add_new_item'       => 'Add New Jacket Style',
                'edit_item'          => 'Edit Jacket Style',
                'new_item'           => 'New Jacket Style',
                'search_items'       => 'Search Jacket Styles',
                'not_found'          => 'No jacket styles found',
                'menu_name'          => 'Jacket Styles',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'asevj-dashboard',
            'show_in_rest'        => true,
            'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
            'hierarchical'        => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ] );
    }

    public function disable_block_editor_for_data_types( bool $use_block_editor, string $post_type ): bool {
        if ( in_array( $post_type, [ 'asevj_school', 'asevj_style' ], true ) ) {
            return false;
        }
        return $use_block_editor;
    }
}
