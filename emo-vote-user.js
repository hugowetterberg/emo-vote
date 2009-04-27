/* jQuery Checkbox Plugin: http://code.google.com/p/jquery-checkbox */
(function($){

	$.fn.checkbox = function(options) {
	
		/* IE6 background flicker fix */
		try	{ document.execCommand('BackgroundImageCache', false, true);	} catch (e) {}
		
		/* Default settings */
		var settings = {
			cls: 'jquery-checkbox',  /* checkbox  */
			empty: 'empty.png'  /* checkbox  */
		};
		
		/* Processing settings */
		settings = $.extend(settings, options || {});
		
		/* Adds check/uncheck & disable/enable events */
		var addEvents = function(object)
		{
			var checked = object.checked;
			var disabled = object.disabled;
			var $object = jQuery(object);
			
			if ( object.stateInterval )
				clearInterval(object.stateInterval);
			
			object.stateInterval = setInterval(
				function() 
				{
					if ( object.disabled != disabled )
						$object.trigger( (disabled = !!object.disabled) ? 'disable' : 'enable');
					if ( object.checked != checked )
						$object.trigger( (checked = !!object.checked) ? 'check' : 'uncheck');
				}, 
				10 /* in miliseconds. Low numbers this can decrease performance on slow computers, high will increase responce time */
			);
			return $object;
		}
		try { console.log(this); } catch(e) {}
		/* Wrapping all passed elements */
		return this.each(function() 
		{
			var ch = this;
			var $ch = addEvents(ch); /* Adds custom eents and returns */
			
			if (ch.wrapper)
			{
				ch.wrapper.remove();
			}
			
			/* Creating div for checkbox and assigning "hover" event */
			ch.wrapper = jQuery('<span id="input_' + $ch.attr('class').substr(9) + '"class="' + settings.cls + '"><span class="mark"><img src="' + settings.empty + '" /></span></span>');
			ch.wrapperInner = ch.wrapper.children('span');
			ch.wrapper.hover(
				function() { ch.wrapperInner.addClass(settings.cls + '-hover'); },
				function() { ch.wrapperInner.removeClass(settings.cls + '-hover'); }
			);

			/* Wrapping checkbox */
			$ch.css({position: 'absolute', zIndex: -1, opacity: 0}).after(ch.wrapper);
			
			/* Fixing IE6 label behaviour */
			var parents = $ch.parents('label');
			/* Creating "click" event handler for checkbox wrapper*/
			if ( parents.length )
			{
				parents.click(function(e) { $ch.trigger('click', [e]); return ( $.browser.msie && $.browser.version < 7 ); });
			}
			else
			{
				ch.wrapper.click(function(e) { $ch.trigger('click', [e]); });
			}
			
			delete parents;
				
			$ch.bind('disable', function() { ch.wrapperInner.addClass(settings.cls+'-disabled');}).bind('enable', function() { ch.wrapperInner.removeClass(settings.cls+'-disabled');});
			$ch.bind('check', function() { ch.wrapper.addClass(settings.cls+'-checked' );}).bind('uncheck', function() { ch.wrapper.removeClass(settings.cls+'-checked' );});
			
			/* Disable image drag-n-drop  */
			jQuery('img', ch.wrapper).bind('dragstart', function () {return false;}).bind('mousedown', function () {return false;});
			
			/* Firefox div antiselection hack */
			if ( window.getSelection )
				ch.wrapper.css('MozUserSelect', 'none');
			
			/* Applying checkbox state */
			if ( ch.checked )
				ch.wrapper.addClass(settings.cls + '-checked');
			if ( ch.disabled )
				ch.wrapperInner.addClass(settings.cls + '-disabled');
		});
	};
})(jQuery);
(function($) {
	$.fn.extend({
		emoDialog:function(options) {
			this.append('<div id="emo-vote-body"><div id="emo-vote-dialog"><p>'+options.str+'?<br /><br /><a href="#">No</a>&nbsp;&nbsp;<a href="#'+options.option+'">Yes</a></p></div></div>');
			
			jQuery('#emo-vote-dialog a').click(function() {
				var option = jQuery(this).attr('href').substr(1);
				
				jQuery('#emo-vote-body').fadeOut(500,function() {
					jQuery(this).remove();
					
					if(option)
						jQuery('.emo-vote input[@name=emo_vote-'+option+']').click();
				});
				return;
			});
		}
	});
})(jQuery);

jQuery(document).ready(function() {
	jQuery('.emo-vote input[@type=checkbox]').checkbox({
		cls: 'jquery-checkbox',
		empty: jQuery('input.emo_url').val()+'images/empty.png'
	});
	jQuery('.emo-vote input[@type=checkbox]').click(function() {
		if(jQuery(this).attr('disabled'))
			return;
		
		var option = jQuery(this).attr('class').substr(9),post = jQuery(this).parent().attr('id').substr(9),url = jQuery('input.emo_url').val();
		
		jQuery('#emo-vote_'+post+' input[@type=checkbox]').attr('disabled',true);
		jQuery(this).attr('checked',true);
		
		jQuery.ajax({
			type: 'POST',
			data: 'emo_vote=1&option='+option+'&post='+post,
			dataType: 'jsonp',
			url: url+'emo-vote-ajax.php',
			success: function(j) {
				if(j.response.status == 200) {
					var i = 0,str,locale;
					
					for(; i < 5; i++) {
						if(jQuery('#emo-vote_'+post+' input[@name=emo_vote-'+i+']').length > 0) {
							jQuery('#emo-vote_'+post+' span.emo_vote-'+i).html('('+j.response.numbers[0]['vote_'+i]+')');
						}
					}
					if(jQuery('#emo-vote_'+post+' .emo_vote_total').length > 0) {
						locale = jQuery('input.emo_locale').val().split('#');
						if(j.response.numbers[0]['vote_total'] > 1) {
							str = j.response.numbers[0]['vote_total'] + ' ' + locale[2].substr(2);
						} else if(j.response.numbers[0]['vote_total'] == 1) {
							str = locale[1];
						}
						jQuery('#emo-vote_'+post+' .emo_vote_total').html(str);
					}
				} else if(j.response.status == 500) {
					alert('An error occurred, please try again');
				}
			}
		});
	});
});