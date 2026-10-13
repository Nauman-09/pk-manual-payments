( function ( $ ) {
	'use strict';

	// The classic checkout replaces the payment box after every update, so
	// remember what the customer typed and restore it afterwards.
	var state = { method: '', txn: '', sender: '' };

	function showMethod( method ) {
		$( '.pkmp-fields .pkmp-instructions' ).each( function () {
			var $panel = $( this );
			$panel.prop( 'hidden', $panel.attr( 'data-method' ) !== method );
		} );
	}

	$( document.body ).on( 'change', 'input[name="pkmp_method"]', function () {
		state.method = this.value;
		showMethod( state.method );
	} );

	$( document.body ).on( 'input', '#pkmp_txn_id', function () {
		state.txn = this.value;
	} );

	$( document.body ).on( 'input', '#pkmp_sender', function () {
		state.sender = this.value;
	} );

	$( document.body ).on( 'updated_checkout', function () {
		var $root = $( '.pkmp-fields' );

		if ( ! $root.length ) {
			return;
		}

		if ( state.method ) {
			$root.find( 'input[name="pkmp_method"]' ).each( function () {
				this.checked = this.value === state.method;
			} );
		}

		if ( state.txn ) {
			$( '#pkmp_txn_id' ).val( state.txn );
		}

		if ( state.sender ) {
			$( '#pkmp_sender' ).val( state.sender );
		}

		showMethod( $root.find( 'input[name="pkmp_method"]:checked' ).val() );
	} );
} )( jQuery );
