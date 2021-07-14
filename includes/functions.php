<?php

function esraw_get_user_roles() {
	global $wp_roles;
	$roles               = $wp_roles->get_names();
	$roles ['not_login'] = 'Guest';
	return $roles;
}

function esraw_get_cart_volume() {
	// Initializing variables
	$volume = $rate = 0;

	$dimension_unit = get_option( 'woocommerce_dimension_unit' );

	// Calculate the rate to be applied for volume in cm3
	if ( 'mm' === $dimension_unit ) {
		$rate = pow( 10, 3 );
	} elseif ( 'cm' === $dimension_unit ) {
		$rate = 1;
	} elseif ( 'm' === $dimension_unit ) {
		$rate = pow( 10, -6 );
	}

	if ( 0 === $rate ) {
		return false; // Exit
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		$qty     = $cart_item['quantity'];

		$length = $product->get_length();
		$width  = $product->get_width();
		$height = $product->get_height();

		$volume += (float) $length * (float) $width * (float) $height * $qty;
	}
	return $volume / $rate;
}

function esraw_get_shipping_zones_list_for_import() {
	global $wpdb;
	$results   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones" );
	$to_return = array();
	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$to_return[ $result->zone_id ] = $result->zone_name;
		}
	}
	return $to_return;
}

function esraw_get_shipping_list_for_export() {
	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id=%s", Esraw_Shipping_Easy_Rate::METHOD_ID )
	);

	$to_return = array();
	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$option = get_option( 'woocommerce_' . Esraw_Shipping_Easy_Rate::METHOD_ID . '_' . $result->instance_id . '_settings' );
			if ( is_array( $option ) && isset( $option['method_title'] ) ) {
				$to_return[ $result->instance_id ] = $option['method_title'];
			} else {
				// wp_die( __( 'Couldn\'t get shipping list', 'esraw-woo' ) );
			}
		}
	}
	return $to_return;
}
