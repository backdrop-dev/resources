<?php
/**
 * Helper functions.
 *
 * Loaded by Composer's autoloader, so they work without registering the service
 * provider. Each is defined only once, in case a theme and a plugin both bundle
 * this package.
 *
 * @package   Backdrop Resources
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/resources
 */

namespace Backdrop\Resources;

if ( ! function_exists( __NAMESPACE__ . '\\resources' ) ) {
	/**
	 * Returns the shared resources for the active theme: the child theme's build (if
	 * there is a child theme) and then the parent theme's build, in the folder given by
	 * the `backdrop/resources/folder` filter (default `public/assets`). The dev server
	 * comes from the `BACKDROP_VITE_DEV_SERVER` constant or the
	 * `backdrop/resources/dev_server` filter.
	 *
	 * @since  1.0.0
	 * @return Resources
	 */
	function resources(): Resources {
		static $resources = null;

		if ( null === $resources ) {
			$folder = trim( (string) apply_filters( 'backdrop/resources/folder', 'public/assets' ), '/' );
			$dev    = defined( 'BACKDROP_VITE_DEV_SERVER' ) ? (string) BACKDROP_VITE_DEV_SERVER : '';

			$resources = new Resources( [], (string) apply_filters( 'backdrop/resources/dev_server', $dev ) );

			if ( get_stylesheet_directory() !== get_template_directory() ) {
				$resources->addManifest( new Manifest( get_stylesheet_directory() . "/{$folder}", get_stylesheet_directory_uri() . "/{$folder}" ) );
			}

			$resources->addManifest( new Manifest( get_template_directory() . "/{$folder}", get_template_directory_uri() . "/{$folder}" ) );
		}

		return $resources;
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\asset' ) ) {
	/**
	 * Returns the URL for a source file, such as `resources/scss/screen.scss`, or an
	 * empty string if it isn't built.
	 *
	 * @since  1.0.0
	 * @param  string  $source
	 * @return string
	 */
	function asset( string $source ): string {
		return resources()->url( $source );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\enqueue_style' ) ) {
	/**
	 * Enqueues a built stylesheet. See `Resources::enqueueStyle()`.
	 *
	 * @since  1.0.0
	 * @param  string  $handle
	 * @param  string  $source
	 * @param  array   $deps
	 * @param  string  $media
	 * @return bool
	 */
	function enqueue_style( string $handle, string $source, array $deps = [], string $media = 'all' ): bool {
		return resources()->enqueueStyle( $handle, $source, $deps, $media );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\enqueue_script' ) ) {
	/**
	 * Enqueues a built script and its stylesheets. See `Resources::enqueueScript()`.
	 *
	 * @since  1.0.0
	 * @param  string  $handle
	 * @param  string  $source
	 * @param  array   $deps
	 * @param  array   $args
	 * @return bool
	 */
	function enqueue_script( string $handle, string $source, array $deps = [], array $args = [] ): bool {
		return resources()->enqueueScript( $handle, $source, $deps, $args );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\is_built' ) ) {
	/**
	 * Whether the styles and scripts are built, or a dev server is set.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	function is_built(): bool {
		return resources()->exists();
	}
}
