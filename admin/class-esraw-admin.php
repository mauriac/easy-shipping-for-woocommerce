<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Esraw
 * @subpackage Esraw/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Esraw
 * @subpackage Esraw/admin
 * @author     DigitCode <digitcode0@gmail.com>
 */
class Esraw_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rsw_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rsw_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rsw-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . 'select2', plugin_dir_url( __FILE__ ) . 'css/select-css/select2.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rsw_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rsw_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/esraw-admin.js', array( 'jquery' ), $this->version, false );

		$data = array(
			'esraw_condition_choices'   => Esraw_Shipping_Easy_Rate::CONDITION_CHOICES,
			'esraw_operator'            => Esraw_Shipping_Easy_Rate::OPERATOR,
			'esraw_condition_actions'   => Esraw_Shipping_Easy_Rate::CONDITIONS_ACTIONS,
			'esraw_currency_symbol'     => get_woocommerce_currency_symbol(),
			'esraw_ship_classes_array'  => Esraw_Shipping_Easy_Rate::ship_classes_select_field(),
			'esraw_user_roles'          => esraw_get_user_roles(),
			'esraw_products_list_array' => esraw_get_products_for_ship_conditions(),
		);
		wp_localize_script( $this->plugin_name, 'esr_vars', $data );

		wp_enqueue_script( $this->plugin_name . 'select2', plugin_dir_url( __FILE__ ) . 'js/select-js/select2.full.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Initialize plugin.
	 */
	public function init_shipping_method() {
		new Esraw_Shipping_Easy_Rate();
	}

	public function add_easy_rate_shipping_method( $methods ) {
		$methods[ Esraw_Shipping_Easy_Rate::METHOD_ID ] = 'Esraw_Shipping_Easy_Rate';
		return $methods;
	}

	public function add_admin_menu() {
		add_menu_page( 'Easy Shipping Rate', 'Easy Shipping Rate', 'manage_options', 'easy_shipping_rate', array( $this, 'export_page' ) );
		add_submenu_page( 'easy_shipping_rate', 'Import Easy Shipping Methods', 'Import Shipping Methods', 'manage_options', 'import-methods-esraw', array( $this, 'import_page' ) );
	}
	public function export_page() {
		if ( isset( $_POST['esraw_securite_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['esraw_securite_nonce'] ), 'esraw-security' ) ) {
			if ( isset( $_POST['esraw_shipping_export_field'] ) ) {
				ob_end_clean();
				$export_data = $_POST['esraw_shipping_export_field'];

				$csv_file = 'easy_rate_shipping_methods_' . date( 'Ymd_His' ) . '.csv';
				header( 'Content-Type: text/csv' );
				header( "Content-Disposition: attachment; filename=\"$csv_file\"" );
				$fh      = fopen( 'php://output', 'w' );
				$records = array();
				foreach ( $export_data as $key => $ship_instance_id ) {
					$method_instance = get_option( 'woocommerce_' . Esraw_Shipping_Easy_Rate::METHOD_ID . '_' . $ship_instance_id . '_settings' );

					$method_condition = get_option( Esraw_Shipping_Easy_Rate::METHOD_ID . $ship_instance_id );
					if ( is_array( $method_instance ) && is_array( $method_condition ) ) {
						$method_instance['next']  = 'yes';
						$method_condition['next'] = 'no';
						$records[]                = array( wp_json_encode( $method_instance ) );
						$records[]                = array( wp_json_encode( $method_condition ) );
					} else {
						wp_die( __( 'Couldn\'t get export file', 'esraw-woo' ) );
					}
				}
				if ( ! empty( $records ) ) {
					foreach ( $records as $record ) {
						fputcsv( $fh, $record );
					}
					fclose( $fh );
				}
				die;
			}
		}
		?>
		<div class="wrap">
			<form method="POST">
				<table class="form-table">
					<tr valign="top">
						<div class="col-auto my-1">
							<th scope="row">
								<strong>
									<?php esc_html_e( 'Export Methods', 'esraw-woo' ); ?>
								</strong>
							</th>
							<td>
								<select multiple name="esraw_shipping_export_field[]" id="esraw_shipping_export_field">
									<?php
									$methods = esraw_get_shipping_list_for_export();
									foreach ( $methods as $key => $method ) :
										?>
									<option value="<?php echo $key; ?>"><?php echo $method; ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</div>
					</tr>
				</table>
				<input type="hidden" name="esraw_securite_nonce" value="<?php echo esc_html( wp_create_nonce( 'esraw-security' ) ); ?>"/>
				<span ><?php submit_button( 'Generate Export File' ); ?></span>
			</form>
		</div>
		<?php
	}

	public function import_page() {
		if ( isset( $_POST['esraw_securite_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['esraw_securite_nonce'] ), 'esraw-security' ) ) {
			if ( isset( $_POST['esraw_shipping_zones_import'] ) && $_FILES['esraw_import_file'] ) {

				// get export data from file.
				if ( isset( $_FILES['esraw_import_file']['tmp_name'] ) ) {
					$file = $_FILES['esraw_import_file']['tmp_name'];

					$zones     = wp_unslash( $_POST['esraw_shipping_zones_import'] );
					$is_import = false;
					foreach ( $zones as $key => $zone_id ) {
						$zone = new WC_Shipping_Zone( $zone_id );

						if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
							$ship_instance_id = null;
							while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
								$data_decode = json_decode( current( $data ), true );
								if ( 'yes' === $data_decode['next'] ) {
									$ship_instance_id = $zone->add_shipping_method( Esraw_Shipping_Easy_Rate::METHOD_ID );

									unset( $data_decode['next'] );
									update_option( 'woocommerce_' . Esraw_Shipping_Easy_Rate::METHOD_ID . '_' . $ship_instance_id . '_settings', $data_decode );
								} elseif ( 'no' === $data_decode['next'] && isset( $ship_instance_id ) && $ship_instance_id ) {
									unset( $data_decode['next'] );
									update_option( Esraw_Shipping_Easy_Rate::METHOD_ID . $ship_instance_id, $data_decode );
								}
							}
							fclose( $handle );
							$is_import = true;
						}
					}
					if ( $is_import ) {
						?>
							<div class="notice notice-success is-dismissible">
								<p><?php esc_html_e( 'Shipping method import successfully!', 'esraw-woo' ); ?></p>
							</div>
						<?php
					}
				}
			}
		}
		?>
		<div class="wrap">
			<form method="POST" enctype="multipart/form-data">
				<table class="form-table">
					<tr valign="top">
						<div class="col-auto my-1">
							<th scope="row">
								<strong>
									<?php esc_html_e( 'zones', 'esraw-woo' ); ?>
								</strong>
							</th>
							<td>
								<select multiple name="esraw_shipping_zones_import[]" id="esraw_shipping_zones_import">
									<?php
									$zones = esraw_get_shipping_zones_list_for_import();
									foreach ( $zones as $key => $zone ) :
										?>
									<option value="<?php echo $key; ?>"><?php echo $zone; ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</div>
					</tr>
					<tr valign="top">
						<div class="col-auto my-1">
							<th scope="row">
								<strong>
									<?php esc_html_e( 'files', 'esraw-woo' ); ?>
								</strong>
							</th>
							<td>
								<input type="file" name="esraw_import_file" id="esraw_import_file" accept=".csv">
							</td>
						</div>
					</tr>
				</table>
				<input type="hidden" name="esraw_securite_nonce" value="<?php echo esc_html( wp_create_nonce( 'esraw-security' ) ); ?>"/>
				<span ><?php submit_button( __( 'Import methods', 'esraw-woo' ) ); ?></span>
			</form>
		</div>
		<?php
	}

	public function delete_shipping_conditions( $instance_id ) {
		delete_option( Esraw_Shipping_Easy_Rate::METHOD_ID . $instance_id );
	}
}
