<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Keeps mascot artwork separate from the school logo and mascot-name text.
 */
final class ASEVJ_Mascot {
    private static ?ASEVJ_Mascot $instance = null;

    public static function instance(): ASEVJ_Mascot {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes_asevj_school', [ $this, 'add_meta_box' ] );
        add_action( 'save_post_asevj_school', [ $this, 'save' ], 20 );
    }

    public function add_meta_box( WP_Post $post ): void {
        add_meta_box(
            'asevj_school_mascot_image',
            'Mascot Image',
            [ $this, 'render' ],
            'asevj_school',
            'normal',
            'default'
        );
    }

    public function render( WP_Post $post ): void {
        wp_nonce_field( 'asevj_save_mascot_image', 'asevj_mascot_image_nonce' );

        $image_id  = absint( get_post_meta( $post->ID, '_asevj_mascot_image_id', true ) );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

        echo '<div class="asevj-media-field">';
        echo '<strong>School Mascot Artwork</strong>';
        echo '<p class="description">Optional. This is separate from both the School Logo and the Mascot name text. Example: a bulldog head graphic while the Mascot field says “Bulldogs”.</p>';
        echo '<div class="asevj-media-preview">' . ( $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span>No mascot image selected</span>' ) . '</div>';
        echo '<input type="hidden" class="asevj-media-id" name="asevj_mascot_image_id" value="' . esc_attr( $image_id ) . '">';
        echo '<button type="button" class="button asevj-pick-media">Choose Mascot Image</button> ';
        echo '<button type="button" class="button-link-delete asevj-remove-media">Remove</button>';
        echo '</div>';
    }

    public function save( int $post_id ): void {
        if ( ! isset( $_POST['asevj_mascot_image_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['asevj_mascot_image_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'asevj_save_mascot_image' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $image_id = isset( $_POST['asevj_mascot_image_id'] ) ? absint( $_POST['asevj_mascot_image_id'] ) : 0;
        update_post_meta( $post_id, '_asevj_mascot_image_id', $image_id );
    }
}
