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

	  		$this->responsepage	= Jigoshop_Base::get_options()->get_option('po_gateway_response_page_id');  		


			// Actions
			// add_action('init', array(&$this, 'check_ipn_response') );
			// add_action('valid-po-ipn-request', array(&$this, 'successful_request') );
			add_action('receipt_pagosonline', array(&$this, 'receipt_page'));
	  		
		}

		/**
		 * Getter for Pagos Online messages cod for tipo_medio_pago code
		 * @var Int
		 * @return String
		 */	
		public function get_tipos_medios_de_pago( $index ) {
			$tipos_medios_pago = array(
				2 => 'Tarjetas de Crédito',
				3 => 'Verified by VISA',
				4 => 'PSE',
				5 => 'Debito ACH',
				7 => 'Pago en efectivo',
				8 => 'Pago referenciado'
			);

			if( !is_numeric($index) )
				return '';

			return $tipos_medios_pago[intval($index)];
		}

		/**
		 * Getter for Pagos Online messages cod for medio_pago code
		 * @var Int
		 * @return String
		 */	
		public function get_medios_de_pago( $index ) {
			$medios_pago = array(
				10 => 'VISA',
				11 => 'MASTERCARD',
				12 => 'AMEX',
				22 => 'DINERS',
				24 => 'Verified by VISA',
				25 => 'PSE',
				27 => 'VISA Debito',
				30 => 'Efecty',
				31 => 'Pago referenciado',
			);

			if( !is_numeric($index) )
				return '';

			return $medios_pago[intval($index)];
		}
		
		/**
		 * Getter for Pagos Online messages cod for estado_pol code
		 * @var Int
		 * @return String
		 */	
		public function get_estado_pol( $index ) {
			$estado_pol = array(
				'',
				'Sin abrir',
				'Abierta',
				'Pagada y abonada',
				'Cancelada',
				'Rechazada',
				'En validación',
				'Reversada',
				'Reversada fraudulenta',
				'Enviada ent. Financiera',
				'Capturando datos tarjeta de crédito',
				'Esperando confirmación sistema PSE',
				'Activa Débitos ACH',
				'Confirmando pago Efecty',
				'Impreso',
				'Debito ACH Registrado'
			);

			if( !is_numeric($index) )
				return '';

			return $estado_pol[intval($index)];
		}

		/**
		 * Getter for Pagos Online messages cod for codigo_respuesta_pol code
		 * @var Int
		 * @return String
		 */	
		public function get_codigo_respuesta_pol( $index ) {
			$codigo_respuesta_pol = array(
				1 => 'Transacción aprobada',
				2 => 'Pago cancelado por el usuario',
				3 => 'Pago cancelado por el usuario durante validación',
				4 => 'Transacción rechazada por la entidad',
				5 => 'Transacción declinada por la entidad',
				6 => 'Fondos insuficientes',
				7 => 'Tarjeta invalida',
				8 => 'Acuda a su entidad',
				9 => 'Tarjeta vencida',
				10 => 'Tarjeta restringida',
				11 => 'Discrecional POL',
				12 => 'Fecha de expiración o campo seg. Inválidos',
				13 => 'Repita transacción',
				14 => 'Transacción inválida',
				15 => 'Transacción en proceso de validación',
				16 => 'Combinación usuario-contraseña inválidos',
				17 => 'Monto excede máximo permitido por entidad',
				18 => 'Documento de identificación inválido',
				19 => 'Transacción abandonada capturando datos TC',
				20 => 'Transacción abandonada',
				21 => 'Imposible reversar transacción',
				22 => 'Tarjeta no autorizada para realizar compras por internet',
				23 => 'Transacción rechazada',
				24 => 'Transacción parcial aprobada',
				25 => 'Rechazada por no confirmación',
				26 => 'Comprobante generado, esperando pago en banco',
				9994 => 'Transacción pendiente por confirmar',
				9995 => 'Certificado digital no encontrado',
				9996 => 'Entidad no responde',
				9997 => 'Error de mensajería con la entidad financiera',
				9998 => 'Error en la entidad financiera',
				9999 => 'Error no especificado',
			);

			if( !is_numeric($index) )
				return '';

			return $codigo_respuesta_pol[intval($index)];
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

			$defaults[] = array(
				'name'		=> __('Página de Respuesta','po_gateway'),
				'desc' 		=> __('Página de respuesta de pagos online. Necesaria','jigoshop'),
				'tip' 		=> '',
				'id' 		=> 'po_gateway_response_page_id',
				'type' 		=> 'single_select_page',
				'std' 		=> ''
			);

			return $defaults;
			
		}


		public function generate_po_form( $order_id ) {

			$order = new jigoshop_order( $order_id );
	        
	        $gateway_url = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

	        $refventa_aux = time();

	        //Handling tax IVA, Colombia only.
	        $baseiva = round($order->order_total / 1.16, 2 );
	        $iva = $order->order_total - $baseiva;

	        //Sending po respone to index page
	        $url_respuesta = get_permalink($this->responsepage);

			$po_args = array(

				// Pagos Online API
				'usuarioId'				=> $this->userid,
				'firma'					=> $this->key,
				'refVenta'				=> "$order->id-$refventa_aux",
				'descripcion'			=> $this->get_articles_detail($order),
				'valor'					=> $order->order_total,			

				//Pagos Online must recibe 2 decimal digits
				'iva'					=> number_format($iva, 2, '.', ''),
				'baseDevolucionIva'		=> number_format($baseiva, 2, '.', ''),

				// Optional info for Pagos Online
				'prueba'				=> ($this->testmode == 'yes') ? 1 : 0,
				'url_respuesta'			=> $url_respuesta,
				'moneda'				=> Jigoshop_Base::get_options()->get_option('jigoshop_currency'),
				'nombreComprador'		=> "$order->billing_first_name $order->billing_last_name",
				'emailComprador'		=> $order->billing_email,
				'telefonoMovil'			=> $order->billing_phone,
				'extra1'				=> "$order->billing_address_1, $order->billing_address_2, $order->billing_city"
				
			);

			// If discount, total amount already include it
			// anyway, notify it in an extra information field
			if( $order->order_discount ) {
				$po_args['extra2'] = '(- $' . $order->order_discount . ') de descuento';
			}

			$po_args['firma'] = $this->gen_digital_sign($po_args);
			$po_args_array = array();

			foreach ($po_args as $key => $value) {
				$po_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
			}

			return '<form action="'. esc_url( $gateway_url ) .'" method="post" id="po_payment_form">
					' . implode('', $po_args_array) . '
					<button type="submit" class="btn btn-large btn-success" id="submit_payment_form" name="submit" />
						'.__('Pagar via Pagos Online', 'po_gateway').'
					</button>
					<a class="btn btn-large btn-warning" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'po_gateway').'</a>
					
					<script type="text/javascript">
						(function($){
							$("body").block(
								{
									message: "<img src=\"'.jigoshop::assets_url().'/assets/images/ajax-loader.gif\" alt=\"Redireccionando...\" />'.__('Gracias por su pedido. Ahora lo estamos redireccionando al sistema de pagos Pagos Online.', 'po_gateway').'",
									overlayCSS:
									{
										background: "#000",
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
							$("#submit_payment_form").click();
						})(jQuery);
					</script>
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
			// TODO

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
			$mon = $data['moneda'];

			$sign_s = "$sign~$uid~$rventa~$valor~$mon";

			return md5( $sign_s );
		}
		 

		function get_articles_detail($order) {			
			$out  = '';
			if (sizeof($order->items)>0) : foreach ($order->items as $item) :

				$_product = $order->get_product_from_item( $item );

				if ($_product->exists() && $item['qty']) :

					$title = $_product->get_title();

					//if variation, insert variation details into product title
					if ($_product instanceof jigoshop_product_variation) {

						$title .= ' (' . jigoshop_get_formatted_variation( $item['variation'], true) . ')';

					}
					$amount = number_format( apply_filters( 'jigoshop_po_adjust_item_price' ,$_product->get_price(), $item, 10, 2 ), 2);

					$out .= $item['qty'];
					$out .= " $title ($amount c/u)\n";

				endif;
			endforeach; endif;

			if( $order->order_discount ) {
				$out .= ' (- ' . number_format($order->order_discount, 2) . ' Descuento)'; 
			}

			$out .= ' - Desde ' . get_bloginfo();
			return $out;
		}

	}   /* End of Class definition for the Gateway */
	
}   /* End of init gateway function */