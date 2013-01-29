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
			
			parent::__construct();

			$this->id			= 'po_gateway';
			$this->title 		= 'Pagos Online';
	  		$this->enabled		= Jigoshop_Base::get_options()->get_option('po_gateway_enabled');
	  		$this->id			= Jigoshop_Base::get_options()->get_option('po_gateway_id');
	  		$this->key			= Jigoshop_Base::get_options()->get_option('po_gateway_key');
	  		$this->testmode		= Jigoshop_Base::get_options()->get_option('po_gateway_testmode');

	  		$this->liveurl 		= 'https://gateway.pagosonline.net/apps/gateway/index.html';
			$this->testurl 		= 'https://gateway2.pagosonline.net/apps/gateway/index.html';

			add_action('receipt_po_gateway', array(&$this, 'receipt_page'));
	  		
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
				'name'		=> __('ID de usuario','po_gateway'),
				'desc' 		=> '',
				'tip' 		=> __('Este numero lo encontrará en el correo de confirmación de la creación de su cuenta de Pagos Online.','po_gateway'),
				'id' 		=> 'po_gateway_id',
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
	        
	        $subtotal = (float)(Jigoshop_Base::get_options()->get_option('jigoshop_prices_include_tax') == 'yes' ? (float)$order->order_subtotal + (float)$order->order_tax : $order->order_subtotal);

	        $gateway_url = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

			// filter redirect page
			$checkout_redirect = apply_filters( 'jigoshop_get_checkout_redirect_page_id', jigoshop_get_page_id('thanks') );

			$po_args = array(
				'return' 				=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink( $checkout_redirect ))),
				'cancel_return'			=> $order->get_cancel_order_url(),

				// Pagos Online API
				'usuarioId'				=> $this->id,
				'refVenta'				=> $order->order_key,
				'descripcion'			=> 'descripcion',
				'valor'					=> $order->order_total,
				'iva'					=> ($order->order_total/1.16),
				'baseDevolucionIva'		=> $order->order_total - ($order->order_total/1.16),
				'firma'					=> md5("$llave_encripcion~$usuarioId~$refVenta~$valor~$moneda"),
				'url_respuesta'			=> '',
				'moneda'				=> 'COP',

				// Address info
				'first_name'			=> $order->billing_first_name,
				'last_name'				=> $order->billing_last_name,
				'company'				=> $order->billing_company,
				'address1'				=> $order->billing_address_1,
				'address2'				=> $order->billing_address_2,
				'city'					=> $order->billing_city,
				'state'					=> $order->billing_state,
				'zip'					=> $order->billing_postcode,
				'country'				=> $order->billing_country,
				'email'					=> $order->billing_email,
			);

			// Cart Contents
			$item_loop = 0;
			if (sizeof($order->items)>0) : foreach ($order->items as $item) :

				$_product = $order->get_product_from_item( $item );

				if ($_product->exists() && $item['qty']) :

					$item_loop++;

					$title = $_product->get_title();

					//if variation, insert variation details into product title
					if ($_product instanceof jigoshop_product_variation) {

						$title .= ' (' . jigoshop_get_formatted_variation( $item['variation'], true) . ')';

					}

					$po_args['item_name_'.$item_loop] = $title;
					$po_args['quantity_'.$item_loop] = $item['qty'];

					$po_args['amount_'.$item_loop] = number_format( apply_filters( 'jigoshop_paypal_adjust_item_price' ,$_product->get_price_excluding_tax(), $item, 10, 2 ), 2); //Apparently, Paypal did not like "28.4525" as the amount. Changing that to "28.45" fixed the issue.
				endif;
			endforeach; endif;

			$po_args = apply_filters( 'jigoshop_paypal_args', $po_args );

			$po_args_array = array();

			foreach ($po_args as $key => $value) {
				$paypal_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
			}

			return '<form action="'.$gateway_url.'" method="post" id="po_payment_form">
					' . implode('', $po_args_array) . '
					<input type="submit" class="button-alt" id="submit_po_payment_form" value="'.__('Pagar via Pagos Online', 'jigoshop').'" /> <a class="button cancel" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'jigoshop').'</a>
					<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block(
								{
									message: "<img src=\"'.jigoshop::assets_url().'/assets/images/ajax-loader.gif\" alt=\"Redireccionando...\" />'.__('Gracias por tu orden. Ahora te estamos redireccionando a Pagos Online para realizar el pago.', 'jigoshop').'",
									overlayCSS:
									{
										background: "#fff",
										opacity: 0.6
									},
									css: {
										padding:		20,
										textAlign:	  "center",
										color:		  "#555",
										border:		 "3px solid #aaa",
										backgroundColor:"#fff",
										cursor:		 "wait"
									}
								});
							jQuery("#submit_po_payment_form").click();
						});
					</script>
				</form>';

		}

		
		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {

			echo '<p>'.__('Gracias por tu orden, por favor de clic en el botón de abajo para pagar por medio de Pagos Online.', 'jigoshop').'</p>';

			echo $this->generate_po_form( $order );

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
		
		public function admin_scripts() {
	    	?>
			<script type="text/javascript">
				/*<![CDATA[*/
					jQuery(function($) {
						jQuery('input#po_gateway_testmode').click( function() {;
							if (jQuery(this).is(':checked')) {
								jQuery(this).parent().parent().next('tr').show();
							} else {
								jQuery(this).parent().parent().next('tr').hide();
							}
						});
					});
				/*]]>*/
			</script>
	    	<?php
	    }
		 
	}   /* End of Class definition for the Gateway */
	
}   /* End of init gateway function */