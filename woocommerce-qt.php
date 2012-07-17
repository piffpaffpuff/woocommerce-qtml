<?php
/*
  Plugin Name: Woocommerce-qT
  Plugin URI: #
  Description: Makes qTranslate play nice with Woocommerce (v1.6.0).
  Author: SomewhereWarm
  Author URI: http://www.somewherewarm.net
  Version: 0.11
 */


class WC_QTML {

	var $enabled_languages;
	var $enabled_locales;
	var $default_language;

	public function __construct() {

		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if ( in_array( 'qtranslate/qtranslate.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				add_action( 'init', array($this, 'qt_woo_init'), 1 );
				add_action( 'plugins_loaded', array($this, 'qt_woo_plugins_loaded' ), 2 );
				add_action( 'wp_head' array($this, 'print_debug' ) );
			}
		}

	}

	function print_debug() {
		echo 'Enabled Languages:';
		print_r( $this->enabled_languages );
	}


	function qt_woo_plugins_loaded(){

		global $q_config;

		$this->enabled_languages = $q_config['enabled_languages'];
		$this->default_language = $q_config['default_language'];
		$this->enabled_locales = $q_config['locale'];

		// customize localization of admin menu
		//remove_action('admin_menu','qtrans_adminMenu');
		add_filter('locale', array($this, 'qt_woo_admin_locale'), 1000);
		add_filter('qtranslate_language', array($this,'qt_woo_admin_lang') );
	}

	function qt_woo_init() {

		// remove generator
		remove_action( 'wp_head', array($GLOBALS['woocommerce'], 'generator') );

		/*
		* FIX Woocommerce stuff
		*/

		// fix ajax pages
		add_filter( 'woocommerce_params', array($this, 'qt_woo_modify_woo_params') );

		// fix almost everything
		add_filter( 'clean_url', array($this,'qt_woo_esc_url_filter') );

		add_filter( 'woocommerce_get_checkout_payment_url', array($this, 'qt_woo_fix_checkout_payment_url_filter') );
		add_filter( 'post_type_link', 'qtrans_convertURL');

		// fix checkout redirects
		add_filter( 'woocommerce_payment_successful_result', array($this, 'qt_woo_fix_payment_url') );
		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array($this, 'qt_woo_fix_no_payment_url') );

		add_filter( 'woocommerce_get_return_url', array($this, 'qt_woo_fix_return_url') );
		add_filter( 'woocommerce_get_cancel_order_url', array($this, 'qt_woo_fix_return_url') );

		// store lang
		add_action('woocommerce_new_order', array($this, 'qt_woo_store_order_language') );

		// customize localization of customer emails
		add_action( 'woocommerce_new_customer_note_notification', array($this, 'qt_woo_switch_email_textdomain_with_args'), 1 );
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array($this, 'qt_woo_switch_email_textdomain'), 1 );
		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array($this, 'qt_woo_switch_email_textdomain'), 1 );
		add_action( 'woocommerce_order_status_completed_notification', array($this,'qt_woo_switch_email_textdomain'), 1 );

		add_action( 'woocommerce_before_send_customer_invoice', array($this, 'qt_woo_before_send_customer_invoice'), 1 );

		// fix payment gateway & shipping method descriptions
		add_filter( 'woocommerce_available_shipping_methods', array($this, 'qt_woo_shipping_methods_filter') );

		add_filter( 'woocommerce_gateway_title', array($this, 'qt_woo_gateway_title_filter'), 10, 2 );
		add_filter( 'woocommerce_gateway_description', array($this, 'qt_woo_gateway_description_filter'), 10, 2 );

		// fix product category listing for translate tags
		add_filter( 'term_links-product_cat', array($this, 'qt_woo_term_links_filter') );

		// fix product single product page items
		add_filter( 'woocommerce_attribute_label', array($this, 'qt_woo_attribute_label_filter') );
		add_filter( 'get_term', array($this, 'qt_woo_term_filter') );
		add_filter( 'woocommerce_variation_option_name', array($this, 'qt_woo_variation_option_name_filter') );
		add_filter( 'woocommerce_attribute', array($this, 'qt_woo_attribute_filter'), 10, 3 );
		add_filter( 'woocommerce_short_description', array($this, 'qt_woo_short_description_filter') );
		// hide coupons meta in emails
		add_filter( 'woocommerce_email_order_meta_keys', array($this, 'qt_woo_hide_email_coupons') );

		// fix localization of date function
		add_filter( 'date_i18n', array($this, 'qt_woo_date_i18n_filter'), 10, 4);

		// fix review comment links and blog comment links
		add_filter( 'get_comment_link', array($this, 'qt_woo_get_comment_link_filter') );
		add_filter( 'paginate_links', array($this, 'qt_woo_get_comment_page_link_filter') );

	}



	// Woocommerce Fixes

	function qt_woo_gateway_title_filter( $title, $gateway_id ) {
		return __( $title );
	}


	function qt_woo_gateway_description_filter( $description, $gateway_id ) {
		return __( $description );
	}


	function qt_woo_shipping_methods_filter( $available_methods ) {
		foreach ( $available_methods as $method ) :
			$method->label = __( esc_html( $method->label ), 'woocommerce' );
		endforeach;
		return $available_methods;
	}


	function qt_woo_attribute_label_filter( $label ) {
		if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
			$label = qtrans_use( $GLOBALS['order_lang'], $label );
		else $label = __( $label );
		return $label;
	}

	function qt_woo_term_filter($term) {
		if ( $term ) {
			if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
				$term->name = qtrans_use( $GLOBALS['order_lang'], $term->name );
			else $term->name = __( $term->name );
		}
		return $term;
	}

	function qt_woo_variation_option_name_filter( $term_name ) {
		return __( $term_name );
	}


	function qt_woo_attribute_filter( $list, $attribute, $values ) {
		return wpautop( wptexturize( implode( ', ', qtrans_use( qtrans_getLanguage(), $values ) ) ) );
	}


	function qt_woo_short_description_filter( $desc ) {
		return __( $desc );
	}


	// fixes localization of date_i18n function
	function qt_woo_date_i18n_filter($j, $format, $i, $gmt) {
		if ( strpos($j, 'of') > 0 ) {
			return date(__('l jS \of F Y h:i:s A', 'woocommerce'), $i);
		}
		return $j;
	}


	// hides coupons meta in emails
	function qt_woo_hide_email_coupons($fields) {
		$empty_fields = array();
		return $empty_fields;
	}


	function qt_woo_get_comment_link_filter($url) {
		if( preg_match("#(\?)lang=([^/]+/)#i", $url, $match) ) {
			$url = preg_replace("#(\?)lang=([^/]+/)#i","",$url);
			$url = preg_replace("#(/)(\#)#i", '/'.rtrim($match[0], '/').'#', $url);
		} else {
			$url = preg_replace("#(/)(\#)#i", '/'.'?lang='.qtrans_getLanguage().'#', $url);
		}
		return $url;
	}


	function qt_woo_get_comment_page_link_filter($url) {
		if( preg_match("#(\?)lang=([^/]+/)#i", $url, $match) ) {
			$url = preg_replace("#(\?)lang=([^/]+/)#i","",$url);
			$url = preg_replace("#(/)(\#)#i", '/'.rtrim($match[0], '/').'#', $url);
		} else {
			$url = preg_replace("#(/)(\#)#i", '/'.'?lang='.qtrans_getLanguage().'#', $url);
		}
		return $url;
	}


	function qt_woo_fix_checkout_payment_url_filter($url) {
		if ( !is_admin() ) {
			$url = qtrans_convertURL($url, qtrans_getLanguage() );
		} else {
			if ( preg_match("#(&|\?)order_id=([^&\#]+)#i",$url,$match) ) {
				$order_id = $match[2];
				$custom_values = get_post_custom_values('language', $order_id);
				$order_lang = $custom_values[0];
				$url = qtrans_convertURL($url, $order_lang, true);
			}
		}
		return $url;
	}


	// fixes product category listing for translate tags
	function qt_woo_term_links_filter($term_links) {
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


	// resets admin locale to english only
	function qt_woo_admin_locale($loc) {
		if ( is_admin() && !is_ajax() ) {
			$loc=$this->enabled_locales[$this->default_language];
		} 
		return $loc;
	}


	function qt_woo_admin_lang( $lang ) {
		if ( is_admin() && !is_ajax() ) {
			return $this->default_language;
		}
		return $lang;
	}


	function qt_woo_switch_email_textdomain( $order_id ) {

		global $q_config;

		if ( $order_id > 0 ) {
			$custom_values = get_post_custom_values( 'language', $order_id );
			$order_lang = $custom_values[0];
			if ( isset( $order_lang ) && $order_lang != '' && $order_lang != qtrans_getLanguage() ) {
				$GLOBALS['order_lang'] = $order_lang;
				$domain = 'woocommerce';
				unload_textdomain( $domain );
				load_textdomain( $domain, WP_PLUGIN_DIR . '/woocommerce/languages/woocommerce-' . $this->enabled_locales[$order_lang] . '.mo' );
			} else { $GLOBALS['order_lang'] = qtrans_getLanguage(); }
		}
	}


	function qt_woo_switch_email_textdomain_with_args ( $args ) {
		$defaults = array(
			'order_id' => ''
		);

		$args = wp_parse_args( $args, $defaults );
		extract( $args );

		$this->qt_woo_switch_email_textdomain( $order_id );
	}


	function qt_woo_before_send_customer_invoice( $order ) {
		$this->qt_woo_switch_email_textdomain( $order->id );
	}


	// stores order language in order object meta
	function qt_woo_store_order_language( $order_id ) {
		if(!get_post_meta($order_id, 'language')) {
			$language = isset( $_SESSION['session_language'] ) ? $_SESSION['session_language'] : qtrans_getLanguage();
			update_post_meta( $order_id, 'language', $language );
		}
	}


	function qt_woo_modify_woo_params( $params ) {
		$params['checkout_url'] = admin_url('admin-ajax.php?action=woocommerce-checkout&lang='. qtrans_getLanguage() );
		$params['ajax_url'] = admin_url('admin-ajax.php?lang='. qtrans_getLanguage() );

		// store session language
		$_SESSION['session_language'] = qtrans_getLanguage();

		return $params;
	}


	function qt_woo_esc_url_filter( $url ) {
		if ( !is_admin() && !preg_match("#(&|\?)lang=#i", $url) ) {
			return qtrans_convertURL( $url, qtrans_getLanguage() );
		} else { return $url; }
	}


	function qt_woo_fix_return_url($return_url) {
		return ( isset($_SESSION['session_language']) ? add_query_arg( 'lang', $_SESSION['session_language'], $return_url ) : $return_url ) ;
	}


	function qt_woo_fix_payment_url( $result ) {
		$result['redirect'] = add_query_arg( 'lang', qtrans_getLanguage(), $result['redirect'] );
		return $result;
	}


	function qt_woo_fix_no_payment_url( $url, $order ) {
		return qtrans_convertURL( $url, qtrans_getLanguage() );
	}


	function qt_woo_remove_accents( $st ) {
	    $replacement = array(
	        "ί"=>"ι","ό"=>"ο","ύ"=>"υ","έ"=>"ε","ά"=>"α","ή"=>"η",
	        "ώ"=>"ω"
	    );

	    foreach( $replacement as $i=>$u ) {
	        $st = mb_eregi_replace( $i,$u,$st );
	    }
	    return $st;
	}

}

$GLOBALS['woocommerce_qt'] = new WC_QTML();


?>
