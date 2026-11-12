( function ( $ ) {
	'use strict';

	// The classic checkout replaces the payment box after every update, so
	// remember what the customer typed and restore it afterwards.
	var state = { method: '', txn: '', sender: '' };

	function showMethod( method ) {
		$( '.raqpay-fields .raqpay-instructions' ).each( function () {
			var $panel = $( this );
			$panel.prop( 'hidden', $panel.attr( 'data-method' ) !== method );
		} );
	}

	$( document.body ).on( 'change', 'input[name="raqpay_method"]', function () {
		state.method = this.value;
		showMethod( state.method );
	} );

	$( document.body ).on( 'input', '#raqpay_txn_id', function () {
		state.txn = this.value;
	} );

	$( document.body ).on( 'input', '#raqpay_sender', function () {
		state.sender = this.value;
	} );

	$( document.body ).on( 'updated_checkout', function () {
		var $root = $( '.raqpay-fields' );

		if ( ! $root.length ) {
			return;
		}

		if ( state.method ) {
			$root.find( 'input[name="raqpay_method"]' ).each( function () {
				this.checked = this.value === state.method;
			} );
		}

		if ( state.txn ) {
			$( '#raqpay_txn_id' ).val( state.txn );
		}

		if ( state.sender ) {
			$( '#raqpay_sender' ).val( state.sender );
		}

		showMethod( $root.find( 'input[name="raqpay_method"]:checked' ).val() );
	} );
} )( jQuery );
