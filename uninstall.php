<?php
/**
 * Uninstall handler — removes plugin options.
 *
 * @package CheckoutGVNTRCK
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'cgv_settings' );
delete_option( 'cgv_fields' );
delete_option( 'cgv_fields_default_version' );
