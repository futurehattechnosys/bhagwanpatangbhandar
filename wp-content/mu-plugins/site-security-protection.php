<?php
/**
 * Plugin Name: Site Security & Anti-Scammer Protection
 * Description: Hardens WordPress by completely disabling comments, blocking unauthorized post creation, disabling XML-RPC, preventing user enumeration, and securing against scammers.
 * Version: 1.0.0
 * Author: Security Hardening
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   1. COMPLETE COMMENT SHUTDOWN
   ========================================================================== */

// Close comments and pings globally on frontend
add_filter( 'comments_open', '__return_false', 999 );
add_filter( 'pings_open', '__return_false', 999 );
add_filter( 'comments_array', '__return_empty_array', 999 );

// Block direct comment submissions via wp-comments-post.php
add_action( 'pre_comment_on_post', function () {
	wp_die( esc_html__( 'Comments are closed on this website.', 'bhagwandas' ), '', array( 'response' => 403 ) );
} );

// Hide existing comments and comment form in templates
add_filter( 'comments_template', function () {
	return __DIR__ . '/empty-comments.php';
} );

// Remove comment support from all post types
add_action( 'init', function () {
	$post_types = get_post_types();
	foreach ( $post_types as $pt ) {
		if ( post_type_supports( $pt, 'comments' ) ) {
			remove_post_type_support( $pt, 'comments' );
			remove_post_type_support( $pt, 'trackbacks' );
		}
	}
} );

// Remove Comments menu from Admin Dashboard
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
} );

// Remove Comments from Admin Bar
add_action( 'wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	if ( is_object( $wp_admin_bar ) ) {
		$wp_admin_bar->remove_menu( 'comments' );
	}
} );


/* ==========================================================================
   2. STRICT POST CREATION RESTRICTION (ONLY ADMINISTRATORS)
   ========================================================================== */

// Ensure only Administrators can publish or create posts
add_filter( 'user_has_cap', function ( $allcaps, $caps, $args, $user ) {
	$restricted_caps = array(
		'edit_posts',
		'publish_posts',
		'create_posts',
		'edit_others_posts',
		'publish_pages',
		'edit_pages',
	);

	// If the user is NOT an administrator, strip post creation/editing capabilities
	if ( ! empty( $user->roles ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
		foreach ( $restricted_caps as $cap ) {
			$allcaps[ $cap ] = false;
		}
	}

	return $allcaps;
}, 10, 4 );

// Block non-administrators from publishing via REST API
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	$method = $request->get_method();
	$route  = $request->get_route();

	// If a write request is targeting posts/pages
	if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
		if ( preg_match( '#^/wp/v2/(posts|pages|product)#', $route ) ) {
			if ( ! current_user_can( 'administrator' ) ) {
				return new WP_Error(
					'rest_forbidden',
					esc_html__( 'You are not authorized to create or modify content.', 'bhagwandas' ),
					array( 'status' => 403 )
				);
			}
		}
	}

	return $result;
}, 10, 3 );


/* ==========================================================================
   3. ANTI-SCAMMER & BOT PROTECTION
   ========================================================================== */

// 1. Disable XML-RPC completely (Blocks brute-force & remote amplification attacks)
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

// 2. Block Author Username Enumeration (?author=1 attacks used to discover admin usernames)
if ( ! is_admin() ) {
	if ( isset( $_REQUEST['author'] ) || ( isset( $_SERVER['QUERY_STRING'] ) && preg_match( '/author=([0-9]*)/i', $_SERVER['QUERY_STRING'] ) ) ) {
		wp_die( esc_html__( 'Author scanning is prohibited.', 'bhagwandas' ), '', array( 'response' => 403 ) );
	}
}

// 3. Remove users endpoint from public REST API (Prevents scammers from scraping user lists)
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! current_user_can( 'administrator' ) ) {
		if ( isset( $endpoints['/wp/v2/users'] ) ) {
			unset( $endpoints['/wp/v2/users'] );
		}
		if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
			unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
	}
	return $endpoints;
} );

// 4. Remove WordPress version info from page head and feeds (Blocks vulnerability version scanners)
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// 5. Disable Pingbacks and Trackbacks
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
