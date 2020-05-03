<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly


if (!class_exists('WC_Settings_Gyf')) :


	function gyf_add_settings()
	{

		/**
		 * Settings class
		 *
		 * @since 1.0.0
		 */
		class WC_Settings_Gyf extends WC_Settings_Page
		{


			/**
			 * Setup settings class
			 *
			 * @since  1.0
			 */
			public function __construct()
			{

				$this->id    = 'woocommerce_gyf';
				$this->label = __('Gyf', 'wc_gyf');

				add_filter('woocommerce_settings_tabs_array',        array($this, 'add_settings_page'), 20);
				add_action('woocommerce_settings_' . $this->id,      array($this, 'output'));
				add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
				add_action('woocommerce_sections_' . $this->id,      array($this, 'output_sections'));
			}


			/**
			 * Get sections
			 *
			 * @return array
			 */
			// public function get_sections()
			// {

			// 	$sections = array(
			// 		''         => __('Section 1', 'wc_gyf'),
			// 		'second' => __('Section 2', 'wc_gyf')
			// 	);

			// 	return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
			// }


			/**
			 * Get settings array
			 *
			 * @since 1.0.0
			 * @param string $current_section Optional. Defaults to empty string.
			 * @return array Array of settings
			 */
			public function get_settings($current_section = '')
			{

				/**
				 * Filter Plugin Section 1 Settings
				 *
				 * @since 1.0.0
				 * @param array $settings Array of the plugin settings
				 */
				$settings = apply_filters('gyf_section1_settings', array(

					array(
						'name' => __('API credentials', 'wc_gyf'),
						'type' => 'title',
						'desc' => 'Enter your Gyf API credentials to connect with GYF Partner portal.',
						'id'   => 'gyf_main_title',
					),

					array(
						'type'     => 'text',
						'id'       => 'gyf_api_publicKey',
						'name'     => __('GYF Public Key', 'wc_gyf'),
						'desc_tip' => __('Get your GYF public key in Gyf partner portal', 'wc_gyf'),
						'default'  => '',
					),

					array(
						'type'     => 'password',
						'id'       => 'gyf_api_privateKey',
						'name'     => __('GYF Private Key', 'wc_gyf'),
						'desc_tip' => __('Get your GYF private key in Gyf partner portal', 'wc_gyf'),
						'default'  => '',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'myplugin_important_options'
					),

				));

				/**
				 * Filter MyPlugin Settings
				 *
				 * @since 1.0.0
				 * @param array $settings Array of the plugin settings
				 */
				return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
			}


			/**
			 * Output the settings
			 *
			 * @since 1.0
			 */
			public function output()
			{
				global $current_section;
				$settings = $this->get_settings($current_section);
				WC_Admin_Settings::output_fields($settings);
			}


			/**
			 * Save settings
			 *
			 * @since 1.0
			 */
			public function save()
			{
				global $current_section;
				$settings = $this->get_settings($current_section);
				WC_Admin_Settings::save_fields($settings);
			}
		}

		return new WC_Settings_Gyf();
	}

	add_filter('woocommerce_get_settings_pages', 'gyf_add_settings', 15);

endif;
