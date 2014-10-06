<?php
/*
  Plugin Name: WooCommerce-qTML
  Plugin URI: http://www.somewherewarm.net
  Description: Add (m)qTranslate support to WooCommerce.
  Author: SomewhereWarm
  Author URI: http://www.somewherewarm.net
  Version: 2.0.11
 */

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WC_Dependencies' ) )
	require_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		return WC_Dependencies::woocommerce_active_check();
	}
}

if ( is_woocommerce_active() ) {

	class WC_QTML {

		var $version = '2.0.11';

		var $enabled_languages;
		var $enabled_locales;
		var $default_language;
		var $current_language;

		var $mode;

		var $domain_switched = false;

		var $email_textdomains = array(
			'woocommerce' 			=> '/woocommerce/i18n/languages/woocommerce-',
			'wc_shipment_tracking' 	=> '/woocommerce-shipment-tracking/languages/wc_shipment_tracking-',
			'woocommerce-bto' 		=> '/woocommerce-composite-products/languages/woocommerce-bto-'
		);

		public function __construct() {

			if ( in_array( 'qtranslate/qtranslate.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'mqtranslate/mqtranslate.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				add_action( 'init', array( $this, 'wc_qtml_init' ), 0 );

				// Forces default language in admin area
				add_action( 'plugins_loaded', array($this, 'wc_qtml_plugins_init' ), 1 );

				add_action( 'plugins_loaded', array( $this, 'wc_qtml_plugins_loaded' ), 3 );

				// Debug
				// add_action( 'wp_head', array($this, 'print_debug' ) );
			}

		}

		function print_debug() {
			echo '<br/><br/>Locale: ';
			print_r( get_locale() );
			echo '<br/>Current Language: ';
			print_r( $this->current_language );
			echo '<br/>Default Lang: ';
			print_r( $this->default_language );
			echo '<br/>qTrans Lang: ';
			print_r( qtrans_getLanguage() );
			echo '<br/>Session Lang: ';
			print_r( $_SESSION[ 'qtrans_language' ] );
		}


		function wc_qtml_plugins_loaded(){

			global $q_config;

			if ( ! session_id() ) {
				session_start();
			}

			$this->enabled_languages 	= $q_config[ 'enabled_languages' ];
			$this->default_language 	= $q_config[ 'default_language' ];
			$this->enabled_locales 		= $q_config[ 'locale' ];

			if ( ! is_admin() || $this->is_ajax_woocommerce() ) {

				if ( in_array( qtrans_getLanguage(), $this->enabled_languages ) ) {

					$this->current_language = qtrans_getLanguage();

					$_SESSION[ 'qtrans_language' ] = $this->current_language;

				} elseif ( ! empty( $_SESSION[ 'qtrans_language' ] ) ) {

					$this->current_language = $_SESSION[ 'qtrans_language' ];
					$q_config[ 'language' ] = $this->current_language;

				} else {

					$this->current_language = $this->default_language;
				}

			} else {
				$this->current_language = empty( $q_config[ 'language' ] ) ? $this->default_language : $q_config[ 'language' ];
			}

			// get url mode

			// QT_URL_QUERY - query: 1
			// QT_URL_PATH - pre-path: 2
			// QT_URL_DOMAIN - pre-domain: 3

			$this->mode = $q_config['url_mode'];

			// add textdomain names and locations for e-mail translations
			$this->email_textdomains = apply_filters( 'wc_qtml_email_textdomain_locations', $this->email_textdomains );

		}


		function wc_qtml_plugins_init(){

			// customize localization of admin menu
			if ( apply_filters( 'wc_qtml_admin_default_language', true ) ) {
				// remove_action( 'admin_menu', 'qtrans_adminMenu' );

				add_filter( 'locale', array( $this, 'wc_qtml_admin_locale' ), 1000 );
				add_filter( 'qtranslate_language', array( $this,'wc_qtml_lang' ) );
			}
		}


		function wc_qtml_init() {

			global $woocommerce;

			// translate strings
			$filters = array(
				'option_woocommerce_email_from_name'          => 10,
				'the_title_attribute'                         => 10,
				'woocommerce_attribute_label'                 => 10,
				'woocommerce_cart_item_name'                  => 10,
				'woocommerce_cart_shipping_method_full_label' => 10,
				'woocommerce_rate_label'                      => 10,
				'woocommerce_email_footer_text'               => 10,
				'woocommerce_gateway_description'             => 10,
				'woocommerce_gateway_title'                   => 10,
				'woocommerce_page_title'                      => 10,
				'woocommerce_order_item_name'                 => 10,
				'woocommerce_order_product_title'             => 10,
				//'woocommerce_order_shipping_to_display'     => 10,
				//'woocommerce_order_subtotal_to_display'     => 10,
				'woocommerce_variation_option_name'           => 10,
				'woocommerce_composite_component_title'       => 10,
				'woocommerce_composite_component_description' => 10,
				'woocommerce_composited_product_excerpt'      => 10,
				'woocommerce_bto_component_title'             => 10,
				'woocommerce_bto_component_description'       => 10,
				'woocommerce_bto_product_excerpt'             => 10,
				'woocommerce_product_title'                   => 10,
				'woocommerce_order_item_display_meta_value'   => 10,
				'woocommerce_short_description'               => 10,
				'woocommerce_bundled_item_title'              => 10,
				'woocommerce_bundled_item_description'        => 10
			);

			$filters = apply_filters( 'wc_qtml_translate_string_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'wc_qtml_split' ), $priority );
			}


			// translate terms
			$filters = array(
				'get_term' => 10,
			);

			$filters = apply_filters( 'wc_qtml_translate_term_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'wc_qtml_translate_term' ), $priority );
			}


			// translate terms
			$filters = array(
				'get_terms'           => 10,
				'wp_get_object_terms' => 10,
			);

			$filters = apply_filters( 'wc_qtml_translate_terms_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'wc_qtml_translate_terms' ), $priority );
			}


			// translate tax totals
			add_filter( 'woocommerce_order_tax_totals', array( $this, 'wc_qtml_translate_tax_totals' ), 10 );


			// translate gateway settings
			$filters = array(
				'option_woocommerce_bacs_settings'   => 10,
				'option_woocommerce_cheque_settings' => 10,
				'option_woocommerce_cod_settings' => 10
			);

			$filters = apply_filters( 'wc_qtml_translate_gateway_settings_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				if ( ! is_admin() || $this->is_ajax_woocommerce() )
					add_filter( $id, array( $this, 'wc_qtml_translate_gateway_settings' ), $priority );
			}


			// add query var to URLs
			$filters = array(
				'post_type_archive_link'                          => 10,
				'post_type_link'                                  => 10,
				'woocommerce_add_to_cart_url'                     => 10,
				'woocommerce_breadcrumb_home_url'                 => 10,
				'woocommerce_product_add_to_cart_url'             => 10,
				'woocommerce_layered_nav_link' 					  => 10
			);

			$filters = apply_filters( 'wc_qtml_convertURL_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				add_filter( $id, 'qtrans_convertURL', $priority );
			}

			add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'wc_qtml_checkout_payment_url_filter' ) );
			add_filter( 'woocommerce_payment_successful_result', array( $this, 'wc_qtml_payment_url' ) );
			add_filter( 'woocommerce_get_return_url', array( $this, 'wc_qtml_return_url' ) );
			add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'wc_qtml_return_url' ) );


			// fix comment posting
			add_filter( 'site_url', array( $this, 'wc_qtml_add_lang_query_var_to_site_url' ), 10, 2 );


			// fix mini-cart
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'wc_qtml_cart_loaded_from_session' ) );


			// add query var to URLs
			$filters = array(
				'woocommerce_get_cart_url'     => 10,
				'woocommerce_get_checkout_url' => 10,
				'woocommerce_get_endpoint_url' => 10,
				'wp_redirect' 				   => 10,
				'tml_action_url' 			   => 10
			);

			$filters = apply_filters( 'wc_qtml_url_filters', $filters );

			foreach ( $filters as $id => $priority ) {
				add_filter( $id, array( $this, 'wc_qtml_add_lang_query_var_to_url' ), $priority );
			}


			// fix wc params
			$filters = array(
				'wc_add_to_cart_params'    => 10,
				'wc_cart_fragments_params' => 10,
				'wc_cart_params'           => 10,
				'wc_checkout_params'       => 10,
				'woocommerce_params'       => 10,
			);

			$filters = apply_filters( 'wc_qtml_woocommerce_params_filters', $filters );

			foreach ( $filters as $id => $priority ) {

				add_filter( $id, array( $this, 'wc_qtml_add_lang_query_var_to_woocommerce_params' ), $priority );
			}

			// fix endpoints
			//add_filter( 'woocommerce_get_endpoint_url', array( $this, 'wc_qtml_modify_endpoints_url' ) );

			// fix almost everything
			//add_filter( 'clean_url', array( $this,'wc_qtml_add_lang_query_var_to_url' ) );

			// store lang
			add_action( 'woocommerce_new_order', array( $this, 'wc_qtml_store_order_language' ) );

			// customize localization of customer emails

			add_action( 'woocommerce_new_customer_note', array( $this, 'wc_qtml_switch_email_textdomain_with_args' ), 1, 2 );

			add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'wc_qtml_switch_email_textdomain' ), 1 );
			add_action( 'woocommerce_order_status_pending_to_on-hold', array( $this, 'wc_qtml_switch_email_textdomain' ), 1 );
			add_action( 'woocommerce_order_status_completed', array( $this,'wc_qtml_switch_email_textdomain' ), 1 );

			add_action( 'woocommerce_order_status_changed', array( $this,'wc_qtml_reset_email_textdomain' ), 1 );
			add_action( 'woocommerce_before_send_customer_invoice', array( $this, 'wc_qtml_before_send_customer_invoice' ), 1 );
			add_action( 'woocommerce_before_resend_order_emails', array( $this, 'wc_qtml_before_resend_email' ), 1 );

			// fix shipping method descriptions
			if ( version_compare( $woocommerce->version, '2.1.0' ) < 0 )
				add_filter( 'woocommerce_available_shipping_methods', array( $this, 'wc_qtml_shipping_methods_filter' ) );
			else {
				add_filter( 'woocommerce_order_shipping_method', array( $this, 'wc_qtml_order_shipping_methods_filter' ), 10, 2 );
				add_filter( 'woocommerce_shipping_method_title', array( $this, 'wc_qtml_order_shipping_method_admin_title' ), 10, 2 );
				add_action ( 'woocommerce_review_order_before_shipping', array( $this, 'wc_qtml_before_shipping' ), 10 );
			}

			// fix product category listing for translate tags
			// add_filter( 'term_links-product_cat', array( $this, 'wc_qtml_term_links_filter' ) );

			// fix taxonomy titles
			//$this->wc_qtml_taxonomies_filter();
			add_filter( 'woocommerce_attribute_taxonomies', array( $this, 'wc_qtml_attribute_taxonomies_filter' ) );
			add_filter( 'woocommerce_attribute', array( $this, 'wc_qtml_attribute_filter' ), 10, 3 );

			// hide coupons meta in emails
			//add_filter( 'woocommerce_email_order_meta_keys', array( $this, 'wc_qtml_hide_email_coupons' ) );

			// fix localization of date function
			add_filter( 'date_i18n', array( $this, 'wc_qtml_date_i18n_filter' ), 10, 4);

			// fix review comment links and blog comment links
			add_filter( 'get_comment_link', array( $this, 'wc_qtml_get_comment_link_filter' ) );
			add_filter( 'paginate_links', array( $this, 'wc_qtml_get_comment_page_link_filter' ) );

			// fixes comment_form action
			//add_action('comment_form', array( $this, 'wc_qtml_comment_post_lang' ) );
			//add_filter('comment_post_redirect', array( $this, 'wc_qtml_comment_post_redirect' ), 10, 2 );

			//add_filter('wp_redirect', array( $this, 'wc_qtml_wp_redirect_filter' ) );

		}


		/**
		 * Check if this is a WooCommerce AJAX request
		 */
		function is_ajax_woocommerce() {

			if ( ! isset( $_REQUEST[ 'action' ] ) ) {
				return FALSE;
			}

			$actions = array(
				'woocommerce_add_new_attribute',
				'woocommerce_add_order_fee',
				'woocommerce_add_order_item',
				'woocommerce_add_order_item_meta',
				'woocommerce_add_order_note',
				'woocommerce_add_to_cart',
				'woocommerce_add_variation',
				'woocommerce_apply_coupon',
				'woocommerce_calc_line_taxes',
				'woocommerce_checkout',
				'woocommerce_delete_order_note',
				'woocommerce_feature_product',
				'woocommerce_get_customer_details',
				'woocommerce_get_refreshed_fragments',
				'woocommerce_grant_access_to_download',
				'woocommerce_increase_order_item_stock',
				'woocommerce_json_search_customers',
				'woocommerce_json_search_downloadable_products_and_variations',
				'woocommerce_json_search_products',
				'woocommerce_json_search_products_and_variations',
				'woocommerce_link_all_variations',
				'woocommerce_mark_order_complete',
				'woocommerce_mark_order_processing',
				'woocommerce_product_ordering',
				'woocommerce_reduce_order_item_stock',
				'woocommerce_remove_order_item',
				'woocommerce_remove_order_item_meta',
				'woocommerce_remove_variation',
				'woocommerce_remove_variations',
				'woocommerce_revoke_access_to_download',
				'woocommerce_save_attributes',
				'woocommerce_term_ordering',
				'woocommerce_update_order_review',
				'woocommerce_update_shipping_method',
				'woo_bto_show_product'
			);

			return in_array( $_REQUEST[ 'action' ], $actions );
		}

		/**
		 * Translate terms.
		 */
		public function wc_qtml_translate_terms( $terms ) {

			if (
				is_array( $terms )
				&& count( $terms )
			) {
				foreach ( $terms as $key => $term ) {
					$terms[ $key ] = $this->wc_qtml_translate_term( $term );
				}
			}

			return $terms;
		}

		/**
		 * Translate term name into current (or default) language.
		 */
		public function wc_qtml_translate_term( $term ) {

			if (
				is_object( $term )
				&& isset( $term->name )
			) {
				$term = $this->wc_qtml_term_filter( $term );
			}

			return $term;
		}

		/**
		 * Translate tax totals.
		 */
		public function wc_qtml_translate_tax_totals( $tax_totals ) {

			foreach ( $tax_totals as $key => $tax_total ) {
				if ( isset( $tax_total->label ) ) {
					$tax_totals[ $key ]->label = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage(
						$tax_total->label
					);
				}
			}

			return $tax_totals;
		}

		/**
		 * Translate gateway settings.
		 */
		public function wc_qtml_translate_gateway_settings( $settings ) {

			if ( is_array( $settings ) ) {
				$keys = array(
					'title',
					'description',
					'instructions',
				);
				foreach ( $keys as $key ) {
					if ( isset( $settings[ $key ] ) ) {
						$settings[ $key ] = $this->wc_qtml_split( $settings[ $key ] );
					}
				}
			}

			return $settings;
		}

		/**
		 * Fixes comment posting.
		 */
		public function wc_qtml_add_lang_query_var_to_site_url( $url, $path ) {

			$paths = array(
				'/wp-comments-post.php',
			);
			if ( in_array( $path, $paths ) ) {
				$url = $this->wc_qtml_add_lang_query_var_to_url( $url );
			}

			return $url;
		}

		/**
		 * Forces the mini cart contents to be reloaded every time.
		 */
		function wc_qtml_cart_loaded_from_session( $cart ) {

			global $woocommerce;

			if ( ! empty( $cart->cart_contents ) ) {
				$keys = array_keys( $cart->cart_contents );
				$firstkey = $keys[0];
				$woocommerce->cart->cart_contents[ $firstkey ][ 'lang' ] = $this->current_language;
			}

		}

		function wc_qtml_order_shipping_method_admin_title( $title, $id ) {

			if ( is_admin() && ! is_ajax() )
				return __( $title );

			return $title;
		}

		function wc_qtml_before_shipping() {

			$packages = WC()->shipping->get_packages();

			foreach ( $packages as $key => $package ) {
				foreach ( $package[ 'rates' ] as $method_id => $data )
					WC()->shipping->packages[ $key ][ 'rates' ][ $method_id ]->label = __( $data->label );
			}
		}

		function wc_qtml_modify_endpoints_url( $url ) {

			if ( $this->mode == 1 ) {
				if ( preg_match( "#\/([&|\?]lang=[^\/]+)#i", $url, $match ) ) {
					$url = preg_replace( "#\/([&|\?]lang=[^\/]+)#i", "", $url );
					$url = rtrim( $url, '/' ) . $match[0];
				} else {
					$url = $this->wc_qtml_add_lang_query_var_to_url( $url );
				}
			}

			return $url;
		}

		function wc_qtml_split( $text ) {

			if ( isset( $GLOBALS[ 'order_lang' ] ) && in_array( $GLOBALS[ 'order_lang' ], $this->enabled_languages ) )
				$text = qtrans_use( $GLOBALS[ 'order_lang' ], $text );
			else
				$text = __( $text );

			return $text;

		}

		function wc_qtml_plugin_url() {
			return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
		}

		function wc_qtml_frontend_scripts() {
			wp_register_script( 'qt-woo-fragments', $this->wc_qtml_plugin_url() . '/assets/js/wc-cart-fragments-fix.js', array( 'jquery', 'jquery-cookie' ), $this->version, true );
			wp_enqueue_script( 'qt-woo-fragments' );
		}

		function wc_qtml_dequeue_scripts() {
			// wp_dequeue_script( 'wc-cart-fragments' );
		}

		function wc_qtml_comment_post_lang( $id ) {
			echo '<input type="hidden" name="comment_post_lang" value="' . $this->current_language . '" id="comment_post_lang">';
		}

		function wc_qtml_comment_post_redirect( $location, $comment ) {

			if ( ! empty( $_POST['comment_post_lang'] ) ) {

				if ( $this->mode == 1 ) {
					$lang = $_POST['comment_post_lang'];
					$lang = rawurlencode( $lang );
					$arg = array('lang' => $lang );
					$location = add_query_arg( $arg, $location );
				}
				elseif ( $this->mode == 2 ) {
					$location = qtrans_convertURL( $location, $_POST['comment_post_lang'], true );
				}
			}

			return $location;
		}

		function wc_qtml_wp_redirect_filter( $location ) {

			if ( $this->mode == 1 && ( ! is_admin() || $this->is_ajax_woocommerce() ) && strpos( $location, 'wp-admin' ) === false ) {
				$lang = '';
				if ( strpos( $location, 'lang=' ) === false ) {
					$lang = $this->current_language;
					$lang = rawurlencode( $lang );
					$arg = array('lang' => $lang );
					$location = add_query_arg($arg, $location);
				}
			}
			elseif ( $this->mode == 2 && ( !is_admin() || $this->is_ajax_woocommerce() ) && strpos( $location, 'wp-admin' ) === false ) {
				foreach ( $this->enabled_languages as $language ) {
					if ( strpos( $location, '/' . $language . '/' ) > 0 ) {
						return $location;
					}
				}
				$location = str_replace( $this->wc_qtml_strip_protocol( site_url() ), $this->wc_qtml_strip_protocol( site_url() ) . '/' . $this->current_language, $location );
			}

			return $location;
		}


		function wc_qtml_shipping_methods_filter( $available_methods ) {
			foreach ( $available_methods as $method ) :
				$method->label = __( esc_html( $method->label ), 'woocommerce' );
			endforeach;
			return $available_methods;
		}

		function wc_qtml_order_shipping_methods_filter( $label, $order ) {
			$labels = array();

			$custom_values = get_post_custom_values( 'language', $order->id );
			$order_lang = $custom_values[0];

			// Backwards compat < 2.1 - get shipping title stored in meta
			if ( $order->shipping_method_title ) {
				$labels[] = qtrans_use( $order_lang, $order->shipping_method_title );
			} else {
				// 2.1+ get line items for shipping
				$shipping_methods = $order->get_shipping_methods();

				foreach ( $shipping_methods as $shipping ) {
					$labels[] = qtrans_use( $order_lang, $shipping[ 'name' ] );
				}
			}

			return implode( ', ', $labels );
		}

		function wc_qtml_attribute_label_filter( $label ) {
			if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
				$label = qtrans_use( $GLOBALS['order_lang'], $label );
			else $label = __( $label );
			return $label;
		}

		function wc_qtml_attribute_taxonomies_filter( $attribute_taxonomies ) {
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax )
					if ( isset( $tax->attribute_label ) && ! ( strpos($tax->attribute_label, '[:') === false ) )
						$tax->attribute_label = __( $tax->attribute_label );
			}
			return $attribute_taxonomies;
		}

		function wc_qtml_taxonomies_filter() {
			global $wp_taxonomies;

			if ( ! is_admin() )
				return;

			foreach ( $wp_taxonomies as $tax_name => $tax ) {
				if ( $tax->labels )
					$tax->labels = qtrans_use( $this->current_language, $tax->labels );
			}
		}

		function wc_qtml_term_filter( $term ) {
			if ( $term ) {
				if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
					$term->name = qtrans_use( $GLOBALS['order_lang'], $term->name );
				elseif ( is_admin() && ! $this->is_ajax_woocommerce() && function_exists( 'get_current_screen' ) ) {

					// product categories and terms back end fix
				    $screen = get_current_screen();
					if ( ! empty( $screen ) && ! strstr( $screen->id, 'edit-pa_' ) && empty( $_GET['taxonomy'] ) )
						$term->name = $this->wc_qtml_split( $term->name );
				}
				else {
					$term->name = $this->wc_qtml_split( $term->name );
				}
			}
			return $term;
		}


		function wc_qtml_attribute_filter( $list, $attribute, $values ) {
			return wpautop( wptexturize( implode( ', ', qtrans_use( $this->current_language, $values ) ) ) );
		}

		// fixes localization of date_i18n function
		function wc_qtml_date_i18n_filter( $j, $format, $i, $gmt ) {
			if ( strpos( $j, 'of' ) > 0 ) {
				return date(__( 'l jS \of F Y h:i:s A', 'woocommerce' ), $i);
			}
			return $j;
		}

		// hides coupons meta in emails
		function wc_qtml_hide_email_coupons( $fields ) {
			$empty_fields = array();
			return $empty_fields;
		}

		function wc_qtml_get_comment_link_filter( $url ) {

			if ( $this->mode == 1 ) {
				if( preg_match( "#(\?)lang=([^/]+/)#i", $url, $match ) ) {
					$url = preg_replace( "#(\?)lang=([^/]+/)#i","",$url );
					$url = preg_replace( "#(/)(\#)#i", '/'.rtrim( $match[0], '/' ).'#', $url );
				} else {
					$url = preg_replace( "#(/)(\#)#i", '/'.'?lang='. $this->current_language .'#', $url );
				}
			}
			return $url;
		}

		function wc_qtml_get_comment_page_link_filter( $url ) {

			if ( $this->mode == 1 ) {
				if( preg_match( "#(\?)lang=([^/]+/)#i", $url, $match ) ) {
					$url = preg_replace( "#(\?)lang=([^/]+/)#i","",$url );
					$url = preg_replace( "#(/)(\#)#i", '/'.rtrim( $match[0], '/' ).'#', $url );
				} else {
					$url = preg_replace( "#(/)(\#)#i", '/'.'?lang='. $this->current_language .'#', $url );
				}
			}
			return $url;
		}

		function woocommerce_layered_nav_link_filter( $link ) {
			return qtrans_convertURL( $link, $this->current_language );
		}

		function wc_qtml_checkout_payment_url_filter( $url ) {
			if ( !is_admin() ) {
				$url = qtrans_convertURL( $url, $this->current_language );
			} else {
				if ( preg_match( "#(&|\?)order_id=([^&\#]+)#i",$url,$match ) ) {
					$order_id = $match[2];
					$custom_values = get_post_custom_values( 'language', $order_id );
					$order_lang = $custom_values[0];
					$url = qtrans_convertURL( $url, $order_lang, true );
				}
			}
			return $url;
		}

		// fixes product category listing for translate tags
		function wc_qtml_term_links_filter( $term_links ) {
			$fixed_links = array();

			foreach ( $term_links as $term_link ) {
				$start = strpos($term_link, '">') + 2;
				$end = strpos($term_link, '</');
				$term = substr($term_link, $start, $end - $start);
				$fixed_link = str_replace($term, __($term,'custom'), $term_link);
				$fixed_links[] = $fixed_link;
			}
			return $fixed_links;
		}

		// resets admin locale to default only
		function wc_qtml_admin_locale( $loc ) {
			if ( is_admin() && ! is_ajax() ) {
				$loc = $this->enabled_locales[ $this->default_language ];
			}
			return $loc;
		}

		function wc_qtml_lang( $lang ) {
			if ( is_admin() && ! $this->is_ajax_woocommerce() ) {
				return 'en';
			}
			return $lang;
		}

		function wc_qtml_reset_email_textdomain( $order_id ) {

			if ( $this->domain_switched ) {

				foreach ( $this->email_textdomains as $domain => $location ) {

					$mofile = WP_PLUGIN_DIR . $location . $this->enabled_locales[ $this->current_language ] . '.mo';

					if ( file_exists( $mofile ) ) {
						unload_textdomain( $domain );
						load_textdomain( $domain, $mofile );
					}
				}

				$this->domain_switched = false;
			}
		}

		function wc_qtml_switch_email_textdomain( $order_id ) {

			if ( $order_id > 0 ) {
				$custom_values = get_post_custom_values( 'language', $order_id );
				$order_lang = $custom_values[0];
				if ( isset( $order_lang ) && $order_lang != '' ) {
					$GLOBALS['order_lang'] = $order_lang;

					foreach ( $this->email_textdomains as $domain => $location ) {

						$mofile = WP_PLUGIN_DIR . $location . $this->enabled_locales[$order_lang] . '.mo';

						if ( file_exists( $mofile ) ) {
							unload_textdomain( $domain );
							load_textdomain( $domain, $mofile );
						}
					}

					$this->domain_switched = true;

				} else { $GLOBALS['order_lang'] = $this->current_language; }
			}
		}

		function wc_qtml_switch_email_textdomain_with_args ( $args ) {
			$defaults = array(
				'order_id' 		=> '',
				'customer_note'	=> ''
			);

			$args = wp_parse_args( $args, $defaults );

			extract( $args );

			$this->wc_qtml_switch_email_textdomain( $order_id );
		}

		function wc_qtml_before_resend_email( $order ) {
			$this->wc_qtml_switch_email_textdomain( $order->id );
		}

		function wc_qtml_before_send_customer_invoice( $order ) {
			$this->wc_qtml_switch_email_textdomain( $order->id );
		}

		function wc_qtml_after_send_customer_invoice( $order ) {
			$domain = 'woocommerce';
			unload_textdomain( $domain );
			load_textdomain( $domain, WP_PLUGIN_DIR . '/woocommerce/languages/woocommerce-' . $this->enabled_locales[$this->current_language] . '.mo' );
		}

		// stores order language in order object meta
		function wc_qtml_store_order_language( $order_id ) {
			if( ! get_post_meta( $order_id, 'language' ) ) {
				$language = $this->current_language;
				update_post_meta( $order_id, 'language', $language );
			}
		}

		function wc_qtml_add_lang_query_var_to_woocommerce_params( $params ) {

			if ( ! is_array( $params ) ) {
				return $params;
			}

			$keys = array(
				'ajax_url',
				'checkout_url',
				'cart_url'
			);

			foreach ( $keys as $key ) {
				if ( isset( $params[ $key ] ) ) {
					$params[ $key ] = $this->wc_qtml_add_lang_query_var_to_url( $params[ $key ] );
				}
			}

			return $params;
		}

		function wc_qtml_add_lang_query_var_to_url( $url ) {

			if ( ( ! is_admin() || $this->is_ajax_woocommerce() ) && strpos( $this->wc_qtml_strip_protocol( $url ), $this->wc_qtml_strip_protocol( site_url() ) . '/' . $this->current_language . '/' ) === false ) {
					$url = str_replace( '&amp;','&',$url );
					$url = str_replace( '&#038;','&',$url );
					$url = add_query_arg( 'lang', $this->current_language, remove_query_arg( 'lang', $url ) );
				}
			return $url;
		}

		function wc_qtml_return_url( $return_url ) {

			if ( $this->mode == 1 )
				$return_url = add_query_arg( 'lang', $this->current_language, $return_url );
			elseif ( $this->mode == 2 && strpos( str_replace( array( 'https:', 'http:' ), '', $return_url ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language . '/' ) === false )
				$return_url = str_replace( str_replace( array( 'https:', 'http:' ), '', site_url() ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language, $return_url );

			return $return_url;
		}

		function wc_qtml_payment_url( $result ) {

			if ( $this->mode == 1 )
				$result['redirect'] = add_query_arg( 'lang', $this->current_language, $result['redirect'] );
			elseif ( $this->mode == 2 && strpos( str_replace( array( 'https:', 'http:' ), '', $result['redirect'] ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language . '/' ) === false )
				$result['redirect'] = str_replace( str_replace( array( 'https:', 'http:' ), '', site_url() ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language, $result['redirect'] );

			return $result;
		}

		function wc_qtml_remove_accents( $st ) {
		    $replacement = array(
		        "ί"=>"ι","ό"=>"ο","ύ"=>"υ","έ"=>"ε","ά"=>"α","ή"=>"η",
		        "ώ"=>"ω"
		    );

		    foreach( $replacement as $i=>$u ) {
		        $st = mb_eregi_replace( $i,$u,$st );
		    }
		    return $st;
		}

		function wc_qtml_strip_protocol( $url ) {
		    // removes everything from start of url to last occurence of char in charlist

		    $char = '//';

			$pos = strrpos( $url, $char );

		    $url_stripped = substr( $url, $pos + 2 );

		    return $url_stripped;

		}

	}

	$GLOBALS['woocommerce_qt'] = new WC_QTML();

}
