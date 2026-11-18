<?php
/**
 * Minimal stand-ins for the WordPress/ClassicPress functions that the package
 * uses, so the tests run without an install.
 */

$GLOBALS['hooks']   = [];
$GLOBALS['styles']  = [];
$GLOBALS['scripts'] = [];

function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][ $hook ][] = $cb; }
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { add_filter( $hook, $cb ); }
function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['hooks'][ $hook ] ?? [] as $cb ) { $value = $cb( $value, ...$args ); }
	return $value;
}
function wp_enqueue_style( $handle, $src, $deps = [], $ver = false, $media = 'all' ) { $GLOBALS['styles'][ $handle ] = compact( 'src', 'deps', 'media' ); }
function wp_enqueue_script( $handle, $src, $deps = [], $ver = false, $footer = false ) { $GLOBALS['scripts'][ $handle ] = compact( 'src', 'deps', 'footer' ); }
function wp_script_is( $handle, $list = 'enqueued' ) { return isset( $GLOBALS['scripts'][ $handle ] ); }
function get_stylesheet_directory() { return $GLOBALS['child_dir']; }
function get_template_directory() { return $GLOBALS['parent_dir']; }
function get_stylesheet_directory_uri() { return 'https://example.com/child'; }
function get_template_directory_uri() { return 'https://example.com/parent'; }

spl_autoload_register( function ( $class ) {
	$prefix = 'Backdrop\\Resources\\';
	if ( 0 === strpos( $class, $prefix ) ) {
		$file = __DIR__ . '/../src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require $file;
		}
	}
} );

require __DIR__ . '/../src/functions-helpers.php';
