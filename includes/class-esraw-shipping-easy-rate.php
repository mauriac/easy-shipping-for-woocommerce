<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Utilities\NumberUtil;

class Esraw_Shipping_Easy_Rate extends WC_Shipping_Method {
	const METHOD_TITLE               = 'method_title';
	const METHOD_DESCRIPTION         = 'method_description';
	const METHOD_TAXABLE             = 'method_taxable';
	const METHOD_FREE_SHIPPING_COST  = 'method_free_shipping_cost';
	const METHOD_FREE_REQUIRES       = 'method_free_requires';
	const METHOD_FREE_MIN_AMOUNT     = 'method_free_min_amount';
	const METHOD_FREE_IGN_DISC       = 'method_free_ignore_discounts';
	const METHOD_FREE_SHIPPING_LABEL = 'method_free_shipping_label';
	const METHOD_VISIBILITY          = 'method_visibility';
	const METHOD_MINIMUM_COST        = 'method_minimum_cost';

	/**
	 * Min amount to be valid.
	 *
	 * @var integer
	 */
	private $min_amount = 0;

	/**
	 * Requires option.
	 *
	 * @var string
	 */
	private $requires = '';

	/**
	 * Whether or not is free shipping.
	 *
	 * @var string
	 */
	private $is_free_shipping;

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

		$this->id = 'esraw';
		$this->init();

		$this->method_title       = __( 'Easy Shipping', 'esraw-woo' );
		$this->method_description = __( 'Easy way to define your shipping method', 'esraw-woo' );
		$this->enabled            = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
		$this->title              = $this->get_instance_option( self::METHOD_TITLE, $this->method_title );
		$this->min_amount       = $this->get_option( self::METHOD_FREE_MIN_AMOUNT, 0 );
		$this->requires         = $this->get_option( self::METHOD_FREE_REQUIRES );
		$this->ignore_discounts = $this->get_option( self::METHOD_FREE_IGN_DISC );

		$this->tax_status         = $this->get_instance_option( self::METHOD_TAXABLE );

		add_action( 'admin_footer', array( 'Esraw_Shipping_Easy_Rate', 'enqueue_admin_js' ), 10 ); // Priority needs to be higher than wc_print_js (25).
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
				'title' => __( 'General Settings', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::METHOD_TITLE               => array(
				'title'       => __( 'Method Title', 'esraw-woo' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'esraw-woo' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			self::METHOD_DESCRIPTION         => array(
				'title'       => __( 'Description', 'esraw-woo' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_TAXABLE             => array(
				'title'    => __( 'Tax Status', 'esraw-woo' ),
				'type'     => 'select',
				'default'  => 'taxable',
				'options'  => array(
					'taxable' => __( 'Taxable', 'esraw-woo' ),
					'none'    => _x( 'None', 'Tax status', 'esraw-woo' ),
				),
				'desc_tip' => __( 'Apply tax or no.', 'esraw-woo' ),
				'desc_tip' => true,
			),

			'section_free_shipping'          => array(
				'title' => __( 'Free Shipping', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::METHOD_FREE_REQUIRES       => array(
				'title'   => __( 'Free shipping requires...', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'options' => array(
					''           => __( 'N/A', 'woocommerce' ),
					'coupon'     => __( 'A valid free shipping coupon', 'woocommerce' ),
					'min_amount' => __( 'A minimum order amount', 'woocommerce' ),
					'either'     => __( 'A minimum order amount OR a coupon', 'woocommerce' ),
					'both'       => __( 'A minimum order amount AND a coupon', 'woocommerce' ),
				),
				'description' => __( 'Condition for free shipping. Choose N/A to disable it.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_FREE_MIN_AMOUNT       => array(
				'title'       => __( 'Minimum order amount', 'woocommerce' ),
				'type'        => 'price',
				'placeholder' => wc_format_localized_price( 0 ),
				'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce' ),
				'default'     => '0',
				'desc_tip'    => true,
			),
			self::METHOD_FREE_IGN_DISC       => array(
				'title'       => __( 'Coupons discounts', 'woocommerce' ),
				'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			self::METHOD_FREE_SHIPPING_LABEL => array(
				'title'       => __( 'Free Shipping Label', 'esraw-woo' ),
				'type'        => 'text',
				'default'     => __( 'Free', 'esraw-woo' ),
				'description' => __( 'Enter free shipping label.', 'esraw-woo' ),
				'desc_tip'    => true,
			),

			'section_advanced_options'       => array(
				'title' => __( 'Advanced Options', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::METHOD_VISIBILITY          => array(
				'title'       => __( 'Visibility', 'esraw-woo' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => ' ',
				'description' => __( 'Show only for logged in users.', 'esraw-woo' ),
			),
			self::METHOD_MINIMUM_COST        => array(
				'title'       => __( 'Shipping minimum cost', 'esraw-woo' ),
				'type'        => 'price',
				'label'       => ' ',
				'default'     => null,
				'description' => __( 'Enter the minimum price for this shipment.', 'esraw-woo' ),
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
		$cost                       = 0;
		$label                      = $this->title;
		if ( $this->is_free_shipping ) {
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

	/**
	 * See if free shipping is available based on the package and cart.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package ) {
		$has_coupon         = false;
		$has_met_min_amount = false;

		if ( in_array( $this->requires, array( 'coupon', 'either', 'both' ), true ) ) {
			$coupons = WC()->cart->get_coupons();

			if ( $coupons ) {
				foreach ( $coupons as $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
			}
		}

		if ( in_array( $this->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = $total - WC()->cart->get_discount_tax();
			}

			if ( 'no' === $this->ignore_discounts ) {
				$total = $total - WC()->cart->get_discount_total();
			}

			$total = NumberUtil::round( $total, wc_get_price_decimals() );

			if ( $total >= $this->min_amount ) {
				$has_met_min_amount = true;
			}
		}

		switch ( $this->requires ) {
			case 'min_amount':
				$is_available = $has_met_min_amount;
				break;
			case 'coupon':
				$is_available = $has_coupon;
				break;
			case 'both':
				$is_available = $has_met_min_amount && $has_coupon;
				break;
			case 'either':
				$is_available = $has_met_min_amount || $has_coupon;
				break;
			default:
				$is_available = false;
				break;
		}
		if ( $is_available ) {
			$this->is_free_shipping = true;
		} else {
			$is_available = parent::is_available( $package );
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Enqueue JS to handle free shipping options.
	 *
	 * Static so that's enqueued only once.
	 */
	public static function enqueue_admin_js() {
		wc_enqueue_js(
			"jQuery( function( $ ) {
				function wcFreeShippingShowHideMinAmountField( el ) {
					var form = $( el ).closest( 'form' );
					var minAmountField = $( '#woocommerce_esraw_min_amount', form ).closest( 'tr' );
					var ignoreDiscountField = $( '#woocommerce_esraw_ignore_discounts', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
						ignoreDiscountField.hide();
					} else {
						minAmountField.show();
						ignoreDiscountField.show();
					}
				}

				$( document.body ).on( 'change', '#woocommerce_esraw_requires', function() {
					wcFreeShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#woocommerce_esraw_requires' ).change();
				$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
					if ( 'wc-modal-shipping-method-settings' === target ) {
						wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_esraw_requires', evt.currentTarget ) );
					}
				} );
			});"
		);
	}
}
