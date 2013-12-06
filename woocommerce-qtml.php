<?php
/*
  Plugin Name: WooCommerce-qTML
  Plugin URI: http://www.somewherewarm.net
  Description: Adds experimental qTranslate support to WooCommerce.
  Author: SomewhereWarm
  Author URI: http://www.somewherewarm.net
  Version: 1.1.7
 */

class WC_QTML {

	var $version = '1.1.7';

	var $enabled_languages;
	var $enabled_locales;
	var $default_language;
	var $current_language;

	var $mode;

	var $domain_switched = false;

	var $email_textdomains = array(
		'woocommerce' => '/woocommerce/i18n/languages/woocommerce-',
		'wc_shipment_tracking' => '/woocommerce-shipment-tracking/languages/wc_shipment_tracking-'
	);

	public function __construct() {

		require_once('wp-updates-plugin.php');
		new WPUpdatesPluginUpdater_278( 'http://wp-updates.com/api/2/plugin', plugin_basename(__FILE__));

		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if ( in_array( 'qtranslate/qtranslate.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				add_action( 'init', array( $this, 'qt_woo_init' ), 0 );

				// Forces default language in admin area
				// add_action( 'plugins_loaded', array($this, 'qt_woo_plugins_init' ), 1 );

				add_action( 'plugins_loaded', array( $this, 'qt_woo_plugins_loaded' ), 3 );

				// Debug
				// add_action( 'wp_head', array($this, 'print_debug' ) );
			}
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
		print_r( $_SESSION['qtrans_language'] );
	}


	function qt_woo_plugins_loaded(){

		global $q_config;

		$this->enabled_languages = $q_config['enabled_languages'];
		$this->default_language = $q_config['default_language'];
		$this->enabled_locales = $q_config['locale'];

		if ( ! is_admin() || is_ajax() ) {
			if ( in_array( qtrans_getLanguage(), $this->enabled_languages ) ) {
				$this->current_language = qtrans_getLanguage();
				$_SESSION['qtrans_language'] = $this->current_language;
			} elseif ( isset( $_SESSION['qtrans_language'] ) ) {
				$this->current_language = $_SESSION['qtrans_language'];
				$q_config['language'] = $this->current_language;
			} else {
				$this->current_language = $this->default_language;
			}

		} else {
			$this->current_language = empty( $q_config['language'] ) ? $this->default_language : $q_config['language'];
		}

		// get url mode

		// QT_URL_QUERY - query: 1
		// QT_URL_PATH - pre-path: 2
		// QT_URL_DOMAIN - pre-domain: 3

		$this->mode = $q_config['url_mode'];

	}


	function qt_woo_plugins_init(){

		// customize localization of admin menu
		remove_action( 'admin_menu', 'qtrans_adminMenu' );
		add_filter( 'locale', array( $this, 'qt_woo_admin_locale' ), 1000 );
		add_filter( 'qtranslate_language', array( $this,'qt_woo_lang' ) );
	}


	function qt_woo_init() {

		// remove generator
		remove_action( 'wp_head', array( $GLOBALS['woocommerce'], 'generator') );

		/*
		* FIX Woocommerce stuff
		*/

		// fix mini-cart
		// add_action(	'wp_print_scripts', array( $this, 'qt_woo_dequeue_scripts' ) );
		add_action( 'wp_head', array( $this, 'qt_woo_session_lang' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'qt_woo_frontend_scripts' ) );

		// fix ajax pages
		add_filter( 'woocommerce_params', array( $this, 'qt_woo_modify_woo_params' ) );

		// fix almost everything
		add_filter( 'clean_url', array( $this,'qt_woo_esc_url_filter' ) );

		add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'qt_woo_fix_checkout_payment_url_filter' ) );
		add_filter( 'post_type_link', 'qtrans_convertURL');

		// fix checkout redirects
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'qt_woo_fix_payment_url' ) );
		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'qt_woo_fix_no_payment_url' ) );

		add_filter( 'woocommerce_get_return_url', array( $this, 'qt_woo_fix_return_url' ) );
		add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'qt_woo_fix_return_url' ) );

		add_filter( 'woocommerce_get_checkout_url', array( $this,'qt_woo_url_forced_admin_filter' ) );
		add_filter( 'woocommerce_get_cart_url', array( $this,'qt_woo_url_forced_admin_filter' ) );

		// store lang
		add_action('woocommerce_new_order', array( $this, 'qt_woo_store_order_language' ) );

		// customize localization of customer emails

		add_action( 'woocommerce_new_customer_note', array( $this, 'qt_woo_switch_email_textdomain_with_args' ), 1, 2 );

		add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'qt_woo_switch_email_textdomain' ), 1 );
		add_action( 'woocommerce_order_status_pending_to_on-hold', array( $this, 'qt_woo_switch_email_textdomain' ), 1 );
		add_action( 'woocommerce_order_status_completed', array( $this,'qt_woo_switch_email_textdomain' ), 1 );

		add_action( 'woocommerce_order_status_changed', array( $this,'qt_woo_reset_email_textdomain' ), 1 );

		add_action( 'woocommerce_before_send_customer_invoice', array( $this, 'qt_woo_before_send_customer_invoice' ), 1 );

		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'qt_woo_before_resend_email' ), 1 );

		// fix payment gateway & shipping method descriptions
		add_filter( 'woocommerce_available_shipping_methods', array( $this, 'qt_woo_shipping_methods_filter' ) );

		add_filter( 'woocommerce_gateway_title', array( $this, 'qt_woo_gateway_title_filter' ), 10, 2 );
		add_filter( 'woocommerce_gateway_description', array( $this, 'qt_woo_gateway_description_filter' ), 10, 2 );

		// fix product category listing for translate tags
		add_filter( 'term_links-product_cat', array( $this, 'qt_woo_term_links_filter' ) );

		// fix taxonomy titles
		$this->qt_woo_taxonomies_filter();

		add_filter( 'woocommerce_attribute_label', array( $this, 'qt_woo_attribute_label_filter' ) );
		add_filter( 'get_term', array( $this, 'qt_woo_term_filter' ) );
		add_filter( 'get_terms', array( $this, 'qt_woo_terms_filter' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, 'qt_woo_object_term_filter' ), 10, 4 );
		add_filter( 'woocommerce_attribute_taxonomies', array( $this, 'qt_woo_attribute_taxonomies_filter' ) );

		add_filter( 'woocommerce_variation_option_name', array( $this, 'qt_woo_variation_option_name_filter' ) );
		add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'qt_woo_variation_option_name_filter' ) );
		add_filter( 'woocommerce_attribute', array( $this, 'qt_woo_attribute_filter' ), 10, 3 );
		add_filter( 'woocommerce_short_description', array( $this, 'qt_woo_short_description_filter' ) );

		// hide coupons meta in emails
		add_filter( 'woocommerce_email_order_meta_keys', array( $this, 'qt_woo_hide_email_coupons' ) );

		// fix localization of date function
		add_filter( 'date_i18n', array( $this, 'qt_woo_date_i18n_filter' ), 10, 4);

		// fix review comment links and blog comment links
		add_filter( 'get_comment_link', array( $this, 'qt_woo_get_comment_link_filter' ) );
		add_filter( 'paginate_links', array( $this, 'qt_woo_get_comment_page_link_filter' ) );

		// fixes comment_form action
		add_action('comment_form', array( $this, 'qt_woo_comment_post_lang' ) );
		add_filter('comment_post_redirect', array( $this, 'qt_woo_comment_post_redirect' ), 10, 2 );

		add_filter('wp_redirect', array( $this, 'qt_woo_wp_redirect_filter' ) );

		// layered nav links
		add_filter( 'woocommerce_layered_nav_link', array( $this, 'woocommerce_layered_nav_link_filter' ) );

		// composite products
		add_filter( 'woocommerce_bto_component_title', array( $this, 'qt_woo_bto_split' ) );
		add_filter( 'woocommerce_bto_component_description', array( $this, 'qt_woo_bto_split' ) );
		add_filter( 'woocommerce_bto_product_excerpt', array( $this, 'qt_woo_bto_split' ) );
	}



	// Woocommerce Fixes

	function qt_woo_bto_split( $text ) {
		return __( $text );
	}

	function qt_woo_url_forced_admin_filter( $location ) {
		return $this->qt_woo_esc_url_filter( $location );
	}

	function qt_woo_session_lang() {
		?>
		<script type="text/javascript">
			window['qtrans_language'] = <?php echo json_encode( $_SESSION['qtrans_language'] ); ?>
		</script>
		<?php
	}

	function qt_woo_plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}


	function qt_woo_frontend_scripts() {
		wp_register_script( 'qt-woo-fragments', $this->qt_woo_plugin_url() . '/assets/js/wc-cart-fragments-fix.js', array( 'jquery', 'jquery-cookie' ), $this->version, true );
		wp_enqueue_script( 'qt-woo-fragments' );
	}

	function qt_woo_dequeue_scripts() {
		// wp_dequeue_script( 'wc-cart-fragments' );
	}


	function qt_woo_comment_post_lang( $id ) {
		echo '<input type="hidden" name="comment_post_lang" value="' . $this->current_language . '" id="comment_post_lang">';
	}


	function qt_woo_comment_post_redirect( $location, $comment ) {

		if ( ! empty($_POST['comment_post_lang']) ) {

			if ( $this->mode == 1 ) {
				$lang = $_POST['comment_post_lang'];
				$lang = rawurlencode( $lang );
				$arg = array('lang' => $lang );
				$location = add_query_arg($arg, $location);
			}
			elseif ( $this->mode == 2 ) {
				$location = qtrans_convertURL( $location, $_POST['comment_post_lang'], true );
			}
		}

		return $location;
	}


	function qt_woo_wp_redirect_filter( $location ) {

		if ( $this->mode == 1 && ( !is_admin() || is_ajax() ) && strpos( $location, 'wp-admin' ) === false ) {
			$lang = '';
			if ( strpos( $location, 'lang=' ) === false ) {
				$lang = $this->current_language;
				$lang = rawurlencode( $lang );
				$arg = array('lang' => $lang );
				$location = add_query_arg($arg, $location);
			}
		}
		elseif ( $this->mode == 2 && ( !is_admin() || is_ajax() ) && strpos( $location, 'wp-admin' ) === false ) {
			foreach ( $this->enabled_languages as $language ) {
				if ( strpos( $location, '/' . $language . '/' ) > 0 ) {
					return $location;
				}
			}
			$location = str_replace( $this->strip_protocol( site_url() ), $this->strip_protocol( site_url() ) . '/' . $this->current_language, $location );
		}

		return $location;
	}


	function qt_woo_gateway_title_filter( $title, $gateway_id ) {
		return __( $title );
	}


	function qt_woo_gateway_description_filter( $description, $gateway_id ) {
		if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
			$description = qtrans_use( $GLOBALS['order_lang'], $description );
		else $description = __( $description );
		return $description;
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


	function qt_woo_attribute_taxonomies_filter( $attribute_taxonomies ) {
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax )
				if ( isset( $tax->attribute_label ) && ! ( strpos($tax->attribute_label, '[:') === false ) )
					$tax->attribute_label = __( $tax->attribute_label );
		}
		return $attribute_taxonomies;
	}


	function qt_woo_taxonomies_filter() {
		global $wp_taxonomies;

		if ( ! is_admin() )
			return;

		foreach ( $wp_taxonomies as $tax_name => $tax ) {
			if ( $tax->labels )
				$tax->labels = qtrans_use( $this->current_language, $tax->labels );
		}
	}


	function qt_woo_term_filter( $term ) {
		if ( $term ) {
			if ( isset( $GLOBALS['order_lang'] ) && in_array( $GLOBALS['order_lang'], $this->enabled_languages ) )
				$term->name = qtrans_use( $GLOBALS['order_lang'], $term->name );
			elseif ( is_admin() && ! is_ajax() ) {
				// product categories and terms back end fix
			    $screen = get_current_screen();
				if ( ! empty( $screen ) && ! strstr( $screen->id, 'edit-pa_' ) && empty( $_GET['taxonomy'] ) )
					$term->name = __( $term->name );
			}
			else {
				$term->name = __( $term->name );
			}
		}
		return $term;
	}


	function qt_woo_terms_filter( $terms, $taxonomies, $args ) {
		if ( $terms ) {
			foreach ( $terms as $term )
				if ( isset( $term->name ) && ! ( strpos($term->name, '[:') === false ) )
					$term->name = __( $term->name );
		}
		return $terms;
	}


	function qt_woo_object_term_filter( $terms, $object_ids, $taxonomies, $args ) {
		if ( $terms ) {
			foreach ( $terms as $term )
				if ( isset( $term->name ) && ! ( strpos($term->name, '[:') === false ) )
					$term->name = __( $term->name );
		}
		return $terms;
	}


	function qt_woo_variation_option_name_filter( $term_name ) {
		return __( $term_name );
	}


	function qt_woo_attribute_filter( $list, $attribute, $values ) {
		return wpautop( wptexturize( implode( ', ', qtrans_use( $this->current_language, $values ) ) ) );
	}


	function qt_woo_short_description_filter( $desc ) {
		return __( $desc );
	}


	// fixes localization of date_i18n function
	function qt_woo_date_i18n_filter( $j, $format, $i, $gmt ) {
		if ( strpos( $j, 'of' ) > 0 ) {
			return date(__( 'l jS \of F Y h:i:s A', 'woocommerce' ), $i);
		}
		return $j;
	}


	// hides coupons meta in emails
	function qt_woo_hide_email_coupons( $fields ) {
		$empty_fields = array();
		return $empty_fields;
	}


	function qt_woo_get_comment_link_filter( $url ) {

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


	function qt_woo_get_comment_page_link_filter( $url ) {

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


	function qt_woo_fix_checkout_payment_url_filter( $url ) {
		if ( !is_admin() ) {
			$url = qtrans_convertURL( $url, $this->current_language );
		} else {
			if ( preg_match("#(&|\?)order_id=([^&\#]+)#i",$url,$match) ) {
				$order_id = $match[2];
				$custom_values = get_post_custom_values( 'language', $order_id );
				$order_lang = $custom_values[0];
				$url = qtrans_convertURL( $url, $order_lang, true );
			}
		}
		return $url;
	}


	// fixes product category listing for translate tags
	function qt_woo_term_links_filter( $term_links ) {
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
	function qt_woo_admin_locale( $loc ) {
		if ( is_admin() && !is_ajax() ) {
			$loc = $this->enabled_locales[$this->default_language];
		}
		return $loc;
	}


	function qt_woo_lang( $lang ) {
		if ( is_admin() && !is_ajax() ) {
			return 'en';
		}
		return $lang;
	}


	function qt_woo_reset_email_textdomain( $order_id ) {

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


	function qt_woo_switch_email_textdomain( $order_id ) {

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


	function qt_woo_switch_email_textdomain_with_args ( $args ) {
		$defaults = array(
			'order_id' 		=> '',
			'customer_note'	=> ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		$this->qt_woo_switch_email_textdomain( $order_id );
	}


	function qt_woo_before_resend_email( $order ) {
		$this->qt_woo_switch_email_textdomain( $order->id );
	}


	function qt_woo_before_send_customer_invoice( $order ) {
		$this->qt_woo_switch_email_textdomain( $order->id );
	}


	function qt_woo_after_send_customer_invoice( $order ) {
		$domain = 'woocommerce';
		unload_textdomain( $domain );
		load_textdomain( $domain, WP_PLUGIN_DIR . '/woocommerce/languages/woocommerce-' . $this->enabled_locales[$this->current_language] . '.mo' );
	}


	// stores order language in order object meta
	function qt_woo_store_order_language( $order_id ) {
		if(!get_post_meta($order_id, 'language')) {
			$language = $this->current_language;
			update_post_meta( $order_id, 'language', $language );
		}
	}


	function qt_woo_modify_woo_params( $params ) {

		$params['checkout_url'] = $this->qt_woo_esc_url_filter( $params['checkout_url'] ); //admin_url( 'admin-ajax.php?action=woocommerce-checkout&lang='. $this->current_language );

		$params['ajax_url'] = $this->qt_woo_esc_url_filter( $params['ajax_url'] );

		$params['cart_url'] = $this->qt_woo_esc_url_filter( $params['cart_url'] );

		return $params;
	}


	function qt_woo_esc_url_filter( $url ) {
		if ( ( !is_admin() || is_ajax() ) && strpos( $this->strip_protocol( $url ), $this->strip_protocol( site_url() ) . '/' . $this->current_language . '/' ) === false ) {
				$url = str_replace( '&amp;','&',$url );
				$url = str_replace( '&#038;','&',$url );
				$url = add_query_arg( 'lang', $this->current_language, remove_query_arg( 'lang', $url ) );
			}
		return $url;
	}


	function qt_woo_fix_return_url( $return_url ) {
		if ( $this->mode == 1 )
			$return_url = add_query_arg( 'lang', $this->current_language, $return_url );
		elseif ( $this->mode == 2 && strpos( str_replace( array( 'https:', 'http:' ), '', $return_url ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language . '/' ) === false )
			$return_url = str_replace( str_replace( array( 'https:', 'http:' ), '', site_url() ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language, $return_url );

		return $return_url;
	}


	function qt_woo_fix_payment_url( $result ) {

		if ( $this->mode == 1 )
			$result['redirect'] = add_query_arg( 'lang', $this->current_language, $result['redirect'] );
		elseif ( $this->mode == 2 && strpos( str_replace( array( 'https:', 'http:' ), '', $result['redirect'] ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language . '/' ) === false )
			$result['redirect'] = str_replace( str_replace( array( 'https:', 'http:' ), '', site_url() ), str_replace( array( 'https:', 'http:' ), '', site_url() ) . '/' . $this->current_language, $result['redirect'] );

		return $result;
	}


	function qt_woo_fix_no_payment_url( $url, $order ) {
		return qtrans_convertURL( $url, $this->current_language );
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

	function strip_protocol( $url ) {
	    // removes everything from start of url to last occurence of char in charlist

	    $char = '//';

		$pos = strrpos( $url, $char );

	    $url_stripped = substr( $url, $pos + 2 );

	    return $url_stripped;

	}

}

$GLOBALS['woocommerce_qt'] = new WC_QTML();


?>
