<?php
/*
Plugin Name: po_gateway
Plugin URI: https://github.com/jonalvarezz/po_gateway
Description: Pagos Online Gateway for Jigoshop in Wordpress
Version: 0.1
Requires at least: 3.3
Tested up to: 3.5
Required Jigoshop Version: 1.3
Text Domain: po_gateway
Domain Path: /languages/
Author: @jonalvarezz
Author URI: http://tunegocioweben1dia.com
*/

add_action( 'plugins_loaded', 'init_po_gateway' );
function init_po_gateway() {
	
	// Sometimes Jigoshop is de-activated - do nothing without it
	if ( ! class_exists( 'jigoshop' )) return;

	// Add the gateway to JigoShop
	function add_po_gateway( $methods ) {
		$methods[] = 'po_gateway';
		return $methods;
	}
	add_filter( 'jigoshop_payment_gateways', 'add_po_gateway', 3 );
	// NOTE: the priority on the above filter determines Settings and Checkout appearance order
	// Other gateways may use the same priority and then it's the order that WordPress loads the plugins
	
	/**
	 * Class definition for this gateway which Jigoshop will instantiate when it needs to
	 * This will usually be done on the WordPress 'init' action hook
	 * We will load text domains for languages in the constructor as these also need to be on 'init'
	 */
	class po_gateway extends jigoshop_payment_gateway {
	
		public function __construct() {
			
			// load our text domains first for translations (constructor is called on the 'init' action hook)
			load_plugin_textdomain( 'po_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			
			parent::__construct(); // now, construct the parent to get our options installed and translated
			
			// and initialize all our variables based on settings here
			$this->id			= 'po_gateway';
	  		$this->enabled		= Jigoshop_Base::get_options()->get_option('po_gateway_enabled');
	  		
		}
		

		/**
		 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
		 * Jigoshop will install, display, validate and save changes for our provided default options
		 *
		 * These will be installed on the Settings 'Payment Gateways' tab by the parent class
		 * 
		 * See 'jigoshop/classes/jigoshop_options.class.php' for details on various option types
		 *
		 */	
		protected function get_default_options() {
	
			$defaults = array();
		
			// Define the Section name for the Jigoshop_Options
			$defaults[] = array( 'name' => __('PO Gateway', 'po_gateway'), 'type' => 'title', 'desc' => __('Pagos Online Gateway for Jigoshop in Wordpress.', 'po_gateway') );
		
			// List each option in order of appearance with details
			$defaults[] = array(
				'name'		=> __('Enable PO Gateway','po_gateway'),
				'desc' 		=> __('Enable pagos online gateway.','po_gateway'),
				'tip' 		=> __('Enable pagos online gateway.','po_gateway'),
				'id' 		=> 'po_gateway_enabled',
				'std' 		=> 'no', /* newly added gateways to a site should be disabled by default */
				'type' 		=> 'checkbox',
				'choices'	=> array(
					'no'			=> __('No', 'po_gateway'),
					'yes'			=> __('Yes', 'po_gateway')
				)
			);
			
			return $defaults;
			
		}
		
		/**
		 * All other gateway specific functions should follow here
		 */
		 
		 
	}   /* End of Class definition for the Gateway */
	
}   /* End of init gateway function */