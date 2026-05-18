<?php
/**
 * Shortcode renderer.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGV_Shortcode {

    public static function init() {
        add_shortcode( 'checkout-gvntrck', [ __CLASS__, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
    }

    /**
     * Register frontend assets (only enqueued when shortcode runs).
     */
    public static function register_assets() {
        wp_register_style(
            'cgv-fonts',
            'https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap',
            [],
            CGV_VERSION
        );
        wp_register_style(
            'cgv-checkout',
            CGV_URL . 'assets/css/checkout.css',
            [ 'cgv-fonts' ],
            CGV_VERSION
        );
        wp_register_script(
            'cgv-checkout',
            CGV_URL . 'assets/js/checkout.js',
            [ 'jquery', 'wc-checkout' ],
            CGV_VERSION,
            true
        );
    }

    /**
     * Render the shortcode.
     */
    public static function render( $atts = [] ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return '';
        }
        if ( is_admin() ) {
            return '';
        }

        // Initialize WC session/cart on frontend if needed.
        if ( null === WC()->session ) {
            $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
            WC()->session = new $session_class();
            WC()->session->init();
        }
        if ( null === WC()->customer ) {
            WC()->customer = new WC_Customer( get_current_user_id(), true );
        }
        if ( null === WC()->cart ) {
            WC()->cart = new WC_Cart();
        }

        wp_enqueue_style( 'cgv-checkout' );
        wp_enqueue_script( 'cgv-checkout' );

        // Make sure the WC frontend checkout script can run outside the canonical checkout page.
        self::ensure_checkout_params();

        $settings = CGV_Plugin::get_settings();
        $fields   = CGV_Fields::get_fields();

        // Ensure the configured product is in the cart for this checkout.
        $product_id = absint( $settings['product_id'] );
        if ( $product_id ) {
            self::ensure_cart( $product_id );
        }

        // Localize plugin-specific data.
        wp_localize_script( 'cgv-checkout', 'CGV', [
            'thank_you_url' => esc_url_raw( $settings['thank_you_url'] ),
            'gateway_map'   => [
                'card'   => $settings['gateway_card'],
                'pix'    => $settings['gateway_pix'],
                'boleto' => $settings['gateway_boleto'],
            ],
            'i18n'          => [
                'generic_error' => __( 'Erro ao processar o pagamento. Tente novamente.', 'checkout-gvntrck' ),
            ],
        ] );

        ob_start();
        include CGV_DIR . 'templates/checkout-card.php';
        return ob_get_clean();
    }

    /**
     * Localize wc_checkout_params manually so wc-checkout.js works in any page.
     */
    protected static function ensure_checkout_params() {
        global $wp_scripts;
        if ( ! isset( $wp_scripts->registered['wc-checkout'] ) ) {
            return;
        }
        $registered = $wp_scripts->registered['wc-checkout'];
        if ( ! empty( $registered->extra['data'] ) && false !== strpos( $registered->extra['data'], 'wc_checkout_params' ) ) {
            return;
        }

        wp_localize_script( 'wc-checkout', 'wc_checkout_params', [
            'ajax_url'                  => WC()->ajax_url(),
            'wc_ajax_url'               => WC_AJAX::get_endpoint( '%%endpoint%%' ),
            'update_order_review_nonce' => wp_create_nonce( 'update-order-review' ),
            'apply_coupon_nonce'        => wp_create_nonce( 'apply-coupon' ),
            'remove_coupon_nonce'       => wp_create_nonce( 'remove-coupon' ),
            'option_guest_checkout'     => get_option( 'woocommerce_enable_guest_checkout', 'yes' ),
            'checkout_url'              => WC_AJAX::get_endpoint( 'checkout' ),
            'is_checkout'               => 1,
            'debug_mode'                => false,
            'i18n_checkout_error'       => esc_attr__( 'Erro ao processar o pagamento. Tente novamente.', 'checkout-gvntrck' ),
        ] );
    }

    /**
     * Ensure the configured product is the only item in the cart.
     */
    protected static function ensure_cart( $product_id ) {
        if ( ! WC()->cart ) {
            return;
        }
        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_purchasable() ) {
            return;
        }

        $found = false;
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( (int) $item['product_id'] === $product_id ) {
                $found = true;
                break;
            }
        }
        if ( ! $found ) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart( $product_id );
        }
        WC()->cart->calculate_totals();
    }
}
