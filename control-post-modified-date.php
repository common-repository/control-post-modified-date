<?php
/**
 * Plugin Name: Control Post Modified Date
 * Description: Keep the last modified date unchanged for minor post updates.
 * Version: 1.0.1
 * Author: MotoPress
 * Author URI: https://motopress.com
 * Text Domain: control-post-modified-date
 * Domain Path: /languages
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Class ControlPostModifiedDate
 *
 * @package ControlPostModifiedDate
 * @since 1.0.0
 */

if ( ! class_exists( 'ControlPostModifiedDate' ) ) {

	/**
	 * Main class.
	 *
	 * @since 1.0.0
	 */
	final class ControlPostModifiedDate {

		/**
		 * The post type which allowed to change "modified on" date.
		 *
		 * @since 1.0.0
		 * @var string $post_type Post type slug.
		 */
		private $post_type = 'post';

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'post_submitbox_start', array( $this, 'render_update_date_setting' ), -1 );
			add_filter( 'wp_insert_post_data', array( $this, 'maybe_change_post_modified_date_on_save' ), 10, 2 );
			add_action( 'init', array( $this, 'load_textdomain' ) );
		}

		/**
		 * Loads textdomain for plugin.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'control-post-modified-date', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Filters post data before save.
		 *
		 * @since 1.0.0
		 * @param array $data An array of slashed, sanitized, and processed post data.
		 * @param array $post_array An array of sanitized (and slashed) but otherwise unmodified post data.
		 *
		 * @return array An array of slashed, sanitized, and processed post data.
		 */
		public function maybe_change_post_modified_date_on_save( $data, $post_array ) {

			if ( ! $this->is_update_allowed( $data['post_type'], $post_array['ID'], $data['post_status'] ) ) {
				return $data;
			}

			if ( ! isset( $_POST['cpmd_nonce_field'] )
				|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpmd_nonce_field'] ) ), 'cpmd_nonce' ) ) {
				return $data;
			}

			$use_current_date = isset( $_POST['cpmd_use_current_date'] )
				&& filter_var( wp_unslash( $_POST['cpmd_use_current_date'] ), FILTER_VALIDATE_BOOLEAN );

			if ( ! $use_current_date ) {

				$modified = isset( $_POST['cpmd_post_modified'] ) && $this->is_valid_date( sanitize_text_field( wp_unslash( $_POST['cpmd_post_modified'] ) ) )
					? sanitize_text_field( wp_unslash( $_POST['cpmd_post_modified'] ) )
					: '';

				if ( ! $modified ) {
					$modified = $data['post_date'];
				}

				$modified = date_format( date_create( $modified ), 'Y-m-d H:i:s' );

				$data['post_modified'] = $modified;
				$data['post_modified_gmt'] = get_gmt_from_date( $modified );

			}

			return $data;
		}

		/**
		 * Output controls for "modified on" date at the beginning of the publishing actions section of the Publish meta box.
		 *
		 * @since 1.0.0
		 * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen,
		 *                           null on Edit Link screen.
		 *
		 * @return void
		 */
		public function render_update_date_setting( $post ) {

			if ( ! $this->is_update_allowed( $post->post_type, $post->ID, $post->post_status ) ) {
				return;
			}

			wp_nonce_field( 'cpmd_nonce', 'cpmd_nonce_field', false );

			?>
			<div style="margin: 0 0 1em;">
				<p style="margin: 0 0 .5em;"><b><?php esc_html_e( 'Modified on:', 'control-post-modified-date' ); ?></b></p>
				<input id="cpmd_post_modified" step='1' name="cpmd_post_modified" type="datetime-local" value="<?php echo esc_attr( $post->post_modified ); ?>">
				<label style="margin-top: .75em; margin-bottom: 1em; display: inline-block;"><input type="checkbox" id="cpmd_use_current_date" name="cpmd_use_current_date" /> <?php esc_html_e( 'Use current date', 'control-post-modified-date' ); ?></label>
				<hr/>
			</div>

			<script>
				const checkbox = document.querySelector('#cpmd_use_current_date');
				const dateInput = document.querySelector('#cpmd_post_modified');

				checkbox.addEventListener( 'change', ( evt ) => {
					if ( checkbox.checked ) {
						dateInput.disabled = true;
					} else {
						dateInput.disabled = false;
					}
				} );
			</script>
			<?php
		}

		/**
		 * Check if "modified on" date edit is allowed for user and post type.
		 *
		 * @since 1.0.0
		 * @param string     $post_type Current post type.
		 * @param int|string $post_ID Current post ID.
		 * @param string     $post_status Current post status.
		 *
		 * @return boolean
		 */
		private function is_update_allowed( $post_type, $post_ID, $post_status ) {

			if ( current_user_can( 'edit_post', $post_ID ) && $this->post_type === $post_type && 'publish' === $post_status ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if string is valid date.
		 *
		 * @since 1.0.0
		 * @param string $date Possible date string.
		 *
		 * @return boolean
		 */
		private function is_valid_date( $date ) {
			return (bool) strtotime( $date );
		}
	}

	new ControlPostModifiedDate();

}
