jQuery(function($) {

	/** Cart Handling */
	$supports_html5_storage = ( 'sessionStorage' in window && window['sessionStorage'] !== null );

	if ( $supports_html5_storage ) {

		$('body')

			.on( 'wc_fragments_refreshed', function() {

				sessionStorage.setItem( "wc_cart_hash", '123' );

			} )

	}
} );
