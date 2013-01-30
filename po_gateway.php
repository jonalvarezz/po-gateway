<?php
/*
Plugin Name: Jigoshop Pagos Online
Plugin URI: https://github.com/jonalvarezz/po_gateway
Description: Pagos Online Gateway for Jigoshop in Wordpress
Version: 0.1
Requires at least: 3.3
Tested up to: 3.5
Required Jigoshop Version: 1.3
Text Domain: po_gateway
Domain Path: /languages/
Author: Tu Negocio Web en 1 dia - @jonalvarezz
Author URI: http://tunegocioweben1dia.com
*/

add_action( 'plugins_loaded', 'init_po_gateway' );
function init_po_gateway() {
	
	// Sometimes Jigoshop is de-activated - do nothing without it
	if ( ! class_exists( 'jigoshop' )) return;

	// Add the gateway to JigoShop
	function add_po_gateway( $methods ) {
		$methods[] = 'PagosOnline_Gateway';
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
	class PagosOnline_Gateway extends jigoshop_payment_gateway {
	
		public function __construct() {

			// load our text domains first for translations (constructor is called on the 'init' action hook)
			load_plugin_textdomain( 'po_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			
			parent::__construct();

			$this->id			= 'pagosonline';
			$this->icon 		= plugins_url( 'images/pagosonline_logo.png', __FILE__ );
			$this->liveurl 		= 'https://gateway.pagosonline.net/apps/gateway/index.html';
			$this->testurl 		= 'https://gateway2.pagosonline.net/apps/gateway/index.html';

			$this->enabled		= Jigoshop_Base::get_options()->get_option('po_gateway_enabled');
			$this->testmode		= Jigoshop_Base::get_options()->get_option('po_gateway_testmode');
			$this->title 		= Jigoshop_Base::get_options()->get_option('po_gateway_title');			
			$this->description 	= Jigoshop_Base::get_options()->get_option('po_gateway_description');	  		
	  		$this->userid		= Jigoshop_Base::get_options()->get_option('po_gateway_userid');
	  		$this->key			= Jigoshop_Base::get_options()->get_option('po_gateway_key');	  		

			// Actions
			// add_action('init', array(&$this, 'check_ipn_response') );
			// add_action('valid-po-ipn-request', array(&$this, 'successful_request') );
			add_action('receipt_pagosonline', array(&$this, 'receipt_page'));
	  		
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
			$defaults[] = array(
				'name' => __('Pagos Online', 'po_gateway'),
				'type' => 'title',
				'desc' => __('Pagos Online Gateway for Jigoshop in Wordpress.', 'po_gateway')
			);
		
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

			$defaults[] = array(
				'name'		=> __('Modo Prueba','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('Al activarse las transacciones serán de prueba.','po_gateway'),
				'id' 		=> 'po_gateway_testmode',
				'std' 		=> 'no',
				'type' 		=> 'checkbox',
				'choices'	=> array(
					'no'			=> __('No', 'po_gateway'),
					'yes'			=> __('Yes', 'po_gateway')
				)
			);

			$defaults[] = array(
				'name'		=> __('Method Title','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('This controls the title which the user sees during checkout.','po_gateway'),
				'id' 		=> 'po_gateway_title',
				'std' 		=> __('Pagos Online','po_gateway'),
				'type' 		=> 'text'
			);

			$defaults[] = array(
				'name'		=> __('Descripcion','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('Esta es la descripcion que el usuario ve durante el checkout.','po_gateway'),
				'id' 		=> 'po_gateway_description',
				'std' 		=> __('Pagar via Pagos Online', 'po_gateway'),
				'type' 		=> 'longtext'
			);

			$defaults[] = array(
				'name'		=> __('ID de usuario','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('Este numero lo encontrará en el correo de confirmación de la creación de su cuenta de Pagos Online.','po_gateway'),
				'id' 		=> 'po_gateway_userid',
				'std' 		=> __('2','po_gateway'),
				'type' 		=> 'text'
			);
		
			
			$defaults[] = array(
				'name'		=> __('Llave Encripción','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('Consulte su llave a través del módulo administrativo del sistema dado por Pagos Online.','po_gateway'),
				'id' 		=> 'po_gateway_key',
				'std' 		=> __('1111111111111111','po_gateway'),
				'type' 		=> 'text'
			);

			return $defaults;
			
		}


		public function generate_po_form( $order_id ) {

			$order = new jigoshop_order( $order_id );
	        
	        $gateway_url = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

			$po_args = array(

				// Pagos Online API
				'usuarioId'				=> $this->userid,
				'firma'					=> $this->key,
				'refVenta'				=> $order->order_key,
				'descripcion'			=> '',
				'valor'					=> $order->order_total,				
				'url_respuesta'			=> '',
				'moneda'				=> 'COP',

				// Let Pagos Online system generate iva and baseDevolucionIva automatically.
				'iva'					=> '',
				'baseDevolucionIva'		=> '',

				// Optional info for Pagos Online
				'nombreComprador'		=> $order->billing_first_name,
				'emailComprador'		=> $order->billing_email,

				'last_name'				=> $order->billing_last_name,
				'company'				=> $order->billing_company,
				'address1'				=> $order->billing_address_1,
				'address2'				=> $order->billing_address_2,
				'city'					=> $order->billing_city,
				'state'					=> $order->billing_state,
				'zip'					=> $order->billing_postcode,
				'country'				=> $order->billing_country,
				
			);

			$po_args['firma'] = $this->gen_digital_sign($po_args);
			$po_args_array = array();

			foreach ($po_args as $key => $value) {
				$po_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
			}

			return '<form action="'. esc_url( $gateway_url ) .'" method="post" id="po_payment_form">
					' . implode('', $po_args_array) . '
					<input type="submit" class="button-alt" id="submit_payment_form" value="'.__('Pagar via Pagos Online', 'po_gateway').'" /> <a class="button cancel" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'po_gateway').'</a>
					
				</form>';

		}

		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {

			$order = new jigoshop_order( $order_id );

			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
			);

		}
		
		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {

			echo '<p>'.__('Gracias por tu orden, por favor de clic en el botón de abajo para pagar por medio de Pagos Online.', 'po_gateway').'</p>';

			echo $this->generate_po_form( $order );

		}

		/**
		 * Successful Payment!
		 **/
		function successful_request( $posted ) {			
			

		}

		/**
		 * There are no payment fields for paypal, but we want to show the description if set.
		 **/
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}

		/**
		 * Digital sign requeried for Pagos Online Api
		 **/
		function gen_digital_sign( $data ) {
			$uid = $data['usuarioId'];
			$sign = $data['firma'];
			$rventa = $data['refVenta'];
			$valor = $data['valor'];
			$mon = 'COP';

			$sign_s = "$sign~$uid~$rventa~$valor~$mon";

			return md5( $sign_s );
		}
		 
	}   /* End of Class definition for the Gateway */
	
}   /* End of init gateway function */