/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

var akeebasubs_ri_offset = 0;
var akeebasubs_ri_limit = 0;
var akeebasubs_ri_total = 0;
var akeebasubs_ri_done = 0;

function akeebasubs_checkall()
{
	(function($) {
		var c = document.forms.adminForm.toggle.checked;
		var f = $(document.forms.adminForm);
		$('input[type=checkbox]').each(function(i, el){
			if($(el).attr('name') == 'id[]') {
				$(el).attr('checked',document.forms.adminForm.toggle.checked);
			}
		});
	})(akeeba.jQuery);
}

function akeebasubs_refresh_integrations()
{
	akeebasubs_ri_start();
}

function akeebasubs_ri_start()
{
	akeebasubs_ri_offset = 0;
	akeebasubs_ri_total = 1;
	akeebasubs_ri_done = 0;
	
	(function($) {
		$('#asriPercent').text('0');
		$('#asriSpinner').show();
		$.blockUI({message: $('#refreshMessage'), fadeOut: 2000});
		akeebasubs_ri_step();
	})(akeeba.jQuery);
}

function akeebasubs_ri_step()
{
	(function($) {
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_akeebasubs&view=subrefreshes',
			data: {
				'action'			: 'process',
				'format'			: 'json',
				'forceoffset'		: akeebasubs_ri_offset,
				'forcelimit'		: 250,
				'refresh'			: 1,
				'_token'			: akeebasubs_token
			},
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				akeebasubs_ri_total = msg.total;
				akeebasubs_ri_done += msg.processed;
				akeebasubs_ri_offset += msg.processed;
				
				var percentage = 0;
				if(akeebasubs_ri_total > 0) {
					percentage = 100 * akeebasubs_ri_done / akeebasubs_ri_total;
				}
				$('#asriPercent').text(parseInt(percentage + ' '));
				
				if(akeebasubs_ri_done == akeebasubs_ri_total) {
					$('#asriSpinner').hide();
					window.location.reload();
				}
				
				if( (msg.processed == 0) || (akeebasubs_ri_done == akeebasubs_ri_total) ) {
					$.unblockUI();
				} else {
					akeebasubs_ri_step();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$.unblockUI();
			}
		});		
		
	})(akeeba.jQuery);
}

function doStartConvertSubscriptions(converter)
{
	(function($){
		$('#asriPercent').text('0');
		$('#asriSpinner').show();
		$.blockUI({message: $('#refreshMessage'), fadeOut: 2000});
		
		akeebasubs_ri_done = 0;
		akeebasubs_ri_offset = -1;
		
		doConvertSubscriptions(converter);
	})(akeeba.jQuery);
}

function doConvertSubscriptions(converter)
{
	(function($){
		var myData = {
			'limit'				: 100,
			'action'			: 'import',
			'_token'			: akeebasubs_token
		};
		if(akeebasubs_ri_offset >= 0) {
			myData.offset = akeebasubs_ri_offset;
		}
		$.ajax({
			type: 'POST',
			url: 'index.php?option=com_akeebasubs&view=tool&import='+converter,
			data: myData,
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				if(!msg.splittable) {
					$('#asriPercent').text(100);
					$.unblockUI();
					return;
				}
				
				if(msg.total) {
					akeebasubs_ri_total = msg.steps;
					akeebasubs_ri_done = 0;
					akeebasubs_ri_offset = 0;
					akeebasubs_ri_limit = msg.limit * 1;
				} else {
					akeebasubs_ri_done = msg.step;
					akeebasubs_ri_offset = akeebasubs_ri_offset * 1 + akeebasubs_ri_limit * 1;
				}
				
				var percentage = 0;
				if(akeebasubs_ri_total > 0) {
					percentage = 100 * akeebasubs_ri_done / akeebasubs_ri_total;
				}
				$('#asriPercent').text(parseInt(percentage + ' '));
				
				if(msg.step == akeebasubs_ri_total) {
					$('#asriSpinner').hide();
					$.unblockUI();
				} else {
					doConvertSubscriptions(converter);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$.unblockUI();
			}
		});	
	})(akeeba.jQuery);
}

// Courtesy of php.js
function empty (mixed_var) {
    var key;

    if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || typeof mixed_var === 'undefined') {
        return true;
    }

    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }

    return false;
}