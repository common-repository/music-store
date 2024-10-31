<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

class WC_Product_MS_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Product_Data_Store_Interface {

	public function read( &$product ) {
		$product->set_defaults();
	} // End read

} // End WC_Product_MS_Data_Store_CPT
