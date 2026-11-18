<?php
/**
 * Vite manifest.
 *
 * @package   Backdrop Resources
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/resources
 */

namespace Backdrop\Resources;

/**
 * Reads a Vite build: the `manifest.json` that maps each source file (such as
 * `resources/scss/screen.scss`) to its built, hashed file (such as
 * `css/screen-BtX2a9.css`), in one folder.
 *
 * @since  1.0.0
 * @access public
 */
class Manifest {

	/**
	 * Folder holding the build, with no trailing slash.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string
	 */
	protected $path;

	/**
	 * URL of that folder, with no trailing slash.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string
	 */
	protected $url;

	/**
	 * The manifest entries, or null if there is no readable manifest.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array|null
	 */
	protected $entries;

	/**
	 * Sets up the manifest for a build folder.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $path      Folder holding the build, such as `{theme}/public/assets`.
	 * @param  string  $url       URL of that folder.
	 * @param  string  $manifest  Manifest file name, relative to the folder.
	 * @return void
	 */
	public function __construct( string $path, string $url, string $manifest = 'manifest.json' ) {

		$this->path = rtrim( $path, '/\\' );
		$this->url  = rtrim( $url, '/' );

		$file = $this->path . '/' . ltrim( $manifest, '/' );

		if ( is_readable( $file ) ) {
			$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			$this->entries = is_array( $data ) ? $data : null;
		}
	}

	/**
	 * Whether the build exists.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 */
	public function exists(): bool {

		return null !== $this->entries;
	}

	/**
	 * Whether a source file is in the build.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $source  Source file, such as `resources/js/app.js`.
	 * @return bool
	 */
	public function has( string $source ): bool {

		return null !== $this->entry( $source );
	}

	/**
	 * Returns the manifest entry for a source file, or null.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $source
	 * @return array|null
	 */
	public function entry( string $source ): ?array {

		$key = ltrim( str_replace( '\\', '/', $source ), '/' );

		return isset( $this->entries[ $key ]['file'] ) ? $this->entries[ $key ] : null;
	}

	/**
	 * Returns the URL of the built file for a source file, or an empty string if it
	 * isn't in the build.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $source
	 * @return string
	 */
	public function url( string $source ): string {

		$entry = $this->entry( $source );

		return $entry ? $this->fileUrl( $entry['file'] ) : '';
	}

	/**
	 * Returns the URLs of the stylesheets a script entry needs: the CSS that Vite split out
	 * of it and of the chunks it imports, in order and without duplicates.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $source
	 * @return string[]
	 */
	public function css( string $source ): array {

		$key = ltrim( str_replace( '\\', '/', $source ), '/' );
		$css = [];

		$this->collectCss( $key, $css, [] );

		return array_values( array_map( [ $this, 'fileUrl' ], array_unique( $css ) ) );
	}

	/**
	 * Adds the CSS files of an entry and its imports.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string  $key
	 * @param  array   $css
	 * @param  array   $seen
	 * @return void
	 */
	protected function collectCss( string $key, array &$css, array $seen ): void {

		if ( isset( $seen[ $key ] ) || ! isset( $this->entries[ $key ] ) ) {
			return;
		}

		$seen[ $key ] = true;
		$entry        = $this->entries[ $key ];

		foreach ( (array) ( $entry['imports'] ?? [] ) as $import ) {
			$this->collectCss( (string) $import, $css, $seen );
		}

		foreach ( (array) ( $entry['css'] ?? [] ) as $file ) {
			$css[] = (string) $file;
		}
	}

	/**
	 * Returns the URL of a file in the build folder.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string  $file
	 * @return string
	 */
	protected function fileUrl( string $file ): string {

		return $this->url . '/' . ltrim( $file, '/' );
	}
}
