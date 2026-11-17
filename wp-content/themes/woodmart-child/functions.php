<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'bootstrap','woodmart-style','wd-blog-base','wd-wp-gutenberg','wd-woocommerce-base','wd-mod-star-rating','wd-woo-el-track-order','wd-woocommerce-block-notices','wd-woo-gutenberg' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'chld_thm_cfg_parent' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );

// END ENQUEUE PARENT ACTION
// Disable forced SSL redirects on localhost only (allows live site to use HTTPS securely)
if ( isset( $_SERVER['HTTP_HOST'] ) && ( strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false || strpos( $_SERVER['HTTP_HOST'], '127.0.0.1' ) !== false ) ) {
	add_filter( 'woocommerce_force_ssl_checkout', '__return_false' );
	add_filter( 'wc_checkout_is_https', '__return_false' );
}


// Prevent failing Woodmart auto-update check (avoids "Download failed. Forbidden")
add_filter( 'site_transient_update_themes', function( $transient ) {
	if ( is_object( $transient ) && isset( $transient->response['woodmart'] ) ) {
		unset( $transient->response['woodmart'] );
	}
	return $transient;
}, 999 );
add_filter( 'pre_set_site_transient_update_themes', function( $transient ) {
	if ( is_object( $transient ) && isset( $transient->response['woodmart'] ) ) {
		unset( $transient->response['woodmart'] );
	}
	return $transient;
}, 999 );


