<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header( 'shop' );

$template_id = class_exists( 'ASEVJ_WooCommerce' ) ? ASEVJ_WooCommerce::product_template_id() : 0;
$template = $template_id ? get_post( $template_id ) : null;

?>
<main id="primary" class="site-main asevj-varsity-product-template">
    <?php
    if ( $template && 'asevj_template' === $template->post_type ) {
        echo do_blocks( $template->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        echo ASEVJ_Product_Page::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>
</main>
<?php
get_footer( 'shop' );
