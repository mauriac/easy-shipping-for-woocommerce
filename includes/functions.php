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
