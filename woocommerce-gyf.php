<?php
/*
Plugin Name: Gyf WooCommerce
Plugin URI: http://gyf.lk
Description: WooCommerce plugin for Gyf
Author: Chamara Abesinghe
Author URI: http://gyf.lk
Version: 0.0.1
Copyright: © 2020 Gyf.lk (email : info@gyf.lk)
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Check if WooCommerce is active
 */

include( plugin_dir_path( __FILE__ ) . 'woocommerce-gyf-settings.php');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	if (!class_exists('WC_Gyf')) {

		/**
		 * Localisation
		 **/
		load_plugin_textdomain('wc_gyf', false, dirname(plugin_basename(__FILE__)) . '/');

		class WC_Gyf
		{

			public $baseUrl;

			public function __construct()
			{

				$baseUrl = "https://5eabe6cea280ac00166576ee.mockapi.io/gyf-api/v1/";

				// called only after woocommerce has finished loading
				add_action('woocommerce_init', array(&$this, 'woocommerce_loaded'));

				// called after all plugins have loaded
				add_action('plugins_loaded', array(&$this, 'plugins_loaded'));

				// called just before the woocommerce template functions are included
				add_action('init', array(&$this, 'include_template_functions'), 20);

				// indicates we are running the admin
				if (is_admin()) {
					// ...
				}

				// indicates we are being served over ssl
				if (is_ssl()) {
					// ...
				}

				add_action('wp_enqueue_scripts', 'gyf_load_plugin_css');
				function gyf_load_plugin_css()
				{
					$plugin_url = plugin_dir_url(__FILE__);
					wp_enqueue_style('style1', $plugin_url . 'css/gyf-style.css');
				}

				// take care of anything else that needs to be done immediately upon plugin instantiation, here in the constructor
			}

			/**
			 * Take care of anything that needs woocommerce to be loaded.  
			 * For instance, if you need access to the $woocommerce global
			 */
			public function woocommerce_loaded()
			{

				/**
				 * Add the field to the checkout
				 */
				add_action('woocommerce_after_order_notes', 'gyf_custom_chkf');

				function gyf_custom_chkf($checkout)
				{
					$code = '';
					$owner = '';
					$value = '';

					if (WC()->session->__isset('gyf_code')) {
						$baseUrl = "https://5eabe6cea280ac00166576ee.mockapi.io/gyf-api/v1/";
						$response = wp_remote_get(sprintf('%sVoucher/%s', $baseUrl, WC()->session->get('gyf_code')));
						$http_code = wp_remote_retrieve_response_code($response);
						if ($http_code == 200) {
							$data = json_decode(wp_remote_retrieve_body($response), true);
							$code = $data['code'];
							$owner = $data['owner'];
							$value = $data['value'];
						}
					}

					echo '<div id="gyfChkF"><h2>' . __('Gyf Code') . '</h2>';
					echo '<p class="gyfield hidden">Applied Code: <span id="gyf_acode">' . $code . '</></p>';
					echo '<p class="gyfield hidden">Gyf Owner: <span id="gyf_auser">' . $owner . '</></p>';
					echo '<p class="gyfield hidden" style="margin-bottom:15px;">Gyf Available: <span id="gyf_available">' . $value . '</></p>';

					woocommerce_form_field('gyf_code', array(
						'type'          => 'text',
						'class'         => array('form-row-wide'),
						'label'         => __('Redeem your Gyf Voucher'),
						'placeholder'   => __('Enter your gyf code here'),
						'default'		=> WC()->session->__isset('gyf_code') ? WC()->session->get('gyf_code') : ''
					), $checkout->get_value('gyf_code'));

					woocommerce_form_field('gyf_value', array(
						'type'          => 'text',
						'class'         => array('form-row-wide hidden'),
						'placeholder'   => __('Enter amount you want to redeem'),
						'default'		=> WC()->session->__isset('gyf_value') ? WC()->session->get('gyf_value') : ''
					), $checkout->get_value('gyf_value'));

					echo '</div>';
					echo '</h2><a class="button alt" name="gyfChkF_btn" id="gyfChkF_btn" value="Apply">Redeem</a>';
				}

				// jQuery - Ajax script
				add_action('wp_footer', 'gyf_redeem_script');

				function gyf_redeem_script()
				{
					// Only checkout page
					if (!is_checkout()) return;
?>
					<script type="text/javascript">
						jQuery(function($) {
							if (typeof wc_checkout_params === 'undefined')
								return false;

							$(document).ready(function() {
								if ($("#gyf_code").val()) {
									$(".gyfield").removeClass('hidden');
									$("#gyf_value_field").removeClass('hidden');
									$("#gyf_code_field").addClass('hidden');
									$("#gyfChkF_btn").text('Update Value');
								}
							});

							$('#gyfChkF_btn').on('click', function() {
								const gyfCode = $("#gyf_code").val();
								const gyfValue = $("#gyf_value").val();
								if (gyfValue) {
									$.ajax({
										type: "post",
										url: wc_checkout_params.ajax_url,
										data: {
											'action': 'gyf_redeem_voucher',
											'vouchercode': gyfCode,
											'vouchervalue': gyfValue
										},
										success: function(response) {
											$('body').trigger('update_checkout');
											if (!response) {
												return;
											}
											console.log('response: ' + response); // just for testing | TO BE REMOVED
										},
										error: function(error) {
											console.log('error: ' + error); // just for testing | TO BE REMOVED
										}
									});
								} else if (gyfCode) {
									$.ajax({
										type: "post",
										dataType: "json",
										url: wc_checkout_params.ajax_url,
										data: {
											'action': 'gyf_redeem_voucher',
											'vouchercode': gyfCode,
											'vouchervalue': null
										},
										success: function(response) {
											$('body').trigger('update_checkout');
											if (!response) {
												return;
											}
											if (response.success == true) {
												$("#gyf_acode").text(response.result.code);
												$("#gyf_auser").text(response.result.owner);
												$("#gyf_available").text(response.result.value);

												// set value
												if (parseFloat(response.result.value) > parseFloat(response.cart_total)) {
													$("#gyf_value").val(response.cart_total);
												} else {
													$("#gyf_value").val(response.result.value);
												}
												$("#gyf_value_field").removeClass('hidden');
												$("#gyf_code_field").addClass('hidden');
												$(".gyfield").removeClass('hidden');
												$("#gyfChkF_btn").text('Update Value');
											}
										},
										error: function(error) {
											console.log('error: ' + error); // just for testing | TO BE REMOVED
										}
									});
								} else {
									$.ajax({
										type: "post",
										dataType: "json",
										url: wc_checkout_params.ajax_url,
										data: {
											'action': 'gyf_redeem_voucher'
										},
										success: function(response) {
											$('body').trigger('update_checkout');
											if (!response) {
												$("#gyf_code_field").addClass('validate-required');
												return;
											}
										},
										error: function(error) {
											console.log('error: ' + error); // just for testing | TO BE REMOVED
										}
									});
								}
							})
						})
					</script>
<?php
				}

				// Wordpress Ajax code (set ajax data in Woocommerce session)
				add_action('wp_ajax_gyf_redeem_voucher', 'gyf_redeem_voucher');
				add_action('wp_ajax_nopriv_gyf_redeem_voucher', 'gyf_redeem_voucher');

				function gyf_redeem_voucher()
				{
					$baseUrl = "https://5eabe6cea280ac00166576ee.mockapi.io/gyf-api/v1/";

					if (isset($_POST['vouchervalue']) && isset($_POST['vouchercode'])) {
						$form_value = $_POST['vouchervalue'];
						$form_code = $_POST['vouchercode'];
						if (!empty($form_code) && !empty($form_value)) {

							$response = wp_remote_get(sprintf('%sVoucher/%s', $baseUrl, $form_code));
							$http_code = wp_remote_retrieve_response_code($response);

							if ($http_code == 200) {
								$data = wp_remote_retrieve_body($response);
								$data = json_decode(wp_remote_retrieve_body($response), true);
								$code = $data['code'];
								$owner = $data['owner'];
								$value = $data['value'];

								if (floatval($form_value) > floatval($value)) {
									// Voucher value exceeded
									wc_add_notice(__('Maximum redeemable value exceeded, GYF Voucher reedemable value is low'), 'error');
									exit();
								} else if (floatval($form_value) > floatval(WC()->cart->total)) {
									// Cart total is lower than redeem value
									wc_add_notice(__('Maximum redeemable value exceeded, Checkout total is lesser than redeem value!'), 'error');
									exit();
								}

								// TODO: Expiry date check								

								WC()->session->set('gyf_value', esc_attr($form_value));
								echo json_encode(array('success' => true, 'result' => json_decode($data, true), 'cart_total' => WC()->cart->total));
								exit();
							} else {
								wp_send_json_error();
							}
						} else if (!empty($form_code)) {

							// find voucher exsists
							$response = wp_remote_get(sprintf('%sVoucher/%s', $baseUrl, $_POST['vouchercode']));
							$http_code = wp_remote_retrieve_response_code($response);
							if ($http_code == 200) {
								$data = wp_remote_retrieve_body($response);
								WC()->session->set('gyf_code', esc_attr($_POST['vouchercode']));
								echo json_encode(array('success' => true, 'result' => json_decode($data, true), 'cart_total' => WC()->cart->total));
								exit();
							} else {
								wc_add_notice(__('GYF Voucher not found.'), 'error');
								exit();
							}
						}
					}

					wc_add_notice(__('Please enter GYF Code to redeem.'), 'error');
					exit();
				}

				// Add Gyf to cart
				add_action('woocommerce_cart_calculate_fees', 'gyf_apply_voucher', 20, 1);
				function gyf_apply_voucher($cart)
				{
					if (is_admin() && !defined('DOING_AJAX'))
						return;

					// Only for targeted shipping method
					if (WC()->session->__isset('gyf_value'))
						$discount = (float) WC()->session->get('gyf_value');

					if (isset($discount) && $discount > 0)
						$cart->add_fee(__('GYF Redeemed', 'woocommerce'), -$discount);
				}

				// add_action('woocommerce_payment_complete', 'gyf_burn_voucher');
				// function gyf_burn_voucher($order_id)
				// {
				// 	// TODO - API Call to backend
				// 	update_post_meta($order_id, 'GYF Code', WC()->session->get('gyf_code'));
				// 	update_post_meta($order_id, 'GYF Redeemed', WC()->session->get('gyf_value'));
				// 	WC()->session->__unset('gyf_value');
				// 	WC()->session->__unset('gyf_code');
				// }

				add_action( 'woocommerce_new_order', 'gyf_burn_voucher',  1, 1  );
				function gyf_burn_voucher($order_id) {
					// TODO - API Call to backend
					update_post_meta($order_id, 'GYF Code', WC()->session->get('gyf_code'));
					update_post_meta($order_id, 'GYF Redeemed', WC()->session->get('gyf_value'));
					WC()->session->__unset('gyf_value');
					WC()->session->__unset('gyf_code');
				}

				/**
				 * Display field value on the order edit page
				 */
				add_action('woocommerce_admin_order_data_after_billing_address', 'gyf_field_display_admin_order_meta', 10, 1);

				function gyf_field_display_admin_order_meta($order)
				{
					echo '<p><strong>' . __('GYF Code') . ':</strong> ' . get_post_meta($order->get_id(), 'GYF Code', true) . '</p>';
					echo '<p><strong>' . __('GYF Redeemed') . ':</strong> ' . get_post_meta($order->get_id(), 'GYF Redeemed', true) . '</p>';
				}
			}

			/**
			 * Take care of anything that needs all plugins to be loaded
			 */
			public function plugins_loaded()
			{
				// ...
			}

			/**
			 * Override any of the template functions from woocommerce/woocommerce-template.php 
			 * with our own template functions file
			 */
			public function include_template_functions()
			{
				include('woocommerce-template.php');
			}
		}

		// finally instantiate our plugin class and add it to the set of globals
		$GLOBALS['wc_gyf'] = new WC_Gyf();
	}
}
