<?php
/**
 * Remove plugin settings on uninstall. Order data is left untouched.
 *
 * @package Raqam_Pay
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'woocommerce_raqpay_settings' );
