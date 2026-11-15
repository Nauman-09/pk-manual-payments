( function ( $ ) {
	'use strict';

	// The classic checkout replaces the payment box after every update, so
	// remember what the customer typed and restore it afterwards.
	var state = { method: '', txn: '', sender: '' };

	function showMethod( method ) {
		$( '.nmpay-fields .nmpay-instructions' ).each( function () {
			var $panel = $( this );
			$panel.prop( 'hidden', $panel.attr( 'data-method' ) !== method );
		} );
	}

	$( document.body ).on( 'change', 'input[name="nmpay_method"]', function () {
		state.method = this.value;
		showMethod( state.method );
	} );

	$( document.body ).on( 'input', '#nmpay_txn_id', function () {
		state.txn = this.value;
	} );

	$( document.body ).on( 'input', '#nmpay_sender', function () {
		state.sender = this.value;
	} );

	$( document.body ).on( 'updated_checkout', function () {
		var $root = $( '.nmpay-fields' );

		if ( ! $root.length ) {
			return;
		}

		if ( state.method ) {
			$root.find( 'input[name="nmpay_method"]' ).each( function () {
				this.checked = this.value === state.method;
			} );
		}

		if ( state.txn ) {
			$( '#nmpay_txn_id' ).val( state.txn );
		}

		if ( state.sender ) {
			$( '#nmpay_sender' ).val( state.sender );
		}

		showMethod( $root.find( 'input[name="nmpay_method"]:checked' ).val() );
	} );
} )( jQuery );
