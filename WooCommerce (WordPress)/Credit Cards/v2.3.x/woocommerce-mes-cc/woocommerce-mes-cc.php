<?php
/**
 * Plugin Name: Merchant e-Solutions WooCommerce Payment Gateway - CC
 * Plugin URI: http://woothemes.com/products/merchante-solutions-cc/
 * Description: Allows processing of credit card transactions via the Merchant e-Solutions PayHere and Payment Gateway APIs.
 * Version: 0.0.1
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Developer: Nate Richardson
 * Developer URI: http://naterichardson.com/
 * Text Domain: woocommerce-mes-cc
 * Domain Path: /languages
 *
 * Copyright: © 2009-2015 WooThemes.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'init_mes_cc' );
function init_mes_cc() {
	/**
	 * @class 		WC_Gateway_Mes_CC
	 * @extends		WC_Payment_Gateway
	 * @version		0.0.1
	 * @package		WooCommerce/Classes/Payment
	 * @author 		@naterchrdsn (http://twitter.com/naterchrdsn)
	 */
	class WC_Gateway_Mes_CC extends WC_Payment_Gateway {

		/** @var boolean Whether or not logging is enabled */
		public static $log_enabled = false;

		/** @var WC_Logger Logger instance */
		public static $log = false;

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'mes_cc';
			$this->has_fields         = true;
			$this->method_title       = __( 'Merchant e-Solutions - Credit Card', 'woocommerce' );
			$this->method_description = __( 'Take payments right on your website using this direct payment gateway plugin from Merchant e-Solutions! This plugin supports credit card transactions. You must have a merchant account to use this plugin.', 'woocommerce' );
			$this->supports           = array(
				'products',
				'default_credit_card_form'
			);

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->enabled          = $this->get_option( 'enabled' );
			$this->title            = $this->get_option( 'title' );
			$this->description      = $this->get_option( 'description' );
			$this->mode             = $this->get_option( 'mode', 'pg' );
			$this->test_mode        = $this->get_option( 'test_mode', 'Y' );
			$this->debug            = 'yes' === $this->get_option( 'debug', 'no' );
			$this->profile_id       = $this->get_option( 'profile_id' );
			$this->profile_key      = $this->get_option( 'profile_key' );
			$this->tran_type        = $this->get_option( 'tran_type', 'sale' );
			$this->security_key      = $this->get_option( 'security_key' );

			self::$log_enabled    = $this->debug;
			$this->init_mes_sdk();

			// Hooks
			add_action( 'admin_notices', array( $this, 'checks' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_wc_gateway_mes_cc', array( $this, 'return_handler' ) );
		}

		/**
		 * Init PG SDK.
		 *
		 * @return void
		 */
		protected function init_mes_sdk() {
			// Include lib... only if another one of our plugins hasn't loaded it yet
			if (class_exists('TpgSale') === false) {
				require_once( dirname(__FILE__) . '/assets/mes_sdk.php' );
			}
		}

		/**
		 * Logging method
		 * @param  string $message
		 */
		public static function log( $message ) {
			if ( self::$log_enabled ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'mes_cc', $message );
			}
		}

		/**
		 * Some random checks to make sure we can use the payment gateway
		 */
		public function checks() {
			if ( 'no' == $this->enabled ) {
				return;
			}

			// PHP Version
			if ( version_compare( phpversion(), '5.3', '<' ) ) {
				echo '<div class="error"><p>' . sprintf( __( 'Merchant e-Solutions - Credit Card Error: the plugin requires PHP 5.3 and above. You are using version %s.', 'woocommerce' ), phpversion() ) . '</p></div>';
			}

			// Check required fields
			elseif ( ! $this->profile_id || ! $this->profile_key ) {
				echo '<div class="error"><p>' . __( 'Merchant e-Solutions - Credit Card Error: Please enter your profile id and required info.', 'woocommerce' ) . '</p></div>';
			}

			// Show message when using standard mode and no SSL on the checkout page
			elseif ( 'pg' == $this->mode && 'no' == get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) {
				echo '<div class="error"><p>' . sprintf( __( 'Merchant e-Solutions - Credit Card is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure!', 'woocommerce'), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
			}
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title'
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options() {
			?>
			<h3><?php _e( 'Merchant e-Solutions - Credit Card', 'woocommerce' ); ?></h3>

			<?php if ( empty( $this->profile_id ) ) : ?>
				<div class="mes-banner updated">
					<img src="<?php echo plugins_url() . '/woocommerce-mes-cc/assets/logo.png'; ?>" />
					<p class="main"><strong><?php _e( 'Getting started', 'woocommerce' ); ?></strong></p>
					<p><?php _e( 'Take payments right on your website using this direct payment gateway plugin from Merchant e-Solutions! This plugin supports credit card transactions, via the MeS Payment Gateway API or PayHere. You must have a merchant account to use this plugin.' ); ?></p>

					<p><a href="https://developer.merchante-solutions.com/#/sandbox-signup" target="_blank" class="button button-primary"><?php _e( 'Sign up with Merchant e-Solutions', 'woocommerce' ); ?></a> <a href="https://www.merchante-solutions.com/merchants/merchants-overview/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>

				</div>
			<?php else : ?>
				<p><?php _e( 'Thank you for choosing the Merchant e-Solutions module for Credit Card transactions! This payment gateway module allows payment via either the PayHere or Payment Gateway APIs provided by Merchant e-Solutions.', 'woocommerce' ); ?></p>
			<?php endif; ?>

			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
				<script type="text/javascript">
					jQuery( '#woocommerce_mes_cc_mode' ).on( 'change', function() {
						var key = jQuery( '#woocommerce_mes_cc_security_key' ).closest( 'tr' ),

						if ( 'pg' == jQuery( this ).val() ) {
							key.hide();
						} else {
							key.show();
						}
					}).change();
					jQuery( "#woocommerce_mes_cc_profile_id" ).attr( "maxlength", 20 );
					jQuery( "#woocommerce_mes_cc_profile_key" ).attr( "maxlength", 32 );
					jQuery( "#woocommerce_mes_cc_security_key" ).attr( "maxlength", 20 );
				</script>
			</table>
			<?php
		}


		/**
		 * Initialise Gateway Settings Form Fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'label'       => __( 'Enable Merchant e-Solutions - Credit Card', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This is the label the customer sees during checkout.', 'woocommerce' ),
					'default'     => __( 'Credit Card', 'woocommerce' ),
					'desc_tip'    => true
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the description which the customer sees during checkout.', 'woocommerce' ),
					'default'     => 'Pay with your credit card through Merchant e-Solutions\' secure Payment Gateway.',
					'desc_tip'    => true
				),
				'mode' => array(
					'title'       => __( 'Payment Mode', 'woocommerce' ),
					'type'        => 'select',
					'description' => sprintf( __( 'Choose Payment Gateway to use the MeS Payment Gateway API via a regular credit card form displayed to your customers. %1$s Choose PayHere to use the MeS PayHere API and redirect the customer to the MeS PayHere hosted page. %1$s Note: You must have either Payment Gateway or PayHere API access on your account!', 'woocommerce' ), '<br />' ),
					'default'     => 'pg',
					'options'     => array(
						'pg'      => __( 'Payment Gateway', 'woocommerce' ),
						'ph'      => __( 'PayHere', 'woocommerce' )
					)
				),
				'test_mode' => array(
					'title'       => __( 'Test Mode', 'woocommerce' ),
					'label'       => __( 'Enable Test Mode', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __( 'Place the payment gateway in test mode using your API keys (real payments will not be taken).', 'woocommerce' ),
					'default'     => 'yes'
				),
				'debug' => array(
					'title'       => __( 'Debug Log', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable logging', 'woocommerce' ),
					'default'     => 'no',
					'description' => sprintf( __( 'Log gateway events, inside <code>%s</code>', 'woocommerce' ), wc_get_log_file_path( 'mes_cc' ) )
				),
				'profile_id' => array(
					'title'       => __( 'Profile ID', 'woocommerce' ),
					'type'        => 'number',
					'description' => __( 'Get your API keys from your MeS account details page.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true
				),
				'profile_key' => array(
					'title'       => __( 'Profile Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Get your API keys from your MeS account details page.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true
				),
				'tran_type' => array(
					'title'       => __( 'Transaction Type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Use the Payment Gateway to process Pre-Authorization or Sale transactions?', 'woocommerce' ),
					'default'     => 'sale',
					'desc_tip'	  => true,
					'options'     => array(
						'sale'      => __( 'Sale', 'woocommerce' ),
						'auth'      => __( 'Pre-Authorization', 'woocommerce' )
					)
				),
				'security_key' => array(
					'title'		  => __( 'Security Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Your security key is a unique PIN you set up when enrolling your merchant account in PayHere. Consult your account rep for this info.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true
				),
			);
		}

		/**
		 * Payment form on checkout page
		 */
		public function payment_fields() {
			$description = $this->get_description();

			if ( 'yes' == $this->test_mode ) {
				$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. Use a test card: %s', 'woocommerce' ), '<a href="http://developer.merchante-solutions.com/#/payment-gateway-testing#card-numbers">developer.merchante-solutions.com</a>' );
			}

			if ( $description ) {
				echo wpautop( wptexturize( trim( $description ) ) );
			}

			if ( 'pg' == $this->mode ) {
				$this->credit_card_form( array( 'fields_have_names' => true ) );
			}
		}

		/**
		 * Process pg payments
		 *
		 * @param  WC_Order $order
		 *
		 * @return array
		 */
		protected function process_pg_payments( $order ) {
			if (!isset($this->profile_id) || !isset($this->profile_key)) {
				wc_add_notice( 'Gateway Configuration Error, Please contact the site admin!', 'error' );
				return array(
					'result'   => 'fail',
					'redirect' => ''
				);
			} else if (!isset($_POST['mes_cc-card-cvc'])) {
				wc_add_notice( 'Gateway Configuration Error, Please contact the site admin!', 'error' );
				return array(
					'result'   => 'fail',
					'redirect' => ''
				);
			} else {
			    if (!function_exists('curl_init')){
			        wc_add_notice('cURL is not installed! Contact the site admin.', 'error');
			        return array(
			        	'result' => 'fail',
			        	'redirect' => ''
			        );
			    }
				if ("sale" === $this->tran_type) {
					$tran = new TpgSale($this->profile_id, $this->profile_key);
				} else {
					$tran = new TpgPreAuth($this->profile_id, $this->profile_key);
				}
				if ( !('yes' == $this->test_mode) ) {
					// Production api endpoint
					$api_endpoint = 'https://api.merchante-solutions.com/mes-api/tridentApi';
					$tran->setHost($api_endpoint);
				}
				$tran->setAvsRequest($order->billing_address_1, $order->billing_postcode);
				$tran->setRequestField('cvv2', $_POST['mes_cc-card-cvc']);
				$tran->setRequestField('invoice_number', $order->id);
				$tran->setRequestField('currency_code', strtoupper( get_woocommerce_currency() ));
				$tran->setRequestField('dest_country_code', $order->shipping_country);
				$tran->setRequestField('ship_to_zip', $order->shipping_postcode);
				$tran->setRequestField('ship_to_address', $order->shipping_address_1);
				$tran->setRequestField('ship_to_last_name', $order->shipping_last_name);
				$tran->setRequestField('ship_to_first_name', $order->shipping_first_name);
				$tran->setRequestField('country_code', $order->billing_country);
				$tran->setRequestField('custom', $order->id);
				$tran->setRequestField('cardholder_first_name', $order->billing_first_name);
				$tran->setRequestField('cardholder_last_name', $order->billing_last_name);
				$tran->setRequestField('cardholder_email', $order->billing_email);
				$tran->setRequestField('cardholder_phone', $order->billing_phone);
				$tran->setRequestField('account_name', $order->account_username);
				$tran->setRequestField('account_email', $order->billing_email);
				$tran->setTransactionData(str_replace(' ', '', $_POST['mes_cc-card-number']), str_replace(' / ', '', $_POST['mes_cc-card-expiry']), $order->order_total);
				$tran->execute();

				if (false == $tran->isApproved()) {
					$this->log('MeS PG SDK Call Failed :: '.$tran->getResponseField('auth_response_text'). ' (' . $tran->getResponseField('error_code') . ') Tran Id: ' . $tran->getResponseField('transaction_id') );
					wc_add_notice( 'Authorization attempt failed.', 'error' );
					return array(
						'result'   => 'fail',
						'redirect' => ''
					);
				} else {
					$order_complete = $this->process_order_status( $order, $tran->getResponseField('transaction_id'), $tran->getResponseField('error_code'), $tran->getResponseField('auth_code') );
					if ( $order_complete ) {
						// Return thank you page redirect
						return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order )
						);
					}
				}
			}
		}

		/**
		 * Process ph payments
		 *
		 * @param WC_Order $order
		 * @return array
		 */
		protected function process_ph_payments( $order ) {
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}

		/**
		 * Process the payment
		 *
		 * @param integer $order_id
		 */
		public function process_payment( $order_id ) {
			$order      = wc_get_order( $order_id );
			if ( 'ph' == $this->mode ) {
				return $this->process_ph_payments( $order );
			} else {
				return $this->process_pg_payments( $order );
			}
		}

		/**
		 * Hosted payment args.
		 *
		 * @param  WC_Order $order
		 *
		 * @return array
		 */
		protected function get_hosted_payments_args( $order ) {
			$args = apply_filters( 'woocommerce_mes_payhere_args', array(
				'profile_id'   		  => $this->profile_id,
				'transaction_amount'  => $order->order_total,
				'invoice_number'    => $order->id,
				'use_merch_receipt'   => 'Y',
				'cardholder_street_address' => $order->billing_address_1,
				'cardholder_zip'     => $order->billing_postcode,
				'echo_return' => site_url(),
				'return_url' => plugins_url().'/woocommerce-mes-cc/wc-mes-cc-redirect.php',
				'cancel_url' => esc_url( $order->get_cancel_order_url() )
			), $order->id );
			if (isset($this->security_key)) {
				$tran_key = md5($this->profile_key.$this->security_key.$order->order_total);
				$args['transaction_key'] = $tran_key;
			};
			return $args;
		}

		/**
		 * Receipt page
		 *
		 * @param  int $order_id
		 */
		public function receipt_page( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( !('yes' == $this->test_mode) ) {
				$endpoint = 'https://www.merchante-solutions.com/jsp/tpg/secure_checkout.jsp';
			} else {
				$endpoint = 'https://test.cielo-us.com/jsp/tpg/secure_checkout.jsp';
			}
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay using your credit card with PayHere by Merchant e-Solutions.', 'woocommerce' ) . '</p>';
			echo '<form method="POST" action="'.$endpoint.'" id="mes_ph">';
			$args = $this->get_hosted_payments_args( $order );
			foreach ( $args as $key => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"></input>';
			}
			echo '<button class="button alt" href="javascript:;" onclick="parentNode.submit();">Pay Now</button></form><a class="button cancel" href="'.$order->get_cancel_order_url().'">Cancel Order & Restore Cart</a>';
		}

		/**
		 * Return handler for Hosted Payments
		 */
		public function return_handler() {
			@ob_clean();
			header( 'HTTP/1.1 200 OK' );

			if ( isset( $_REQUEST['invoice_number'] ) && isset( $_REQUEST['tran_id'] ) && isset( $_REQUEST['auth_code'] ) ) {
				$order_id  = $_REQUEST['invoice_number'];
				$order     = wc_get_order( $order_id );
				$order_complete = $this->process_order_status( $order, $_REQUEST['tran_id'], $_REQUEST['resp_code'], $_REQUEST['auth_code'] );
				if ( ! $order_complete ) {
					$order->update_status( 'failed', __( 'Payment was declined by PayHere: ', 'woocommerce' ).$_REQUEST['resp_text'].' ('.$_REQUEST['resp_code'].')  Tran Id: '.$_REQUEST['tran_id'] );
				} else {
					wp_redirect( $this->get_return_url( $order ) );
					exit();
				}
			}
			wc_add_notice( 'Authorization attempt failed: '.$_REQUEST['resp_text'].' ('.$_REQUEST['resp_code'].') Tran Id: '.$_REQUEST['tran_id'], 'error' );
			wp_redirect( wc_get_page_permalink( 'cart' ) );
			exit();
		}

		/**
		 * Process the order status
		 *
		 * @param  WC_Order $order
		 * @param  string   $payment_id
		 * @param  string   $status
		 * @param  string   $auth_code
		 *
		 * @return bool
		 */
		public function process_order_status( $order, $payment_id, $status, $auth_code ) {
			if ( '000' == $status ) {
				// Payment complete
				$order->payment_complete( $payment_id );

				// Add order note
				$order->add_order_note( sprintf( __( 'MeS payment approved (Transaction ID: %s, Auth Code: %s)', 'woocommerce' ), $payment_id, $auth_code ) );

				// Remove cart
				WC()->cart->empty_cart();

				return true;
			}

			return false;
		}
		
	}
	
	/**
	* Add our payment gateway to WooCommerce!
	*/
	function add_mes_cc( $methods ) {
		$methods[] = 'WC_Gateway_Mes_CC'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_mes_cc' );
}