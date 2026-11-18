<?php
/**
 * Resources service provider.
 *
 * @package   Backdrop Resources
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/resources
 */

namespace Backdrop\Resources;

use Backdrop\Core\ServiceProvider;

/**
 * Registers the shared resources with the application, as `resources` and
 * `Backdrop\Resources\Resources`, and shows a notice in the dashboard when the
 * theme's styles and scripts haven't been built.
 *
 * @since  1.0.0
 * @access public
 */
class Provider extends ServiceProvider {

	/**
	 * Binds the shared resources.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function register(): void {

		$this->app->singleton( Resources::class, function() {
			return resources();
		} );

		$this->app->alias( Resources::class, 'resources' );
	}

	/**
	 * Adds the missing build notice.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function boot(): void {

		add_action( 'admin_notices', [ $this, 'notice' ] );
	}

	/**
	 * Shows a notice to administrators when there is no build. Return false from the
	 * `backdrop/resources/notice` filter to turn it off.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function notice(): void {

		if ( ! current_user_can( 'switch_themes' ) || resources()->exists() || ! apply_filters( 'backdrop/resources/notice', true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses(
				sprintf(
					// Translators: 1 is the theme name, 2 is the build command.
					__( '%1$s can\'t find its built styles and scripts. Run %2$s in the theme folder, or install the theme from a release.', 'backdrop' ),
					esc_html( wp_get_theme( get_template() )->get( 'Name' ) ),
					'<code>npm install &amp;&amp; npm run build</code>'
				),
				[ 'code' => [] ]
			)
		);
	}
}
