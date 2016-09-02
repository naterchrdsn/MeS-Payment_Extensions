/*!
 * Merchant e-Solutions
 * http://www.merchante-solutions.com
 *
 * Copyright 2014 Merchant e-Solutions
 */
	
	function dumpResults(resultArray) {
		var out = '';
        for (var i in resultArray) {
            out += i + ": " + resultArray[i] + "\n";
        }
        alert(out);
	}
	
	function mesTokenRequest() {
		// Token Library may not have loaded
		if(Mes != 'undefined') {
			var mes_cc = document.getElementById('gateway_cc_number').value;
			var mes_month = document.getElementById('gateway_expiration').value;
			if(mes_month.length == 1) mes_month = '0' + mes_month; // Pad month to 2-digit
			var mes_year = document.getElementById('gateway_expiration_yr').value.substr(2,2); // Cut year to last 2 digits
			Mes.tokenize(mes_cc, mes_month+mes_year, mesTokenResponse);
		}
		else {
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("Tokenize Library failed to load.");
		}
	}
	
	function mesTokenResponse(result) {
		switch(result['code']) {
		case 0: // Success
			// Set hidden field to the token in the library result
			document.getElementById('gateway_cc_token').value = result['token'];
			
			// Truncate card number
			var cc = document.getElementById('gateway_cc_number');
			cc.value = truncate(cc.value);
			
			// Store truncatd card number
			document.getElementById('gateway_cc_truncated').value = cc.value;
			
			// Disable/hide fields
			lockEntry();
			$('mes_err').show().update('Card number tokenized.');
			break;
		case 1: // Unsupported browser
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("Your browser is not compatible with the payment security enforced by this website.<br />Please upgrade or use the latest version of any modern web browser.");
			break;
		case 2: // Invalid CC Number
			$$('#gateway_cc_number').invoke('removeClassName', 'validation-passed').invoke('addClassName', 'validation-failed');
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("Invalid Credit Card Number");
			break;
		case 3: // Exp date is invalid. Should not happen.
			$$('#gateway_cc_number').invoke('removeClassName', 'validation-passed').invoke('addClassName', 'validation-failed');
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("Invalid expiry date or expired card");
			break;
		case 4:	// Payment Gateway Error
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("There was an error processing the request with the gateway: "+result['gateway_text']);
			break;
		case 5: // HTTP error (IE 8,9 only)
		case 6: // Transmission Error
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("There was an error processing the request. Please try again, or contact the administrator.");
			break;
		case 7: // Cross Scheme (non SSL to SSL) Error (IE 8,9 only)
			new Effect.Appear($('mes_err'), {duration:1, from:0.0, to:1.0});
			$('mes_err').show().update("Site must be secured with SSL to proceed. Please contact the administrator.");
			break;
		}
	}
	
	function lockEntry() {
		$('tokenize_button').disable();
		$('tokenize_button').hide();
		$('gateway_cc_number').disable();
		$('gateway_expiration').disable();
		$('gateway_expiration_yr').disable();
	}
	
	function truncate(number) {
		last = number.substring(number.length-4, number.length);
		number = number.replace(new RegExp(".", "ig"),"*");
		return number.substring(0, number.length-4) + last;
	}