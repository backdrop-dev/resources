<?php
/**
 * Resources.
 *
 * @package   Backdrop Resources
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/resources
 */

namespace Backdrop\Resources;

/**
 * Finds and enqueues the styles and scripts that Vite builds.
 *
 * Builds are looked up in order, so a child theme's build is checked before its
 * parent's. When a Vite dev server is set, files load from it instead.
 *
 * @since  1.0.0
 * @access public
 */
class Resources {

	/**
	 * Builds to look in, most important first.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Manifest[]
	 */
	protected $manifests = [];

	/**
	 * URL of the Vite dev server, such as `http://localhost:5173`, or an empty string.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string
	 */
	protected $devServer = '';

	/**
	 * Script handles to print as `type="module"`.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array
	 */
	protected $modules = [];

	/**
	 * Sets up the builds and the dev server.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  Manifest[]  $manifests
	 * @param  string      $dev_server
	 * @return void
	 */
	public function __construct( array $manifests = [], string $dev_server = '' ) {

		foreach ( $manifests as $manifest ) {
			$this->addManifest( $manifest );
		}

		$this->devServer = rtrim( $dev_server, '/' );
	}

	/**
	 * Adds a build, checked after the ones already added.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  Manifest  $manifest
	 * @return void
	 */
	public function addManifest( Manifest $manifest ): void {

		$this->manifests[] = $manifest;
	}

	/**
	 * Whether files load from the Vite dev server.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 */
	public function isDev(): bool {

		return '' !== $this->devServer;
	}

	/**
	 * Whether there is a build, or a dev server to load from.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 */
	public function exists(): bool {

		if ( $this->isDev() ) {
			return true;
		}

		foreach ( $this->manifests as $manifest ) {
			if ( $manifest->exists() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the URL for a source file, such as `resources/scss/screen.scss`: from the
	 * dev server if one is set, otherwise the first build that has it. Returns an empty
	 * string if no build has it.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $source
	 * @return string
	 */
	public function url( string $source ): string {

		$source = ltrim( str_replace( '\\', '/', $source ), '/' );

		if ( $this->isDev() ) {
			return $this->devServer . '/' . $source;
		}

		$manifest = $this->find( $source );

		return $manifest ? $manifest->url( $source ) : '';
	}

	/**
	 * Enqueues a stylesheet. Returns false if the source file isn't in a build.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $handle
	 * @param  string  $source  Source file, such as `resources/scss/screen.scss`.
	 * @param  array   $deps
	 * @param  string  $media
	 * @return bool
	 */
	public function enqueueStyle( string $handle, string $source, array $deps = [], string $media = 'all' ): bool {

		$url = $this->url( $source );

		if ( '' === $url ) {
			return false;
		}

		wp_enqueue_style( $handle, $url, $deps, null, $media );

		return true;
	}

	/**
	 * Enqueues a script, and the stylesheets Vite split out of it. Returns false if the
	 * source file isn't in a build.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $handle
	 * @param  string  $source  Source file, such as `resources/js/app.js`.
	 * @param  array   $deps
	 * @param  array   $args {
	 *     Optional.
	 *
	 *     @type bool  $in_footer  Load in the footer. Default true.
	 *     @type bool  $module     Print as `type="module"`, for scripts that import other
	 *                             files. Always true with the dev server. Default false.
	 * }
	 * @return bool
	 */
	public function enqueueScript( string $handle, string $source, array $deps = [], array $args = [] ): bool {

		$args = array_merge( [ 'in_footer' => true, 'module' => false ], $args );
		$url  = $this->url( $source );

		if ( '' === $url ) {
			return false;
		}

		if ( $this->isDev() ) {
			$this->enqueueClient();
			$deps[]          = 'vite-client';
			$args['module'] = true;
		}

		wp_enqueue_script( $handle, $url, $deps, null, (bool) $args['in_footer'] );

		if ( $args['module'] ) {
			$this->module( $handle );
		}

		if ( ! $this->isDev() ) {
			$manifest = $this->find( ltrim( str_replace( '\\', '/', $source ), '/' ) );

			foreach ( $manifest ? $manifest->css( $source ) : [] as $i => $css ) {
				wp_enqueue_style( "{$handle}-" . ( $i + 1 ), $css, [], null );
			}
		}

		return true;
	}

	/**
	 * Prints a script as `type="module"`.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $handle
	 * @return void
	 */
	public function module( string $handle ): void {

		if ( ! $this->modules ) {
			add_filter( 'script_loader_tag', [ $this, 'moduleTag' ], 10, 2 );
		}

		$this->modules[ $handle ] = true;
	}

	/**
	 * Filter on `script_loader_tag` that adds `type="module"` to module scripts.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $tag
	 * @param  string  $handle
	 * @return string
	 */
	public function moduleTag( $tag, $handle ) {

		if ( empty( $this->modules[ $handle ] ) || false !== strpos( (string) $tag, 'type="module"' ) ) {
			return $tag;
		}

		// Replace a `type` attribute if there is one, otherwise add one.
		$tag = preg_replace( '/\stype=([\'"])[^\'"]*\1/', '', (string) $tag );

		return preg_replace( '/<script\b(?=[^>]*\ssrc=)/', '<script type="module"', $tag );
	}

	/**
	 * Enqueues Vite's client script, which reloads the page when files change.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return void
	 */
	protected function enqueueClient(): void {

		if ( ! wp_script_is( 'vite-client', 'enqueued' ) ) {
			wp_enqueue_script( 'vite-client', $this->devServer . '/@vite/client', [], null, false );
			$this->module( 'vite-client' );
		}
	}

	/**
	 * Returns the first build that has a source file.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string  $source
	 * @return Manifest|null
	 */
	protected function find( string $source ): ?Manifest {

		foreach ( $this->manifests as $manifest ) {
			if ( $manifest->has( $source ) ) {
				return $manifest;
			}
		}

		return null;
	}
}
