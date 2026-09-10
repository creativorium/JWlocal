/* Yapp checkout CTA — lives next to "Gunakan Transfer Manual" on the checkout
   page. Reads the buyer's info straight off the checkout form fields (same
   pattern as manual-payment.js) instead of asking again. */
( function () {
	'use strict';

	var CFG = window.JWT_YAPP || {};

	function val( id ) {
		var el = document.getElementById( id );
		return el ? String( el.value || '' ).trim() : '';
	}

	function showWarn( el, text ) {
		if ( ! el ) { return; }
		el.textContent = text;
		el.hidden = false;
	}
	function hideWarn( el ) {
		if ( el ) { el.hidden = true; }
	}

	// --- Checkout CTA -----------------------------------------------------
	var btn = document.getElementById( 'jwt-yapp-btn' );
	var warn = document.getElementById( 'jwt-yapp-warning' );

	if ( btn ) {
		btn.addEventListener( 'click', function () {
			var first = val( 'billing_first_name' );
			var last = val( 'billing_last_name' );
			var email = val( 'billing_email' );
			var phone = val( 'billing_phone' );
			var discord = val( 'discord_username' );
			var termsEl = document.getElementById( 'jw_accept_terms' );
			var terms = termsEl ? termsEl.checked : false;

			// Mirrors the server-side checks in ajax_create_invoice(), which are the
			// authoritative ones — this is only here to fail fast with a clear message.
			if ( ! first || ! last || ! email || ! phone || ! discord ) {
				showWarn( warn, CFG.msg_incomplete );
				return;
			}
			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				showWarn( warn, CFG.msg_email );
				return;
			}
			if ( termsEl && ! terms ) {
				showWarn( warn, CFG.msg_terms );
				return;
			}
			hideWarn( warn );

			btn.disabled = true;
			var original = btn.textContent;
			btn.textContent = 'Memproses…';

			var body = new URLSearchParams( {
				action: 'jwt_yapp_create_invoice',
				nonce: CFG.nonce,
				first_name: first,
				last_name: last,
				email: email,
				phone: phone,
				discord: discord,
				terms: terms ? '1' : ''
			} );

			fetch( CFG.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						window.location.replace( res.data.url );
						return;
					}
					btn.disabled = false;
					btn.textContent = original;
					showWarn( warn, ( res && res.data && res.data.message ) || CFG.msg_generic );
				} )
				.catch( function () {
					btn.disabled = false;
					btn.textContent = original;
					showWarn( warn, CFG.msg_generic );
				} );
		} );
	}

	// --- Mock checkout screen: "Simulate Payment Success" ------------------
	var payBtn = document.getElementById( 'jwt-yapp-mock-pay' );
	if ( payBtn ) {
		payBtn.addEventListener( 'click', function () {
			payBtn.disabled = true;
			var original = payBtn.textContent;
			payBtn.textContent = 'Processing…';

			var body = new URLSearchParams( { action: 'jwt_yapp_mock_pay', nonce: CFG.nonce, invoice: payBtn.dataset.invoice } );

			fetch( CFG.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success && res.data && res.data.url ) {
						window.location.replace( res.data.url );
						return;
					}
					payBtn.disabled = false;
					payBtn.textContent = original;
					var msg = document.getElementById( 'jwt-yapp-mock-msg' );
					showWarn( msg, ( res && res.data && res.data.message ) || CFG.msg_generic );
				} )
				.catch( function () {
					payBtn.disabled = false;
					payBtn.textContent = original;
				} );
		} );
	}
} )();
