<?php
/**
 * Plugin Name: Checkout GVNTRCK
 * Plugin URI:  https://projetoalfa.org
 * Description: Checkout personalizado de alta conversão para WooCommerce. Renderiza um card de checkout via shortcode [checkout-gvntrck], totalmente compatível com gateways de pagamento.
 * Version:     1.1.5
 * Author:      Giovani Tureck
 * Author URI:  https://projetoalfa.org
 * Text Domain: checkout-gvntrck
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.4
 *
 * @package CheckoutGVNTRCK
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CGV_VERSION', '1.1.5');
define('CGV_FILE', __FILE__);
define('CGV_DIR', plugin_dir_path(__FILE__));
define('CGV_URL', plugin_dir_url(__FILE__));
define('CGV_BASENAME', plugin_basename(__FILE__));

require_once CGV_DIR . 'includes/class-cgv-plugin.php';

/**
 * Bootstrap.
 */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('checkout-gvntrck', false, dirname(CGV_BASENAME) . '/languages');

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Checkout GVNTRCK</strong>: ' .
                esc_html__('WooCommerce precisa estar ativo para o plugin funcionar.', 'checkout-gvntrck') .
                '</p></div>';
        });
        return;
    }

    CGV_Plugin::instance();
});

register_activation_hook(__FILE__, ['CGV_Plugin', 'activate']);

// Declara compatibilidade com HPOS (High-Performance Order Storage) do WooCommerce.
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
