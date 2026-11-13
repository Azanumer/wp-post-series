<?php
/**
 * Uninstall handler for Post Series.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'wpseries_settings' );
