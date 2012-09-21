// in case we want to namespace. don't really need this right now.
var ABT = {};

(function ($) {

	// simple dialogs to confirm certain actions
	ABT.notice = function (msg) {
		var msg = msg || 'Are you sure?';
		return confirm(msg);
	};
	// initialize with jQuery
	$('body').on('click.abtNotice', '[data-notice]', function (e) {
		if ( !ABT.notice($(this).data('notice')) ) return false;
	});
	
})(jQuery);

(function ($) {
		
	// calculate estimated number of days needed to run an experiment (on tools page)
	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	var poz = function (z) {
		var z_max = 6;
		var x, y, w;
		
	    if (z === 0) {
	        x = 0;
	    } else {
	        y = 0.5 * Math.abs(z);
	
	        if ( y > (z_max * 0.5) ) {
	            x = 1;
	
	        }
			else if ( y < 1 ) {
	            w = y * y;
	            x = ((((((((0.000124818987 * w
	                     - 0.001075204047) * w + 0.005198775019) * w
	                     - 0.019198292004) * w + 0.059054035642) * w
	                     - 0.151968751364) * w + 0.319152932694) * w
	                     - 0.531923007300) * w + 0.797884560593) * y * 2;
	        }
			else {
	            y -= 2.0;
	            x = (((((((((((((-0.000045255659 * y
	                           + 0.000152529290) * y - 0.000019538132) * y
	                           - 0.000676904986) * y + 0.001390604284) * y
	                           - 0.000794620820) * y - 0.002034254874) * y
	                           + 0.006549791214) * y - 0.010557625006) * y
	                           + 0.011630447319) * y - 0.009279453341) * y
	                           + 0.005353579108) * y - 0.002141268741) * y
	                           + 0.000535310849) * y + 0.999936657524;
	        }
	    }
	    return z > 0 ? ( (x + 1) * 0.5 ) : ( (1 - x) * 0.5 );
	}

	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	var p_to_z = function (p) {
		var Z_EPSILON = 0.000001;
	    var minz = -6,
			maxz = 6,
			zval = 0,
			pval;

	    if ( p < 0 || p > 1) return -1;

	    while ( (maxz - minz) > Z_EPSILON ) {
	        pval = poz(zval);
	
	        if (pval > p) {
	            maxz = zval;
	        } else {
	            minz = zval;
	        }
	        zval = (maxz + minz) * 0.5;
	    }
	    return zval;
	}

	// calc number of days to confidence
	// params: 	expected conversion rate, desired observed effect, num of variations,
	// 			confidence level, anticipated views/day
	var daysToConfidence = function (opts) {
		var numer, denom, days;
		opts.convRate = opts.convRate / 100;	
		opts.effect = opts.effect / 100;	
		numer = 2 * opts.vars * Math.pow(( p_to_z(opts.conf + (1 - opts.conf)/2) + 
			p_to_z(opts.conf) ), 2) * (1 - opts.convRate);
		denom = Math.pow(opts.effect, 2) * opts.views * opts.convRate;
		days = numer / denom;
		return Math.round(days);
	}
	// hook into jQuery to calculate on form change or submit
	$('body').on('change submit', '#days-to-confidence', function (e) {
		e.preventDefault();
		var form = $(this);
		var array = form.serializeArray();
		var obj = {};
		for ( var i = 0, l = array.length, field; i < l; i++ ) {
			field = array[i];
			obj[field.name] = parseFloat(field.value);
		}
		$('#numDays').text(daysToConfidence(obj));
		return false;
	});
	
	// on load, calculate with defaults and fill in the result field
	$('#days-to-confidence').trigger('change');
	
})(jQuery);