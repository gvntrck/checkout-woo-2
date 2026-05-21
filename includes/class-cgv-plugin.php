<?php
/**
 * Bootstrap class for Checkout GVNTRCK.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once CGV_DIR . 'includes/class-cgv-fields.php';
require_once CGV_DIR . 'includes/class-cgv-shortcode.php';
require_once CGV_DIR . 'includes/class-cgv-checkout.php';
require_once CGV_DIR . 'includes/class-cgv-admin.php';

/**
 * Main plugin class.
 */
class CGV_Plugin {

    /**
     * @var CGV_Plugin
     */
    protected static $instance = null;

    /**
     * Singleton.
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        CGV_Fields::init();
        CGV_Fields::maybe_seed_default_fields();
        CGV_Shortcode::init();
        CGV_Checkout::init();

        if ( is_admin() ) {
            CGV_Admin::init();
        }
    }

    /**
     * Activation handler.
     */
    public static function activate() {
        if ( false === get_option( 'cgv_settings', false ) ) {
            update_option( 'cgv_settings', self::default_settings() );
        }
        if ( false === get_option( 'cgv_fields', false ) ) {
            update_option( 'cgv_fields', CGV_Fields::default_fields() );
        }
        update_option( 'cgv_fields_default_version', CGV_Fields::DEFAULT_FIELDS_VERSION );
    }

    /**
     * Default settings.
     */
    public static function default_settings() {
        return [
            'product_id'        => 0,
            'thank_you_url'     => '',
            'empty_cart_home_url' => home_url( '/' ),
            'show_coupon_field' => 0,
            'gateway_card'      => '',
            'gateway_pix'       => '',
            'gateway_boleto'    => '',
            'tab_card_label'    => 'Cartão',
            'tab_pix_label'     => 'PIX',
            'tab_boleto_label'  => 'Boleto',
            'tab_card_enabled'  => 1,
            'tab_pix_enabled'   => 1,
            'tab_boleto_enabled' => 1,
            'header_title'      => '',
            'header_icon'       => 'shopping_cart',
            'ident_title'       => '1. Identificação',
            'ident_icon'        => 'person',
            'payment_title'     => '2. Forma de Pagamento',
            'payment_icon'      => 'payments',
            'button_text'       => 'Finalizar Inscrição Agora',
            'button_icon'       => 'lock',
            'summary_label'     => 'Valor da Mentoria',
            'total_label'       => 'Total',
            'badge_1_icon'      => 'verified_user',
            'badge_1_text'      => 'Compra Segura',
            'badge_2_icon'      => 'bolt',
            'badge_2_text'      => 'Acesso Imediato',
            'badge_3_icon'      => 'verified',
            'badge_3_text'      => '7 Dias de Garantia',
            'primary_color'     => '#131b2e',
            'accent_color'      => '#006c49',
            'card_bg_color'     => '#ffffff',
            'enable_pulse'      => 1,
            'card_max_width_single'  => 480,
            'card_max_width_general' => 680,

            // Borda e sombra — single
            'card_border_enabled_single' => 1,
            'card_border_width_single'   => 1,
            'card_border_color_single'   => '#c6c6cd',
            'card_radius_single'         => 12,
            'card_shadow_enabled_single' => 1,

            // Borda e sombra — geral
            'card_border_enabled_general' => 1,
            'card_border_width_general'   => 1,
            'card_border_color_general'   => '#c6c6cd',
            'card_radius_general'         => 12,
            'card_shadow_enabled_general' => 1,

            // Layout Split (Duas Colunas)
            'split_layout'                => 0,
            'checkout_logo'               => '',
        ];
    }

    /**
     * Get settings merged with defaults.
     */
    public static function get_settings() {
        $saved = get_option( 'cgv_settings', [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return wp_parse_args( $saved, self::default_settings() );
    }
}
