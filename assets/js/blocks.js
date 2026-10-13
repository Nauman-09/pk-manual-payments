( function () {
	'use strict';

	var registry = window.wc && window.wc.wcBlocksRegistry;
	var wcSettings = window.wc && window.wc.wcSettings;

	if ( ! registry || ! wcSettings || ! window.wp ) {
		return;
	}

	var createElement = window.wp.element.createElement;
	var useState = window.wp.element.useState;
	var useEffect = window.wp.element.useEffect;
	var __ = window.wp.i18n.__;
	var decodeEntities = window.wp.htmlEntities.decodeEntities;

	var data = wcSettings.getSetting( 'pkmp_data', {} );
	var methods = data.methods || [];

	if ( ! methods.length ) {
		return;
	}

	var TXN_PATTERN = /^[A-Za-z0-9\-_\/]{4,40}$/;
	var title = decodeEntities( data.title || '' ) || __( 'Manual payment', 'pk-manual-payments' );

	function findMethod( key ) {
		for ( var i = 0; i < methods.length; i++ ) {
			if ( methods[ i ].key === key ) {
				return methods[ i ];
			}
		}
		return methods[ 0 ];
	}

	function Instructions( props ) {
		var method = props.method;
		var children = [];

		if ( method.rows && method.rows.length ) {
			children.push(
				createElement(
					'ul',
					{ className: 'pkmp-details', key: 'rows' },
					method.rows.map( function ( row, index ) {
						return createElement(
							'li',
							{ key: index },
							createElement( 'strong', null, row.label + ': ' ),
							row.value
						);
					} )
				)
			);
		}

		if ( method.instructions ) {
			children.push(
				createElement( 'p', { className: 'pkmp-instruction-text', key: 'text' }, method.instructions )
			);
		}

		return createElement( 'div', { className: 'pkmp-instructions' }, children );
	}

	function Field( props ) {
		return createElement(
			'div',
			{ className: 'pkmp-field' },
			createElement(
				'label',
				{ htmlFor: props.id },
				props.label,
				props.required
					? createElement( 'abbr', { className: 'required', title: __( 'required', 'pk-manual-payments' ) }, ' *' )
					: null
			),
			createElement( 'input', {
				type: 'text',
				id: props.id,
				value: props.value,
				maxLength: 40,
				autoComplete: 'off',
				onChange: function ( event ) {
					props.onChange( event.target.value );
				},
			} ),
			props.hint ? createElement( 'span', { className: 'pkmp-hint' }, props.hint ) : null
		);
	}

	function Content( props ) {
		var eventRegistration = props.eventRegistration;
		var emitResponse = props.emitResponse;
		var onPaymentSetup = eventRegistration.onPaymentSetup || eventRegistration.onPaymentProcessing;
		var ERROR = emitResponse.responseTypes.ERROR;
		var SUCCESS = emitResponse.responseTypes.SUCCESS;

		var methodState = useState( data.defaultMethod || methods[ 0 ].key );
		var method = methodState[ 0 ];
		var setMethod = methodState[ 1 ];

		var txnState = useState( '' );
		var txn = txnState[ 0 ];
		var setTxn = txnState[ 1 ];

		var senderState = useState( '' );
		var sender = senderState[ 0 ];
		var setSender = senderState[ 1 ];

		useEffect(
			function () {
				return onPaymentSetup( function () {
					var cleanTxn = txn.replace( /\s+/g, '' ).toUpperCase();

					if ( data.requireTxn && ! cleanTxn ) {
						return {
							type: ERROR,
							message: __( 'Please enter your transaction ID.', 'pk-manual-payments' ),
						};
					}

					if ( cleanTxn && ! TXN_PATTERN.test( cleanTxn ) ) {
						return {
							type: ERROR,
							message: __( 'The transaction ID looks invalid. Please check it and try again.', 'pk-manual-payments' ),
						};
					}

					return {
						type: SUCCESS,
						meta: {
							paymentMethodData: {
								pkmp_method: method,
								pkmp_txn_id: cleanTxn,
								pkmp_sender: data.askSender ? sender : '',
							},
						},
					};
				} );
			},
			[ onPaymentSetup, ERROR, SUCCESS, method, txn, sender ]
		);

		return createElement(
			'div',
			{ className: 'pkmp-fields' },
			data.description
				? createElement( 'p', { className: 'pkmp-description' }, decodeEntities( data.description ) )
				: null,
			createElement(
				'fieldset',
				{ className: 'pkmp-methods' },
				createElement( 'legend', null, __( 'I will pay via', 'pk-manual-payments' ) ),
				methods.map( function ( item ) {
					return createElement(
						'label',
						{ className: 'pkmp-method', key: item.key },
						createElement( 'input', {
							type: 'radio',
							name: 'pkmp_method',
							value: item.key,
							checked: method === item.key,
							onChange: function () {
								setMethod( item.key );
							},
						} ),
						createElement( 'span', null, item.label )
					);
				} )
			),
			createElement( Instructions, { method: findMethod( method ) } ),
			createElement( Field, {
				id: 'pkmp-txn-id',
				label: __( 'Transaction ID', 'pk-manual-payments' ),
				required: !! data.requireTxn,
				value: txn,
				onChange: setTxn,
				hint: __( 'Find it in the confirmation message or receipt from your bank or wallet app.', 'pk-manual-payments' ),
			} ),
			data.askSender
				? createElement( Field, {
						id: 'pkmp-sender',
						label: __( 'Sent from (number or account, optional)', 'pk-manual-payments' ),
						value: sender,
						onChange: setSender,
				  } )
				: null
		);
	}

	function Preview() {
		return createElement(
			'div',
			{ className: 'pkmp-fields' },
			createElement(
				'p',
				{ className: 'pkmp-description' },
				decodeEntities( data.description || '' ) || title
			)
		);
	}

	registry.registerPaymentMethod( {
		name: 'pkmp',
		label: createElement( 'span', { className: 'pkmp-label' }, title ),
		ariaLabel: title,
		content: createElement( Content, null ),
		edit: createElement( Preview, null ),
		canMakePayment: function () {
			return true;
		},
		paymentMethodId: 'pkmp',
		supports: {
			features: data.supports || [ 'products' ],
		},
	} );
} )();
