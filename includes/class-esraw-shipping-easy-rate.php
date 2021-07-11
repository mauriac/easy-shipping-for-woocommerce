<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-includes/pluggable.php'; // for nonce
use Automattic\WooCommerce\Utilities\NumberUtil;

class Esraw_Shipping_Easy_Rate extends WC_Shipping_Method {
	const METHOD_TITLE               = 'method_title';
	const METHOD_DESCRIPTION         = 'method_description';
	const METHOD_TAXABLE             = 'method_taxable';
	const METHOD_START_DATE          = 'method_start_date';
	const METHOD_END_DATE            = 'method_end_date';
	const METHOD_FREE_SHIPPING_COST  = 'method_free_shipping_cost';
	const METHOD_FREE_REQUIRES       = 'method_free_requires';
	const METHOD_FREE_MIN_AMOUNT     = 'method_free_min_amount';
	const METHOD_FREE_IGN_DISC       = 'method_free_ignore_discounts';
	const METHOD_FREE_USER_POSTCODE  = 'method_free_user_postcode';
	const METHOD_FREE_SHIPPING_LABEL = 'method_free_shipping_label';
	const METHOD_FREE_NOTIFICATION   = 'method_free_shipping_notification';
	const METHOD_VISIBILITY          = 'method_visibility';
	const METHOD_DEFAULT             = 'method_DEFAULT';
	const METHOD_MINIMUM_COST        = 'method_minimum_cost';
	const METHOD_MAXIMUM_COST        = 'method_maximum_cost';
	const METHOD_ESTIMATED_DELIVERY  = 'method_estimated_delivery';
	const METHOD_RULE_CALCULATION    = 'method_rule_calculation';
	const METHOD_DIM_FACTOR          = 'method_dim_factor';
	const METHOD_ID                  = 'esraw';
	const CONFIG_HIDE_ALL            = 'config_hide_method';
	const METHOD_EXPORT_FIELD        = 'method_export_field';

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
	 * Requires postcodes.
	 *
	 * @var string
	 */
	private $postcode_need = '';

	/**
	 * Whether or not is free shipping.
	 *
	 * @var string
	 */
	public $is_free_shipping;

	const CONDITION_CHOICES = array(
		'Cart'                => array(
			'Subtotal'                => 'subtotal',
			'Subtotal ex. taxes'      => 'subtotal_ex',
			'Quantity'                => 'quantity',
			'Cart line item'          => 'cart_line_item',
			'Contains shipping class' => 'contains_shipping_class',
		),
		'Weight & Dimensions' => array(
			'Weight'             => 'weight',
			'Volume'             => 'volume',
			'Dimensional weight' => 'dim_weight',
			// 'Max dimension' => 'max_dim',
			// 'Total overall dimensions' => 'total_dim',
		),
		'User Details'        => array(
			'Zipcode'   => 'zipcode',
			'City'      => 'city',
			// 'Country'   => 'country',
			'User role' => 'user_roles',
		),
	);
	const Operator           = array(
		'is'     => 'is',
		'is_not' => 'is not',
	);
	const CONDITIONS_ACTIONS = array(
		'none'      => 'None',
		'stop'      => 'Stop',
		'cancel'    => 'Cancel',
		'free_ship' => 'Free Shipping',
	);

	/**
	 *
	 */
	private $conditions_option_key;

	/**
	 *
	 */
	private $conditions_key;

	/**
	 *
	 */
	private $conditions_options;

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

		$this->id = self::METHOD_ID;
		$this->init();

		$this->method_title       = __( 'Easy Shipping', 'esraw-woo' );
		$this->method_description = __( 'Easy way to define your shipping method', 'esraw-woo' );
		$this->enabled            = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
		$this->title              = $this->get_option( self::METHOD_TITLE, $this->method_title );
		$this->min_amount         = $this->get_option( self::METHOD_FREE_MIN_AMOUNT, 0 );
		$this->postcode_need      = $this->get_option( self::METHOD_FREE_USER_POSTCODE, null );
		$this->requires           = $this->get_option( self::METHOD_FREE_REQUIRES );
		$this->ignore_discounts   = $this->get_option( self::METHOD_FREE_IGN_DISC );

		$this->tax_status = $this->get_option( self::METHOD_TAXABLE );

		$this->conditions_key        = 'easy_rate';
		$this->conditions_option_key = $this->id . $this->instance_id;

		if ( isset( $_POST['esr_securite_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['esr_securite_nonce'] ), 'esr-security' ) ) {
			update_option( $this->conditions_option_key, ( isset( $_POST[ $this->conditions_key ] ) && is_array( $_POST[ $this->conditions_key ] ) ) ? $_POST[ $this->conditions_key ] : array() );
		}
		$this->conditions_options = get_option( $this->conditions_option_key, array() );

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
			self::METHOD_START_DATE          => array(
				'title'       => __( 'Start Date', 'esraw-woo' ),
				'type'        => 'date',
				'default'     => null,
				'description' => __(
					'When to apply this method.(Optional)',
					'esraw-woo'
				),
				'desc_tip'    => true,
			),
			self::METHOD_END_DATE            => array(
				'title'       => __( 'End Date', 'esraw-woo' ),
				'type'        => 'date',
				'default'     => null,
				'description' => __(
					'When to stop this method.(Optional)',
					'esraw-woo'
				),
				'desc_tip'    => true,
			),

			'section_free_shipping'          => array(
				'title' => __( 'Free Shipping', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::METHOD_FREE_REQUIRES       => array(
				'title'       => __( 'Free shipping requires', 'woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => '',
				'options'     => array(
					''           => __( 'N/A', 'woocommerce' ),
					'postcode'   => __( 'A user postcode', 'esraw-woo' ),
					'coupon'     => __( 'A valid free shipping coupon', 'woocommerce' ),
					'min_amount' => __( 'A minimum order amount', 'woocommerce' ),
					'either'     => __( 'A minimum order amount OR a coupon', 'woocommerce' ),
					'both'       => __( 'A minimum order amount AND a coupon', 'woocommerce' ),
				),
				'description' => __( 'Condition for free shipping. Choose N/A to disable it.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_FREE_MIN_AMOUNT     => array(
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
			self::METHOD_FREE_USER_POSTCODE  => array(
				'title'       => __( 'User Postcode', 'esraw-woo' ),
				'type'        => 'text',
				'placeholder' => 'postcode1,postcode2,etc',
				'description' => __( 'Users with this Postal Code will get free shipping (if enabled above). To match several post codes separate them with commas', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_FREE_NOTIFICATION   => array(
				'title'       => __( 'Free Shipping Notification', 'esraw-woo' ),
				'type'        => 'checkbox',
				'description' => __( 'Let your customer know how much more to pay to get free shipping', 'esraw-woo' ),
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
				'title'   => __( 'Visibility', 'esraw-woo' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => 'Show only for logged in users.',
				// 'description' => __( 'Show only for logged in users.', 'esraw-woo' ),
				// 'desc_tip' => true,
			),
			self::METHOD_DEFAULT             => array(
				'title'   => __( 'Default', 'esraw-woo' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => 'Set this Method as the default selected choice on the cart page.',
				// 'description' => __( 'Set this option as the default selected choice on the cart page.', 'esraw-woo' ),
				// 'desc_tip' => true,
			),
			self::METHOD_MINIMUM_COST        => array(
				'title'       => __( 'Shipping minimum cost', 'esraw-woo' ),
				'type'        => 'price',
				'label'       => ' ',
				'default'     => null,
				'description' => __( 'Enter the minimum price for this shipment.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_MAXIMUM_COST        => array(
				'title'       => __( 'Shipping maximum cost', 'esraw-woo' ),
				'type'        => 'price',
				'label'       => ' ',
				'default'     => null,
				'description' => __( 'Enter the maximum price for this shipment.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_ESTIMATED_DELIVERY  => array(
				'title'       => __( 'Estimated days for delivery', 'esraw-woo' ),
				'type'        => 'number',
				'default'     => '',
				'description' => __( 'Estimated days for delivery. If filled, user will be notified of its delivery date.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_RULE_CALCULATION    => array(
				'title'       => __( 'Rules Calculation', 'esraw-woo' ),
				'type'        => 'select',
				'default'     => 'sum',
				'options'     => array(
					'sum'     => __( 'Sum', 'esraw-woo' ),
					'lowest'  => __( 'Lowest cost', 'esraw-woo' ),
					'highest' => __( 'Highest cost', 'esraw-woo' ),
				),
				'description' => __( 'Select how rules will be calculated.', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			self::METHOD_DIM_FACTOR          => array(
				'title'       => __( 'DIM Factor', 'esraw-woo' ),
				'type'        => 'number',
				'default'     => null,
				'description' => __(
					'Filling in the DIM Factor value in this field is required if ' .
					'you use the When: Dimensional weight condition to calculate the shipping cost. ' .
					'What\'s more, all the products in your shop should have their dimensions entered.',
					'esraw-woo'
				),
				'desc_tip'    => true,
			),
		);

		$this->instance_form_fields = $settings;

		$form_sets         = array(
			'config_general'          => array(
				'title' => __( 'General Settings', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::CONFIG_HIDE_ALL     => array(
				'title'       => __( 'Hide method', 'esraw-woo' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array(
					''         => __( 'N/A', 'esraw-woo' ),
					'hide_all' => __( 'Show only "Free Shipping"', 'esraw-woo' ),
				),
				'description' => __( 'this option not only includes the methods of this plugin but also the free shipping of woocommerce', 'esraw-woo' ),
				'desc_tip'    => true,
			),
			'section_export_ship'     => array(
				'title' => __( 'Export shipping Method', 'esraw-woo' ),
				'type'  => 'title',
			),
			self::METHOD_EXPORT_FIELD => array(
				'title'       => __( 'Export', 'esraw-woo' ),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'default'     => '',
				'options'     => self::get_shipping_list_for_export(),
				'description' => __( 'Select methods that will be export', 'esraw-woo' ),
				'desc_tip'    => true,
			),
		);
		$this->form_fields = $form_sets;
	}

	public static function get_shipping_list_for_export() {
		global $wpdb;
		$results   = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id=%s", self::METHOD_ID )
		);
		$to_return = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$option = get_option( 'woocommerce_' . self::METHOD_ID . '_' . $result->instance_id . '_settings' );
				if ( is_array( $option ) && isset( $option['method_title'] ) ) {
					$to_return[ $result->instance_id ] = $option['method_title'];
				} else {
					wp_die( __( 'Couldn\'t get shipping list', 'esraw-woo' ) );
				}
			}
		}
		return $to_return;
	}

	/**
	 * Return admin options as a html string.
	 *
	 * @return string
	 */
	public function get_admin_options_html() {
		if ( $this->instance_id ) {
			$settings_html = $this->instance_options();
		} else {
			$settings_html = $this->generate_settings_html( $this->get_form_fields() );
			?>
			<tr valign="top">
				<td>
					<button id="esraw_export_btn" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Export', 'esraw-woo' ); ?>"><?php esc_html_e( 'Export', 'esraw-woo' ); ?></button>
				</td>
			</tr>
			<?php
			wp_enqueue_script( 'esraw-ship-1', plugin_dir_url( __FILE__ ) . 'js/esraw-ship.js', array( 'jquery' ), 'ESRAW_VERSION', false );
		}
		return '<table class="form-table">' . $settings_html . '</table>';
	}

	public function get_export_file() {

			$export_data = filter_input( INPUT_POST, 'export_data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

			$records = array();
		foreach ( $export_data as $key => $ship_instance_id ) {
			$method_instance  = get_option( 'woocommerce_' . self::METHOD_ID . '_' . $ship_instance_id . '_settings' );
			$method_condition = get_option( self::METHOD_ID . $ship_instance_id );
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
			exit;
	}

	public function export_esraw_ship() {

		// get export data from file
			// $row = 1;
			// if (($handle = fopen("/Applications/MAMP/htdocs/wad_pro/wp-content/plugins/easy-shipping-rate/includes/phpzag_csv_export_20210710.csv", "r")) !== FALSE) {
			// while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			// $num = count($data);
			// echo "<p> $num champs à la ligne $row: <br /></p>\n";
			// $row++;
			// $data_decode = json_decode( current( $data ) );
			// var_dump($data, "decoded", $data_decode);
			// for ($c=0; $c < $num; $c++) {
			// echo $data[$c] . "<br />\n";
			// }
			// }
			// fclose($handle);
			// }
			// die;

			// $csv_file = "phpzag_csv_export_".date('Ymd') . ".csv";
			// header("Content-Type: text/csv");
			// header("Content-Disposition: attachment; filename=\"$csv_file\"");
			// $fh = fopen( 'php://output', 'w' );
			// $is_coloumn = true;
			// if(!empty($records)) {
			// foreach($records as $record) {
			// foreach ($record as $key => $rec) {
			// fputcsv($fh, array_keys($rec));
			// fputcsv($fh, $rec);
			// }
			// if($is_coloumn) {
			// fputcsv($fh, array_keys($record));
			// $is_coloumn = false;
			// }
			// var_dump($record);
			// die;
			// fputcsv($fh, $record);
			// }
			// fclose($fh);
			// }
			// exit;

		// $export_data       = filter_input( INPUT_POST, 'export_data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// $connection_key = filter_input( INPUT_POST, 'connection_key' );
	}

	/**
	 * admin_options function.
	 */
	public function instance_options() {
			$this->generate_settings_html( $this->get_instance_form_fields() );
		?>
			<tr>
				<td>
				<h2><?php esc_attr_e( 'Rules for calculating shipping costs', 'esr-woo' ); ?></h2>
				<input type="hidden" name="esr_securite_nonce" value="<?php echo esc_html( wp_create_nonce( 'esr-security' ) ); ?>"/>
					<table id="esr-woo-table" class="widefat">
						<thead>
							<tr>
								<th>
									<input type="checkbox" id="esr_remove_all_tr" class="esr_remove_all_tr">
								</th>
								<th>
									<?php esc_attr_e( 'Condition', 'esr-woo' ); ?>
								</th>
								<th>
									<?php esc_attr_e( 'Cost', 'esr-woo' ); ?>
								</th>
								<th>
									<?php esc_attr_e( 'Actions', 'esr-woo' ); ?>
								</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th colspan="2">
									<a href="#" class="button" id="esr-insert-new-row"><?php _e( 'Insert row', 'esr-woo' ); ?></a>
								</th>
								<th colspan="3">
									<a href="#" class="button" id="esr-remove-rows"><?php _e( 'Delete Selected Row(s)', 'esr-woo' ); ?></a>
								</th>
							</tr>
						</tfoot>
						<tbody>
							<?php foreach ( $this->conditions_options as $key => $condition ) : ?>
								<tr  data-key="<?php esc_attr_e( $key ); ?>" >
									<td>
										<input type="checkbox" data-num="<?php esc_attr_e( $key ); ?>" class="esr_remove_tr">
									</td>
									<td>
										<div class="easy_rate_condition_content" id="easy_rate_condition_content_<?php esc_attr_e( $key ); ?>">
											<select id="easy_rate_condition_<?php esc_attr_e( $key ); ?>" name="easy_rate[<?php esc_attr_e( $key ); ?>][condition]">
												<option></option>
												<?php foreach ( self::CONDITION_CHOICES as $groupe => $choices ) : ?>
													<optgroup label="<?php esc_attr_e( $groupe ); ?>">
														<?php foreach ( $choices as $choice_name => $choice_value ) : ?>
															<option value="<?php esc_attr_e( $choice_value ); ?>" <?php ( $choice_value === $condition['condition'] ) ? esc_attr_e( 'selected' ) : ''; ?>>
																<?php esc_attr_e( $choice_name ); ?>
															</option>
														<?php endforeach; ?>
													</optgroup>
												<?php endforeach; ?>
											</select>
											<span class="easy_rate_operator_content" id="easy_rate_operator_content_<?php esc_attr_e( $key ); ?>">
												<select id="easy_rate_operator_<?php esc_attr_e( $key ); ?>" name="easy_rate[<?php esc_attr_e( $key ); ?>][operator]" required>
													<?php foreach ( self::Operator as $op_key => $operat ) : ?>
														<option value="<?php esc_attr_e( $op_key ); ?>" <?php ( $op_key === $condition['operator'] ) ? esc_attr_e( 'selected' ) : ''; ?>>
															<?php esc_attr_e( $operat ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<?php if ( 'contains_shipping_class' === $condition['condition'] ) : ?>
													<select multiple style="overflow: scroll;height: 35px;" name="easy_rate[<?php esc_attr_e( $key ); ?>][choices][]" required="" id="esraw_ship_class">
														<?php foreach ( self::ship_classes_select_field() as $choice_key => $choice_class_value ) : ?>
															<option value="<?php esc_attr_e( $choice_key ); ?>" <?php in_array( $choice_key, $condition['choices'], true ) ? esc_attr_e( 'selected' ) : ''; ?>>
																<?php esc_attr_e( $choice_class_value ); ?>
															</option>
														<?php endforeach; ?>
													</select>
												<?php elseif ( 'user_roles' === $condition['condition'] ) : ?>
													<select multiple id="esraw_user_roles" style="overflow: scroll;height: 35px;" name="easy_rate[<?php esc_attr_e( $key ); ?>][choices][]" required="">
														<?php foreach ( esraw_get_user_roles() as $choice_role_key => $choice_role_name ) : ?>
															<option value="<?php esc_attr_e( $choice_role_key ); ?>" <?php in_array( $choice_role_key, $condition['choices'], true ) ? esc_attr_e( 'selected' ) : ''; ?>>
																<?php esc_attr_e( $choice_role_name ); ?>
															</option>
														<?php endforeach; ?>
													</select>
												<?php elseif ( 'zipcode' === $condition['condition'] ) : ?>
													<input type="text" placeholder="postcode1,postcode2,etc." name="easy_rate[<?php esc_attr_e( $key ); ?>][choices]" value="<?php esc_attr_e( $condition['choices'] ); ?>"/>
												<?php elseif ( 'city' === $condition['condition'] ) : ?>
													<input type="text" placeholder="city1,city2,etc." name="easy_rate[<?php esc_attr_e( $key ); ?>][choices]" value="<?php esc_attr_e( $condition['choices'] ); ?>"/>
												<?php else : ?>
													<input type="number"  step="0.01" value="<?php esc_attr_e( $condition['operand1'] ); ?>" placeholder="from" name="easy_rate[<?php esc_attr_e( $key ); ?>][operand1]"/>
													<input type="number" step="0.01" value="<?php esc_attr_e( $condition['operand2'] ); ?>" placeholder="to" name="easy_rate[<?php esc_attr_e( $key ); ?>][operand2]"/>
												<?php endif; ?>
												<?php
													$unit = '';
												if ( 'subtotal' === $condition['condition'] || 'subtotal_ex' === $condition['condition'] ) {
													$unit = get_woocommerce_currency_symbol();
												} elseif ( 'quantity' === $condition['condition'] || 'cart_line_item' === $condition['condition'] ) {
													$unit = 'qty';
												} elseif ( 'weight' === $condition['condition'] || 'dim_weight' === $condition['condition'] ) {
													$unit = 'kg';
												} elseif ( 'volume' === $condition['condition'] ) {
													$unit = 'cm<sup>3</sup>';
												} elseif ( 'max_dim' === $condition['condition'] || 'total_dim' === $condition['condition'] ) {
													$unit = 'cm';
												}
												?>
												<div class="easy_rate_unit"><?php echo $unit; ?> </div>
											</span>
										</div>
									</td>
									<td>
										<input type="number"  step="0.01" value="<?php esc_attr_e( $condition['cost'] ); ?>" name="easy_rate[<?php esc_attr_e( $key ); ?>][cost]" required/>
									</td>
									<td>
										<select id="easy_rate_action_<?php esc_attr_e( $key ); ?>" name="easy_rate[<?php esc_attr_e( $key ); ?>][action]">
											<?php foreach ( self::CONDITIONS_ACTIONS as $ac_key => $action ) : ?>
												<option value="<?php esc_attr_e( $ac_key ); ?>" <?php ( isset( $condition['action'] ) && $ac_key === $condition['action'] ) ? esc_attr_e( 'selected' ) : ''; ?>>
													<?php esc_attr_e( $action ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>
		<?php
	}

	public static function ship_classes_select_field() {
		$shipping_classes     = WC()->shipping()->get_shipping_classes();
		$ship_classes_options = array();
		foreach ( $shipping_classes as $shipping_class ) {
			$ship_classes_options[ $shipping_class->slug ] = $shipping_class->name;
		}
		return $ship_classes_options;
	}

	/**
	 * Finds and returns shipping classes in cart
	 *
	 * @param mixed $package Package of items from cart.
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class              = $values['data']->get_shipping_class();
				$found_shipping_classes[] = $found_class;
			}
		}

		return $found_shipping_classes;
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
		$cost  = 0;
		$label = $this->title;
		if ( $this->is_free_shipping ) {
			$cost  = 0;
			$label = $this->get_instance_option( self::METHOD_FREE_SHIPPING_LABEL, $this->title );
		} else {
			$cost           = $this->get_instance_option( self::METHOD_MINIMUM_COST, 0 );
			$conditions_ops = $this->conditions_options;
			$temp_cost      = 0;
			foreach ( $conditions_ops as $condition_row => $condition ) {
				$array_compa    = false;
				$value_to_check = null;
				if ( 'subtotal' === $condition['condition'] ) {
					$value_to_check = $package['cart_subtotal'];
				} elseif ( 'subtotal_ex' === $condition['condition'] ) {
					$value_to_check = WC()->cart->get_subtotal();
				} elseif ( 'quantity' === $condition['condition'] ) {
					$value_to_check = WC()->cart->get_cart_contents_count();
				} elseif ( 'cart_line_item' === $condition['condition'] ) {
					$value_to_check = count( WC()->cart->get_cart() );
				} elseif ( 'weight' === $condition['condition'] ) {
					$value_to_check = WC()->cart->get_cart_contents_weight();
				} elseif ( 'volume' === $condition['condition'] ) {
					$value_to_check = esraw_get_cart_volume();
				} elseif ( 'dim_weight' === $condition['condition'] ) {
					$cart_vol = esraw_get_cart_volume();
					$dim_fact = $this->get_option( self::METHOD_DIM_FACTOR );
					if ( $dim_fact ) {
						$value_to_check = $cart_vol / $dim_fact;
					}
				}
				// elseif ( 'max_dim' === $condition['condition'] ) {
				// $value_to_check = WC()->cart->get_cart_contents_weight();
				// } elseif ( 'total_dim' === $condition['condition'] ) {
				// $value_to_check = WC()->cart->get_cart_contents_weight();
				// }

				$can_get_cost = false;
				if ( 'contains_shipping_class' === $condition['condition'] ) {
					$find_ship_class = $this->find_shipping_classes( $package );
					$array_compa     = true;
					if ( array_intersect( $find_ship_class, $condition['choices'] ) ) {
						$can_get_cost = true;
					}
				} elseif ( 'user_roles' === $condition['condition'] ) {
					if ( is_user_logged_in() ) {
						$user         = wp_get_current_user();
						$current_role = (array) $user->roles;
					} else {
						$current_role = 'not_login';
					}

					$array_compa = true;
					if ( array_intersect( $current_role, $condition['choices'] ) ) {
						$can_get_cost = true;
					}
				} elseif ( 'zipcode' === $condition['condition'] ) {
					$customer_post_code = WC()->customer->get_shipping_postcode();
					$p_code_slice       = explode( ',', $condition['choices'] );

					$array_compa = true;
					if ( in_array( $customer_post_code, $p_code_slice, true ) ) {
						$can_get_cost = true;
					}
				} elseif ( 'city' === $condition['condition'] ) {
					$customer_city      = WC()->customer->get_shipping_city();
					$p_city_slice       = explode( ',', $condition['choices'] );
					$p_city_slice_lower = array_map(
						function( $p ) {
							return strtolower( $p );
						},
						$p_city_slice
					);

					$array_compa = true;
					if ( in_array( strtolower( $customer_city ), $p_city_slice_lower, true ) ) {
						$can_get_cost = true;
					}
				}

				if ( ! $array_compa ) {
					if ( ( $condition['operand1'] <= $value_to_check || '' === $condition['operand1'] ) && ( $condition['operand2'] >= $value_to_check || '' === $condition['operand2'] ) ) {
						$can_get_cost = true;
					}
				}

				if ( 'is_not' === $condition['operator'] ) {
					$can_get_cost = ! $can_get_cost;
				}

				if ( $can_get_cost ) {
					$cost_calculation = $this->get_instance_option( self::METHOD_RULE_CALCULATION, 'sum' );
					if ( 'sum' === $cost_calculation ) {
						$temp_cost += $condition['cost'];
					} elseif ( 'lowest' === $cost_calculation && $condition['cost'] < $temp_cost ) {
						$temp_cost = $condition['cost'];
					} elseif ( 'highest' === $cost_calculation && $condition['cost'] > $temp_cost ) {
						$temp_cost = $condition['cost'];
					}
				}

				if ( isset( $condition['action'] ) ) {
					if ( 'stop' === $condition['action'] ) {
						break;
					} elseif ( 'cancel' === $condition['action'] ) {
						return null;
					} elseif ( 'free_ship' === $condition['action'] ) {
						$cost      = 0;
						$temp_cost = 0;
						$label     = $this->get_instance_option( self::METHOD_FREE_SHIPPING_LABEL, $this->title );
					}
				}
			}
			$cost += $temp_cost;
		}
		$max_cost = $this->get_instance_option( self::METHOD_MAXIMUM_COST, 0 );
		if ( ! empty( $max_cost ) && $cost > $max_cost ) {
			$cost = $max_cost;
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
		$today_date = gmdate( 'Y-m-d' );
		$start_date = $this->get_option( self::METHOD_START_DATE, null );
		if ( $start_date && $today_date < $start_date ) {
			return;
		}

		$end_date = $this->get_option( self::METHOD_END_DATE, null );
		if ( $end_date && $end_date < $today_date ) {
			return;
		}

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

		$has_met_postcode = false;
		if ( in_array( $this->requires, array( 'postcode' ), true ) ) {
			$customer_post_code = WC()->customer->get_shipping_postcode();
			$p_code_slice       = explode( ',', $this->postcode_need );

			if ( in_array( $customer_post_code, $p_code_slice, true ) ) {
				$has_met_postcode = true;
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
			case 'postcode':
				$is_available = $has_met_postcode;
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
					var minAmountField = $( '#woocommerce_esraw_method_free_min_amount', form ).closest( 'tr' );
					var ignoreDiscountField = $( '#woocommerce_esraw_method_free_ignore_discounts', form ).closest( 'tr' );
					var userPostcodeField = $( '#woocommerce_esraw_method_free_user_postcode', form ).closest( 'tr' );
					var freeNotificationField = $( '#woocommerce_esraw_method_free_shipping_notification', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
						ignoreDiscountField.hide();
						userPostcodeField.hide();
						freeNotificationField.hide();
					} else {
						minAmountField.hide();
						ignoreDiscountField.hide();
						userPostcodeField.hide();
						freeNotificationField.hide();
						if ( 'postcode' === $( el ).val() ) {
							userPostcodeField.show();
						} else {
							minAmountField.show();
							ignoreDiscountField.show();
							freeNotificationField.show();
						}
					}
				}

				$( document.body ).on( 'change', '#woocommerce_esraw_method_free_requires', function() {
					wcFreeShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#woocommerce_esraw_method_free_requires' ).change();
			});"
		);
	}
}
