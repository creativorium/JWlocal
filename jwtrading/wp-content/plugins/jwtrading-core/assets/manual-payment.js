/* Manual bank-transfer front-end: checkout gate + capture, and the verify/cancel screens. */
( function () {
	'use strict';

	var CFG = window.JWT_MANUAL || {};

	function val( id ) {
		var el = document.getElementById( id );
		return el ? String( el.value || '' ).trim() : '';
	}

	function post( action, data, done, fail ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', CFG.nonce );
		Object.keys( data || {} ).forEach( function ( k ) { body.set( k, data[ k ] ); } );
		fetch( CFG.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		} )
			.then( function ( r ) { return r.json(); } )
			.then( done )
			.catch( fail || function () {} );
	}

	// --- Checkout: gate the "Gunakan Transfer Manual" button ------------------
	var mBtn = document.getElementById( 'jwt-manual-btn' );
	var mWarn = document.getElementById( 'jwt-manual-warning' );

	if ( mBtn ) {
		mBtn.addEventListener( 'click', function () {
			var L = CFG.labels || {};
			var missing = [];
			var first = val( 'billing_first_name' );
			var last = val( 'billing_last_name' );
			var email = val( 'billing_email' );
			var phone = val( 'billing_phone' );
			var discord = val( 'discord_username' );
			var terms = document.getElementById( 'jw_accept_terms' );

			if ( ! first ) { missing.push( L.first ); }
			if ( ! last ) { missing.push( L.last ); }
			if ( ! email ) { missing.push( L.email ); }
			if ( ! phone ) { missing.push( L.phone ); }
			if ( ! discord ) { missing.push( L.discord ); }
			if ( terms && ! terms.checked ) { missing.push( L.terms ); }

			if ( missing.length ) {
				showWarn( CFG.msg_incomplete + ' <strong>' + missing.join( ', ' ) + '</strong>' );
				return;
			}
			if ( email && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				showWarn( CFG.msg_email );
				return;
			}

			hideWarn();
			mBtn.disabled = true;
			var original = mBtn.textContent;
			mBtn.textContent = 'Memproses…';

			post( 'jwt_manual_create',
				{ first_name: first, last_name: last, email: email, phone: phone, discord: discord },
				function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						// replace() so the checkout page isn't left in history (its cart
						// gets emptied → Back would hit "session expired").
						window.location.replace( res.data.url );
					} else {
						mBtn.disabled = false;
						mBtn.textContent = original;
						showWarn( ( res && res.data && res.data.message ) || CFG.msg_generic );
					}
				},
				function () {
					mBtn.disabled = false;
					mBtn.textContent = original;
					showWarn( CFG.msg_generic );
				}
			);
		} );
	}

	function showWarn( html ) {
		if ( ! mWarn ) { return; }
		mWarn.innerHTML = html;
		mWarn.hidden = false;
	}
	function hideWarn() {
		if ( mWarn ) { mWarn.hidden = true; }
	}

	// --- Instruction screen: reveal verify form, bank "Lainnya", submit -------
	var toggle = document.getElementById( 'jwt-manual-verify-toggle' );
	var vForm = document.getElementById( 'jwt-manual-verify-form' );

	if ( toggle && vForm ) {
		toggle.addEventListener( 'click', function () {
			vForm.hidden = false;
			toggle.hidden = true;
			vForm.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		} );
	}

	var bankSel = document.getElementById( 'jwt-manual-bank' );
	var bankOther = document.getElementById( 'jwt-manual-bank-other-wrap' );
	if ( bankSel && bankOther ) {
		bankSel.addEventListener( 'change', function () {
			bankOther.hidden = ( bankSel.value !== 'Lainnya' );
		} );
	}

	if ( vForm ) {
		vForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var msg = document.getElementById( 'jwt-manual-verify-msg' );
			var acct = val( 'jwt-manual-account-name' );
			var bank = bankSel ? bankSel.value : '';
			if ( bank === 'Lainnya' ) { bank = val( 'jwt-manual-bank-other' ); }

			function vMsg( t ) { if ( msg ) { msg.textContent = t; msg.hidden = false; } }

			if ( ! acct ) { vMsg( CFG.msg_account ); return; }
			if ( ! bank ) { vMsg( CFG.msg_bank ); return; }
			if ( msg ) { msg.hidden = true; }

			var submit = document.getElementById( 'jwt-manual-verify-submit' );
			var origSubmit = submit ? submit.textContent : 'Kirim Konfirmasi';
			if ( submit ) { submit.disabled = true; submit.textContent = 'Mengirim…'; }

			post( 'jwt_manual_verify',
				{ token: CFG.token, account_name: acct, bank: bank },
				function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						// replace() so the emptied-cart checkout isn't left in history.
						window.location.replace( res.data.url );
					} else {
						if ( submit ) { submit.disabled = false; submit.textContent = origSubmit; }
						vMsg( ( res && res.data && res.data.message ) || CFG.msg_generic );
					}
				},
				function () {
					if ( submit ) { submit.disabled = false; submit.textContent = origSubmit; }
					vMsg( CFG.msg_generic );
				}
			);
		} );
	}

	// --- Cancel (Batal) -------------------------------------------------------
	var cancel = document.getElementById( 'jwt-manual-cancel' );
	if ( cancel ) {
		cancel.addEventListener( 'click', function () {
			if ( ! window.confirm( CFG.confirm_cancel ) ) { return; }
			cancel.disabled = true;
			post( 'jwt_manual_cancel', { token: CFG.token }, function ( res ) {
				window.location.href = ( res && res.data && res.data.url ) ? res.data.url : '/';
			}, function () { cancel.disabled = false; } );
		} );
	}
} )();
