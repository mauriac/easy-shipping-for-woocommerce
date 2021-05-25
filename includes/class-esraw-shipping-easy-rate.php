<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Esraw_Shipping_Easy_Rate extends WC_Shipping_Method {
	const METHOD_TITLE               = 'method_title';
	const METHOD_DESCRIPTION         = 'method_description';
	const METHOD_TAXABLE             = 'method_taxable';
	const METHOD_FREE_SHIPPING_COST  = 'method_free_shipping_cost';
	const METHOD_FREE_SHIPPING_LABEL = 'method_free_shipping_label';
	const METHOD_VISIBILITY          = 'method_visibility';
	const METHOD_MINIMUM_COST        = 'method_minimum_cost';

	/**
	 * Constructor for your shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 ) {
		$this->instance_id = absint( $instance_id );
		$this->supports    = array(
			'shipping-zones',
			'instance-settings',
			'settings',
		);

		$this->id = 'esrw';
		$this->init();

		$this->method_title       = __( 'Easy Shipping', 'esr-woo' );
		$this->method_description = __( 'Easy way to define your shipping method', 'esr-woo' );
		$this->enabled            = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
		$this->title              = $this->get_instance_option( self::METHOD_TITLE, $this->method_title );
		$this->tax_status         = $this->get_instance_option( self::METHOD_TAXABLE );
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$settings = array(
			'section_general'                => array(
				'title' => __( 'General Settings', 'esr-woo' ),
				'type'  => 'title',
			),
			self::METHOD_TITLE               => array(
				'title'       => __( 'Method Title', 'esr-woo' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'esr-woo' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			self::METHOD_DESCRIPTION         => array(
				'title'       => __( 'Description', 'esr-woo' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'esr-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_TAXABLE             => array(
				'title'    => __( 'Tax Status', 'esr-woo' ),
				'type'     => 'select',
				'default'  => 'taxable',
				'options'  => array(
					'taxable' => __( 'Taxable', 'esr-woo' ),
					'none'    => _x( 'None', 'Tax status', 'esr-woo' ),
				),
				'desc_tip' => __( 'Apply tax or no.', 'esr-woo' ),
				'desc_tip' => true,
			),

			'section_free_shipping'          => array(
				'title' => __( 'Free Shipping', 'esr-woo' ),
				'type'  => 'title',
			),
			self::METHOD_FREE_SHIPPING_COST  => array(
				'title'       => __( 'Free Shipping', 'esr-woo' ),
				'type'        => 'price',
				'default'     => null,
				'description' => __( 'Enter a minimum order amount for free shipping. This will override the costs configured below.', 'esr-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_FREE_SHIPPING_LABEL => array(
				'title'       => __( 'Free Shipping Label', 'esr-woo' ),
				'type'        => 'text',
				'default'     => __( 'Free', 'esr-woo' ),
				'description' => __( 'Enter free shipping label.', 'esr-woo' ),
				'desc_tip'    => true,
			),

			'section_advanced_options'       => array(
				'title' => __( 'Advanced Options', 'esr-woo' ),
				'type'  => 'title',
			),
			self::METHOD_VISIBILITY          => array(
				'title'       => __( 'Visibility', 'esr-woo' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => ' ',
				'description' => __( 'Show only for logged in users.', 'esr-woo' ),
			),
			self::METHOD_MINIMUM_COST        => array(
				'title'       => __( 'Shipping minimum cost', 'esr-woo' ),
				'type'        => 'price',
				'label'       => ' ',
				'default'     => null,
				'description' => __( 'Enter the minimum price for this shipment.', 'esr-woo' ),
				'desc_tip'    => true,
			),
		);

		$this->instance_form_fields = $settings;
		$this->form_fields          = array(); // No global options for table rates
	}

	/**
	 * Calculate_shipping function.
	 *
	 * @param mixed $package comment.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		if ( ! $this->is_visible_for_user() ) {
			return;
		}
		$order_total                = $package ['cart_subtotal'];
		$minum_order_total_for_free = $this->get_instance_option( self::METHOD_FREE_SHIPPING_COST, null );
		$cost                       = 0;
		$label                      = $this->title;
		if ( $minum_order_total_for_free && $minum_order_total_for_free <= $order_total ) {
			$cost  = 0;
			$label = $this->get_instance_option( self::METHOD_FREE_SHIPPING_LABEL, $this->title );
		} else {
			$cost = $this->get_instance_option( self::METHOD_MINIMUM_COST, 0 );
		}

		$rate = array(
			'label' => $label,
			'cost'  => $cost,
		);

		$this->add_rate( $rate );
	}

	/**
	 * Is method visible for user
	 *
	 * @return bool
	 */
	private function is_visible_for_user() {
		if ( 'yes' === $this->get_instance_option( self::METHOD_VISIBILITY, 'no' ) && ! is_user_logged_in() ) {
			return false;
		}
		return true;
	}
}
