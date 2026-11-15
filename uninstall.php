<?php
/**
 * Remove plugin settings on uninstall. Order data is left untouched.
 *
 * @package Nauman_Manual_Payments
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'woocommerce_nmpay_settings' );
