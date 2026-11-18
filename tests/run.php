<?php
/**
 * Dependency free tests. Run with: php tests/run.php
 */

require __DIR__ . '/bootstrap.php';

use Backdrop\Resources\Manifest;
use Backdrop\Resources\Resources;

$failed = 0;

function check( string $label, $actual, $expected ): void {
	global $failed;
	if ( $actual === $expected ) {
		echo "ok   - {$label}\n";
		return;
	}
	$failed++;
	echo "FAIL - {$label}\n       expected: " . var_export( $expected, true ) . "\n       actual:   " . var_export( $actual, true ) . "\n";
}

// A parent build and a child build in temporary folders.
$tmp    = sys_get_temp_dir() . '/backdrop-resources-' . getmypid();
$parent = "{$tmp}/parent/public/assets";
$child  = "{$tmp}/child/public/assets";
@mkdir( $parent, 0777, true );
@mkdir( $child, 0777, true );

file_put_contents( "{$parent}/manifest.json", json_encode( [
	'resources/scss/screen.scss' => [ 'file' => 'css/screen-AAA.css', 'isEntry' => true ],
	'resources/js/app.js'        => [ 'file' => 'js/app-BBB.js', 'isEntry' => true, 'imports' => [ '_shared-CCC.js' ], 'css' => [ 'css/app-DDD.css' ] ],
	'_shared-CCC.js'             => [ 'file' => 'js/shared-CCC.js', 'css' => [ 'css/shared-EEE.css', 'css/app-DDD.css' ] ],
	'resources/js/nav.js'        => [ 'file' => 'js/nav-FFF.js', 'isEntry' => true ],
] ) );

file_put_contents( "{$child}/manifest.json", json_encode( [
	'resources/scss/screen.scss' => [ 'file' => 'css/screen-CHILD.css', 'isEntry' => true ],
] ) );

// Manifest.
$manifest = new Manifest( $parent, 'https://example.com/parent/public/assets/' );
check( 'manifest exists', $manifest->exists(), true );
check( 'built URL', $manifest->url( 'resources/scss/screen.scss' ), 'https://example.com/parent/public/assets/css/screen-AAA.css' );
check( 'leading slash is ignored', $manifest->url( '/resources/scss/screen.scss' ), 'https://example.com/parent/public/assets/css/screen-AAA.css' );
check( 'unknown source', $manifest->url( 'resources/js/missing.js' ), '' );
check( 'chunks without a file are not entries', $manifest->has( '_nope' ), false );
check( 'css from imports first, no duplicates', $manifest->css( 'resources/js/app.js' ), [
	'https://example.com/parent/public/assets/css/shared-EEE.css',
	'https://example.com/parent/public/assets/css/app-DDD.css',
] );
check( 'missing manifest', ( new Manifest( "{$tmp}/nowhere", 'https://example.com' ) )->exists(), false );

file_put_contents( "{$tmp}/broken.json", '{not json' );
check( 'invalid manifest', ( new Manifest( $tmp, 'https://example.com', 'broken.json' ) )->exists(), false );

// Resources: the child build is checked first, then the parent.
$GLOBALS['child_dir']  = "{$tmp}/child";
$GLOBALS['parent_dir'] = "{$tmp}/parent";

$resources = Backdrop\Resources\resources();
check( 'child build wins', Backdrop\Resources\asset( 'resources/scss/screen.scss' ), 'https://example.com/child/public/assets/css/screen-CHILD.css' );
check( 'falls back to the parent build', Backdrop\Resources\asset( 'resources/js/nav.js' ), 'https://example.com/parent/public/assets/js/nav-FFF.js' );
check( 'is built', Backdrop\Resources\is_built(), true );

// Enqueue.
check( 'enqueue style', Backdrop\Resources\enqueue_style( 'screen', 'resources/scss/screen.scss' ), true );
check( 'style URL', $GLOBALS['styles']['screen']['src'], 'https://example.com/child/public/assets/css/screen-CHILD.css' );
check( 'enqueue missing style', Backdrop\Resources\enqueue_style( 'nope', 'resources/scss/nope.scss' ), false );

check( 'enqueue script', Backdrop\Resources\enqueue_script( 'app', 'resources/js/app.js', [ 'jquery' ] ), true );
check( 'script URL, deps and footer', $GLOBALS['scripts']['app'], [ 'src' => 'https://example.com/parent/public/assets/js/app-BBB.js', 'deps' => [ 'jquery' ], 'footer' => true ] );
check( 'script css is enqueued', [ $GLOBALS['styles']['app-1']['src'], $GLOBALS['styles']['app-2']['src'] ], [
	'https://example.com/parent/public/assets/css/shared-EEE.css',
	'https://example.com/parent/public/assets/css/app-DDD.css',
] );

// Module scripts.
Backdrop\Resources\enqueue_script( 'nav', 'resources/js/nav.js', [], [ 'module' => true ] );
$tag = apply_filters( 'script_loader_tag', '<script type="text/javascript" src="nav.js" id="nav-js"></script>', 'nav' );
check( 'module tag', $tag, '<script type="module" src="nav.js" id="nav-js"></script>' );
check( 'other scripts are untouched', apply_filters( 'script_loader_tag', '<script src="x.js"></script>', 'app' ), '<script src="x.js"></script>' );

// Dev server.
$GLOBALS['scripts'] = [];
$dev = new Resources( [ new Manifest( $parent, 'https://example.com/parent/public/assets' ) ], 'http://localhost:5173/' );
check( 'dev URL', $dev->url( 'resources/scss/screen.scss' ), 'http://localhost:5173/resources/scss/screen.scss' );
$dev->enqueueScript( 'app', 'resources/js/app.js' );
check( 'dev client is enqueued', $GLOBALS['scripts']['vite-client']['src'], 'http://localhost:5173/@vite/client' );
check( 'dev script depends on the client', $GLOBALS['scripts']['app']['deps'], [ 'vite-client' ] );
check( 'dev script is a module', apply_filters( 'script_loader_tag', '<script src="app.js"></script>', 'app' ), '<script type="module" src="app.js"></script>' );
check( 'dev counts as built', ( new Resources( [], 'http://localhost:5173' ) )->exists(), true );
check( 'nothing built', ( new Resources( [ new Manifest( "{$tmp}/nowhere", 'x' ) ] ) )->exists(), false );

// Clean up.
foreach ( [ "{$parent}/manifest.json", "{$child}/manifest.json", "{$tmp}/broken.json" ] as $file ) {
	@unlink( $file );
}

echo $failed ? "\n{$failed} failed\n" : "\nAll passed\n";
exit( $failed ? 1 : 0 );
