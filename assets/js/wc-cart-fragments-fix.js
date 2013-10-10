jQuery(document).ready(function($) {

	/** Cart Handling */
	$supports_html5_storage = ( 'sessionStorage' in window && window['sessionStorage'] !== null );

	if ( $supports_html5_storage ) {

		$('body')

			.on( 'wc_fragments_refreshed', function() {

				var qt_lang = window['qtrans_language'];

				sessionStorage.setItem( "wc_cart_lang", qt_lang );

			} )

			.on( 'wc_fragments_loaded', function() {

				var qt_lang = window['qtrans_language'];

				var mini_cart_lang = sessionStorage.getItem( "wc_cart_lang" );

				if ( qt_lang != mini_cart_lang ) {
					throw "No fragment";
				}

			} );
	}
} );
