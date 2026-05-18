<?php
/**
 * Front-end checkout card template.
 *
 * @package CheckoutGVNTRCK
 *
 * @var array $settings
 * @var array $fields
 * @var string $checkout_mode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = isset( $settings ) ? $settings : CGV_Plugin::get_settings();
$fields   = isset( $fields ) ? $fields : CGV_Fields::get_fields();
$checkout_mode = isset( $checkout_mode ) ? $checkout_mode : 'single';
$is_general_checkout = 'general' === $checkout_mode;

$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

// Build tabs (only ones whose mapped gateway is currently available).
$raw_tabs = [
    'card'   => [
        'enabled' => ! empty( $settings['tab_card_enabled'] ),
        'label'   => $settings['tab_card_label'],
        'gateway' => $settings['gateway_card'],
    ],
    'pix'    => [
        'enabled' => ! empty( $settings['tab_pix_enabled'] ),
        'label'   => $settings['tab_pix_label'],
        'gateway' => $settings['gateway_pix'],
    ],
    'boleto' => [
        'enabled' => ! empty( $settings['tab_boleto_enabled'] ),
        'label'   => $settings['tab_boleto_label'],
        'gateway' => $settings['gateway_boleto'],
    ],
];

$tabs = [];
foreach ( $raw_tabs as $key => $t ) {
    if ( $t['enabled'] && ! empty( $t['gateway'] ) && isset( $available_gateways[ $t['gateway'] ] ) ) {
        $tabs[ $key ] = $t;
    }
}

// Fallback: if no tab matches but there are available gateways, show them all under a single "Pagamento" tab.
$fallback_gateways = [];
if ( empty( $tabs ) && ! empty( $available_gateways ) ) {
    $fallback_gateways = $available_gateways;
}

$tab_keys   = array_keys( $tabs );
$active_tab = $tab_keys[0] ?? '';
$active_gw  = $active_tab ? $tabs[ $active_tab ]['gateway'] : ( $fallback_gateways ? array_key_first( $fallback_gateways ) : '' );

$cart      = WC()->cart;
$cart_total = $cart ? wc_price( $cart->get_total( 'edit' ) ) : '';
$cart_subtotal = $cart ? wc_price( $cart->get_subtotal() ) : '';
$has_items = $cart && $cart->get_cart_contents_count() > 0;
$applied_coupons = $cart ? $cart->get_applied_coupons() : [];

$root_style = sprintf(
    '--cgv-primary:%s;--cgv-accent:%s;--cgv-card-bg:%s;',
    esc_attr( $settings['primary_color'] ),
    esc_attr( $settings['accent_color'] ),
    esc_attr( $settings['card_bg_color'] )
);
$root_classes = 'cgv-card';
if ( ! empty( $settings['enable_pulse'] ) ) {
    $root_classes .= ' cgv-pulse';
}
$root_classes .= $is_general_checkout ? ' cgv-card-general' : ' cgv-card-single';
?>
<div class="<?php echo esc_attr( $root_classes ); ?>" data-cgv-mode="<?php echo esc_attr( $checkout_mode ); ?>" style="<?php echo $root_style; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
    <?php if ( ! $has_items ) : ?>
        <div class="cgv-empty-warning">
            <p>
                <?php
                echo esc_html(
                    $is_general_checkout
                        ? __( 'Seu carrinho está vazio.', 'checkout-gvntrck' )
                        : __( 'Nenhum produto disponível para checkout. Configure um produto nas opções do plugin.', 'checkout-gvntrck' )
                );
                ?>
            </p>
            <?php if ( $is_general_checkout ) : ?>
                <a class="cgv-empty-button" href="<?php echo esc_url( $settings['empty_cart_home_url'] ?: home_url( '/' ) ); ?>">
                    <?php esc_html_e( 'Ir para a home', 'checkout-gvntrck' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout cgv-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="cgv_checkout" value="1" />
        <input type="hidden" name="cgv_checkout_mode" value="<?php echo esc_attr( $checkout_mode ); ?>" />
        <input type="hidden" name="woocommerce-process-checkout-nonce" value="<?php echo esc_attr( wp_create_nonce( 'woocommerce-process_checkout' ) ); ?>" />
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wc_get_checkout_url() ); ?>" />
        <input type="hidden" name="terms" value="1" />
        <input type="hidden" name="terms-field" value="1" />
        <input type="hidden" name="ship_to_different_address" value="0" />

        <?php if ( ! empty( $settings['header_title'] ) ) : ?>
            <div class="cgv-card-header">
                <span class="material-symbols-outlined cgv-icon"><?php echo esc_html( $settings['header_icon'] ); ?></span>
                <h2 class="cgv-card-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
            </div>
        <?php endif; ?>

        <!-- Section 1: Identification -->
        <section class="cgv-section">
            <header class="cgv-section-header">
                <span class="material-symbols-outlined cgv-icon"><?php echo esc_html( $settings['ident_icon'] ); ?></span>
                <h2 class="cgv-section-title"><?php echo esc_html( $settings['ident_title'] ); ?></h2>
            </header>

            <div class="cgv-fields-grid">
                <?php foreach ( $fields as $f ) :
                    if ( empty( $f['enabled'] ) ) {
                        continue;
                    }
                    $name = ( 'full_name' === $f['id'] ) ? 'cgv_full_name' : $f['billing_key'];
                    $span = ( ( $f['span'] ?? 'full' ) === 'half' ) ? 'half' : 'full';
                    $numeric_input_ids = [ 'cpf', 'cnpj', 'birthdate', 'cellphone' ];
                    ?>
                    <div class="cgv-field cgv-field-<?php echo esc_attr( $span ); ?>">
                        <label class="cgv-label" for="cgv-field-<?php echo esc_attr( $f['id'] ); ?>">
                            <?php echo esc_html( $f['label'] ); ?>
                            <?php if ( ! empty( $f['required'] ) ) : ?><span class="cgv-req" aria-hidden="true">*</span><?php endif; ?>
                        </label>
                        <?php if ( 'select' === ( $f['type'] ?? '' ) ) : ?>
                            <select
                                id="cgv-field-<?php echo esc_attr( $f['id'] ); ?>"
                                class="cgv-input"
                                name="<?php echo esc_attr( $name ); ?>"
                                <?php if ( ! empty( $f['required'] ) ) : ?>required<?php endif; ?>
                            >
                                <?php foreach ( CGV_Fields::sanitize_options( $f['options'] ?? [] ) as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input
                                id="cgv-field-<?php echo esc_attr( $f['id'] ); ?>"
                                class="cgv-input"
                                type="<?php echo esc_attr( $f['type'] ); ?>"
                                name="<?php echo esc_attr( $name ); ?>"
                                placeholder="<?php echo esc_attr( $f['placeholder'] ?? '' ); ?>"
                                <?php if ( in_array( $f['id'], $numeric_input_ids, true ) ) : ?>inputmode="numeric"<?php endif; ?>
                                <?php if ( ! empty( $f['autocomplete'] ) ) : ?>autocomplete="<?php echo esc_attr( $f['autocomplete'] ); ?>"<?php endif; ?>
                                <?php if ( ! empty( $f['required'] ) ) : ?>required<?php endif; ?>
                            />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 2: Payment -->
        <section class="cgv-section">
            <header class="cgv-section-header">
                <span class="material-symbols-outlined cgv-icon"><?php echo esc_html( $settings['payment_icon'] ); ?></span>
                <h2 class="cgv-section-title"><?php echo esc_html( $settings['payment_title'] ); ?></h2>
            </header>

            <?php if ( ! empty( $tabs ) ) : ?>
                <div class="cgv-tabs" role="tablist">
                    <?php foreach ( $tabs as $key => $t ) : ?>
                        <button
                            type="button"
                            class="cgv-tab<?php echo $key === $active_tab ? ' is-active' : ''; ?>"
                            data-tab="<?php echo esc_attr( $key ); ?>"
                            data-gateway="<?php echo esc_attr( $t['gateway'] ); ?>"
                            role="tab"
                            aria-selected="<?php echo $key === $active_tab ? 'true' : 'false'; ?>"
                        ><?php echo esc_html( $t['label'] ); ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Standard WooCommerce payment block (visually re-styled, controls the real payment_method radios). -->
            <div id="payment" class="cgv-payment">
                <?php
                $methods_to_render = ! empty( $tabs )
                    ? array_intersect_key( $available_gateways, array_flip( wp_list_pluck( $tabs, 'gateway' ) ) )
                    : $fallback_gateways;
                if ( ! empty( $methods_to_render ) ) :
                    ?>
                    <ul class="wc_payment_methods payment_methods methods">
                        <?php foreach ( $methods_to_render as $gateway ) :
                            $is_active = ( $gateway->id === $active_gw );
                            ?>
                            <li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
                                <input
                                    id="payment_method_<?php echo esc_attr( $gateway->id ); ?>"
                                    type="radio"
                                    class="input-radio"
                                    name="payment_method"
                                    value="<?php echo esc_attr( $gateway->id ); ?>"
                                    <?php checked( $is_active, true ); ?>
                                    data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"
                                />
                                <label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
                                    <?php echo wp_kses_post( $gateway->get_title() ); ?>
                                    <?php echo $gateway->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </label>
                                <?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
                                    <div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php echo $is_active ? '' : 'style="display:none"'; ?>>
                                        <?php $gateway->payment_fields(); ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="cgv-no-gateway">
                        <?php esc_html_e( 'Nenhum gateway de pagamento disponível. Configure os gateways na tela de admin do plugin.', 'checkout-gvntrck' ); ?>
                    </div>
                <?php endif; ?>

                <!-- Order summary -->
                <div class="cgv-summary">
                    <?php if ( $is_general_checkout ) : ?>
                        <div class="cgv-cart-items">
                            <?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) :
                                $product = $cart_item['data'] ?? null;
                                if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
                                    continue;
                                }
                                $product_permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
                                ?>
                                <div class="cgv-cart-item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                    <div class="cgv-cart-item-main">
                                        <span class="cgv-cart-item-name">
                                            <?php if ( $product_permalink ) : ?>
                                                <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html( $product->get_name() ); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="cgv-cart-item-price"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?></span>
                                    </div>
                                    <div class="cgv-cart-item-actions">
                                        <div class="cgv-qty-control" aria-label="<?php esc_attr_e( 'Quantidade', 'checkout-gvntrck' ); ?>">
                                            <button type="button" class="cgv-qty-btn" data-cgv-cart-action="decrease" aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'checkout-gvntrck' ); ?>">-</button>
                                            <input
                                                class="cgv-qty-input"
                                                type="number"
                                                min="0"
                                                step="1"
                                                inputmode="numeric"
                                                value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
                                                aria-label="<?php esc_attr_e( 'Quantidade', 'checkout-gvntrck' ); ?>"
                                            />
                                            <button type="button" class="cgv-qty-btn" data-cgv-cart-action="increase" aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'checkout-gvntrck' ); ?>">+</button>
                                        </div>
                                        <button type="button" class="cgv-remove-item" data-cgv-cart-action="remove_item">
                                            <?php esc_html_e( 'Remover', 'checkout-gvntrck' ); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ( ! empty( $settings['show_coupon_field'] ) ) : ?>
                            <div class="cgv-coupon">
                                <label class="cgv-label" for="cgv-coupon-code"><?php esc_html_e( 'Cupom de desconto', 'checkout-gvntrck' ); ?></label>
                                <div class="cgv-coupon-row">
                                    <input id="cgv-coupon-code" class="cgv-input" type="text" autocomplete="off" placeholder="<?php esc_attr_e( 'Digite seu cupom', 'checkout-gvntrck' ); ?>" />
                                    <button type="button" class="cgv-coupon-apply" data-cgv-cart-action="apply_coupon"><?php esc_html_e( 'Aplicar', 'checkout-gvntrck' ); ?></button>
                                </div>
                                <?php if ( ! empty( $applied_coupons ) ) : ?>
                                    <div class="cgv-applied-coupons">
                                        <?php foreach ( $applied_coupons as $coupon_code ) : ?>
                                            <button type="button" class="cgv-coupon-chip" data-cgv-cart-action="remove_coupon" data-coupon-code="<?php echo esc_attr( $coupon_code ); ?>">
                                                <?php echo esc_html( $coupon_code ); ?> <span aria-hidden="true">x</span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="cgv-summary-row">
                        <span><?php echo esc_html( $settings['summary_label'] ); ?></span>
                        <span><?php echo wp_kses_post( $cart_subtotal ); ?></span>
                    </div>
                    <?php if ( $is_general_checkout && $cart->get_discount_total() > 0 ) : ?>
                        <div class="cgv-summary-row">
                            <span><?php esc_html_e( 'Desconto', 'checkout-gvntrck' ); ?></span>
                            <span>-<?php echo wp_kses_post( wc_price( $cart->get_discount_total() ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="cgv-summary-row cgv-summary-total">
                        <span><?php echo esc_html( $settings['total_label'] ); ?></span>
                        <strong><?php echo wp_kses_post( $cart_total ); ?></strong>
                    </div>
                </div>

                <!-- Place order -->
                <div class="form-row place-order cgv-submit-wrap">
                    <noscript>
                        <em><?php esc_html_e( 'Habilite o JavaScript para finalizar o pagamento.', 'checkout-gvntrck' ); ?></em>
                    </noscript>
                    <button
                        type="submit"
                        class="button alt cgv-submit"
                        name="woocommerce_checkout_place_order"
                        id="place_order"
                        value="<?php echo esc_attr( $settings['button_text'] ); ?>"
                        data-value="<?php echo esc_attr( $settings['button_text'] ); ?>"
                    >
                        <span class="material-symbols-outlined cgv-icon"><?php echo esc_html( $settings['button_icon'] ); ?></span>
                        <span class="cgv-submit-label"><?php echo esc_html( $settings['button_text'] ); ?></span>
                    </button>
                    <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce', true, true ); ?>
                </div>
            </div>
        </section>

        <!-- Trust badges -->
        <div class="cgv-badges">
            <?php for ( $i = 1; $i <= 3; $i++ ) :
                $icon = $settings[ "badge_{$i}_icon" ] ?? '';
                $text = $settings[ "badge_{$i}_text" ] ?? '';
                if ( '' === $icon && '' === $text ) {
                    continue;
                }
                ?>
                <div class="cgv-badge">
                    <span class="material-symbols-outlined cgv-icon"><?php echo esc_html( $icon ); ?></span>
                    <span class="cgv-badge-text"><?php echo esc_html( $text ); ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </form>
    <?php endif; ?>
</div>
