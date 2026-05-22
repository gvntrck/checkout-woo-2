<?php
/**
 * WooCommerce checkout integration.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGV_Checkout {

    public static function init() {
        add_filter( 'woocommerce_checkout_fields', [ __CLASS__, 'relax_required_fields' ], 999 );
        add_filter( 'woocommerce_checkout_posted_data', [ __CLASS__, 'inject_defaults' ], 5 );
        add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate_configured_fields' ] );
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'save_configured_fields' ], 20, 2 );
        add_filter( 'woocommerce_get_checkout_order_received_url', [ __CLASS__, 'override_thankyou' ], 999, 2 );
        add_filter( 'woocommerce_available_payment_gateways', [ __CLASS__, 'filter_available_gateways' ], 99 );
    }

    /**
     * Limit available payment gateways to those mapped in plugin settings,
     * so AJAX fragment refreshes (update_order_review) don't inject other
     * gateways into our re-styled payment block.
     */
    public static function filter_available_gateways( $gateways ) {
        if ( ! self::is_cgv_request() ) {
            return $gateways;
        }
        $settings = CGV_Plugin::get_settings();
        $allowed  = array_filter( [
            $settings['gateway_card'],
            $settings['gateway_pix'],
            $settings['gateway_boleto'],
        ] );
        if ( empty( $allowed ) ) {
            return $gateways;
        }
        $filtered = array_intersect_key( $gateways, array_flip( $allowed ) );
        return ! empty( $filtered ) ? $filtered : $gateways;
    }

    /**
     * Detect whether the current request comes from our custom checkout card.
     */
    protected static function is_cgv_request() {
        return isset( $_POST['cgv_checkout'] ) && '1' === (string) $_POST['cgv_checkout']; // phpcs:ignore WordPress.Security.NonceVerification
    }

    /**
     * Make non-essential billing fields optional when the request is from our card.
     */
    public static function relax_required_fields( $fields ) {
        if ( ! self::is_cgv_request() ) {
            return $fields;
        }
        $relax = [
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_phone',
            'billing_company',
            'billing_first_name',
            'billing_last_name',
        ];
        foreach ( $relax as $key ) {
            if ( isset( $fields['billing'][ $key ] ) ) {
                $fields['billing'][ $key ]['required'] = false;
            }
        }
        if ( isset( $fields['shipping'] ) && is_array( $fields['shipping'] ) ) {
            foreach ( $fields['shipping'] as $k => $f ) {
                $fields['shipping'][ $k ]['required'] = false;
            }
        }
        return $fields;
    }

    /**
     * Inject sensible defaults for hidden billing fields and split full name.
     */
    public static function inject_defaults( $data ) {
        if ( ! self::is_cgv_request() ) {
            return $data;
        }

        // Split "Nome Completo" into first/last.
        $full_name = '';
        if ( ! empty( $_POST['cgv_full_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $full_name = sanitize_text_field( wp_unslash( $_POST['cgv_full_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        } elseif ( ! empty( $_POST['billing_full_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $full_name = sanitize_text_field( wp_unslash( $_POST['billing_full_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }
        if ( $full_name ) {
            $parts = preg_split( '/\s+/', trim( $full_name ), 2 );
            $data['billing_first_name'] = $parts[0] ?? $full_name;
            $data['billing_last_name']  = isset( $parts[1] ) && '' !== $parts[1] ? $parts[1] : '.';
        } else {
            if ( empty( $data['billing_first_name'] ) ) {
                $data['billing_first_name'] = 'Cliente';
            }
            if ( empty( $data['billing_last_name'] ) ) {
                $data['billing_last_name'] = '.';
            }
        }

        $defaults = [
            'billing_address_1' => 'N/A',
            'billing_city'      => 'N/A',
            'billing_postcode'  => '00000-000',
            'billing_country'   => 'BR',
            'billing_state'     => 'SP',
            'billing_phone'     => '00000000000',
        ];
        foreach ( $defaults as $k => $v ) {
            if ( empty( $data[ $k ] ) ) {
                $data[ $k ] = $v;
            }
        }
        return $data;
    }

    /**
     * Validate configured required fields that are not native WooCommerce fields.
     */
    public static function validate_configured_fields() {
        if ( ! self::is_cgv_request() ) {
            return;
        }

        foreach ( CGV_Fields::get_fields() as $field ) {
            if ( empty( $field['enabled'] ) || ! self::field_applies_to_person_type( $field ) || empty( $field['required'] ) || 'full_name' === ( $field['id'] ?? '' ) ) {
                continue;
            }
            $billing_key = sanitize_key( $field['billing_key'] ?? '' );
            if ( '' === $billing_key || ! empty( $_POST[ $billing_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                continue;
            }
            wc_add_notice(
                sprintf(
                    /* translators: %s: field label */
                    __( '%s é um campo obrigatório.', 'checkout-gvntrck' ),
                    $field['label'] ?? $billing_key
                ),
                'error'
            );
        }
    }

    /**
     * Save configured checkout fields to order meta and customer user meta.
     */
    public static function save_configured_fields( $order, $data ) {
        if ( ! self::is_cgv_request() || ! $order instanceof WC_Order ) {
            return;
        }

        foreach ( CGV_Fields::get_fields() as $field ) {
            if ( empty( $field['enabled'] ) || ! self::field_applies_to_person_type( $field ) || 'full_name' === ( $field['id'] ?? '' ) ) {
                continue;
            }

            $billing_key = sanitize_key( $field['billing_key'] ?? '' );
            if ( '' === $billing_key || ! isset( $_POST[ $billing_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                continue;
            }

            $value = self::sanitize_field_value( wp_unslash( $_POST[ $billing_key ] ), $field ); // phpcs:ignore WordPress.Security.NonceVerification
            if ( '' === $value ) {
                continue;
            }

            $order->update_meta_data( '_' . $billing_key, $value );

            $user_id = $order->get_customer_id();
            if ( $user_id ) {
                update_user_meta( $user_id, $billing_key, $value );
            }
        }
    }

    /**
     * Check CPF/CNPJ fields against the selected person type.
     */
    protected static function field_applies_to_person_type( $field ) {
        $field_id = sanitize_key( $field['id'] ?? '' );
        if ( ! in_array( $field_id, [ 'cpf', 'cnpj' ], true ) ) {
            return true;
        }

        $person_type = self::get_posted_person_type();
        if ( '' === $person_type ) {
            return true;
        }

        return ( 'cpf' === $field_id && '1' === $person_type ) || ( 'cnpj' === $field_id && '2' === $person_type );
    }

    /**
     * Get the posted Brazilian person type value.
     */
    protected static function get_posted_person_type() {
        if ( empty( $_POST['billing_persontype'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return '';
        }

        return sanitize_text_field( wp_unslash( $_POST['billing_persontype'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
    }

    /**
     * Sanitize a configured field value according to field type.
     */
    protected static function sanitize_field_value( $value, $field ) {
        $type = $field['type'] ?? 'text';
        if ( is_array( $value ) ) {
            return '';
        }

        $value = (string) $value;
        if ( in_array( $type, [ 'tel', 'number' ], true ) ) {
            return preg_replace( '/[^0-9()+.\-\s\/]/', '', $value );
        }
        if ( 'email' === $type ) {
            return sanitize_email( $value );
        }
        return sanitize_text_field( $value );
    }

    /**
     * Replace the WooCommerce thank-you URL with the user-defined page.
     */
    public static function override_thankyou( $url, $order ) {
        $settings = CGV_Plugin::get_settings();
        if ( empty( $settings['thank_you_url'] ) || ! $order ) {
            return $url;
        }
        return add_query_arg( [
            'order_id' => $order->get_id(),
            'key'      => $order->get_order_key(),
        ], $settings['thank_you_url'] );
    }
}
