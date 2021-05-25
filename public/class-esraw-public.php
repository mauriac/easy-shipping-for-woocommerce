<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Esraw
 * @subpackage Esraw/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Esraw
 * @subpackage Esraw/public
 * @author     DigitCode <digitcode0@gmail.com>
 */
class Esraw_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function display_shipping_description_on_cart( $method, $index ) {
		$easy_shipping        = new Esraw_Shipping_Easy_Rate( $method->get_instance_id() );
		$description_for_user = $easy_shipping->get_instance_option( Esraw_Shipping_Easy_Rate::METHOD_DESCRIPTION, '' );
		if ( $description_for_user ) {
			?>
				<small><p class="shipping-method-description"><?php echo $description_for_user; ?></p></small>
			<?php
		}
	}
}
