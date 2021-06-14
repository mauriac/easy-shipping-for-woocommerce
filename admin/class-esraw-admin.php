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
			'esraw_condition_choices'  => Esraw_Shipping_Easy_Rate::CONDITION_CHOICES,
			'esraw_operator'           => Esraw_Shipping_Easy_Rate::Operator,
			'esraw_currency_symbol'    => get_woocommerce_currency_symbol(),
			'esraw_ship_classes_array' => Esraw_Shipping_Easy_Rate::ship_classes_select_field(),
			'esraw_user_roles'         => esraw_get_user_roles(),
		);
		wp_localize_script( $this->plugin_name, 'esr_vars', $data );

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
}
