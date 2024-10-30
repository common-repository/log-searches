var slog_cache = [],
	theresmore = true,
	months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	slog_currentpage = 0;

function slog_get_queries(page) {
	//Use cache if we have it
	if (slog_cache.length > (page - 1) * 10) {
		var sstart = (page - 1) * 10
		slog_draw_results(slog_cache.slice(sstart, sstart + 10))
		slog_arrows(page)
		
	//Get results from db if we don't
	} else {
		var ajax = jQuery.ajax(ajaxurl, {
			method: 'POST',
			data: {
				action: 'slog_records',
				page: page
			}
		});
	
		ajax.success(function(data) {
			slog_draw_results(data);
			slog_cache = slog_cache.concat(data);
			slog_arrows(page);
		});
	}
}

function slog_delete(id) {
	var row = jQuery('#slog-id-'+id).css('opacity','0.5');
	var ajax = jQuery.ajax(ajaxurl, {
		method: 'POST',
		data: {
			action: 'slog_delete',
			id: id
		}
	});
	
	ajax.done(function(data) {
		m = data.status;
		console.log(data);
		console.log(data.status);
		if (m == 1) row.remove();
		else row.css('opacity','1');
		
		//Delete it from the cache also
		for (var i in slog_cache) if (id == slog_cache[i]['id']) {
			var index = i;
			break;
		}
		slog_cache.splice(i,1);
	});
}

function slog_arrows(page) {
	slog_currentpage = page;
	if (slog_numsearches > page * 10) slog_bottom_arrow();
	if (page > 1) slog_top_arrow();
}

function slog_td4() { return jQuery('<td class="slog-arrow" colspan="4">'); }

function slog_bottom_arrow() {
	var a = jQuery('<a href="#">')
		.click(function(e) { slog_get_queries(slog_currentpage + 1); return false; })
		.html('Previous Searches &#9660;');
	slog_td4()
		.addClass('slog-bottom')
		.append(a)
		.appendTo('#searchlog tbody');
}

function slog_top_arrow() {
	var a = jQuery('<a href="#">')
		.click(function(e) { slog_get_queries(slog_currentpage - 1); return false; })
		.html('Next Searches &#9650;')
	slog_td4()
		.addClass('slog-top')
		.append(a)
		.prependTo('#searchlog tbody');
}

function slog_draw_results(data) {
	target = jQuery('#searchlog tbody');
	target.empty();
	for (var r in data) {
		var tr = jQuery('<tr class="author-self status-inherit" valign="top" id="slog-id-'+data[r]['id']+'">'),
			d = new Date(data[r]['time'] * 1000),
			tstr = d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds(),
			slink = jQuery('<a href="'+data[r]['link']+'">').html(break_long_words(data[r]['term'], 20)),
			td1 = jQuery('<td class="column-date" title="'+tstr+'">'),
			td2 = jQuery('<td class="column-term">'),
			td3 = jQuery('<td class="column-ip">'),
			td4 = jQuery('<td class="column-delete">'),
			del = jQuery('<a href="#" class="slog-delete" title="Delete">').html('&times;').appendTo(td4),
			country = data[r]['country'] ? data[r]['country'] : '',
			place = (data[r]['city'] ? data[r]['city'] + ', ' : '') + (data[r]['region'] ? data[r]['region'] + ', ' : '') + country,
			flag = data[r]['country'] ? jQuery('<img src="'+slog_dir+'flags_16/'+country.toLowerCase()+'.png" alt="'+country+'" title="'+place+'" />') : '',
			ip = jQuery('<a href="https://www.iplocation.net/?query='+data[r]['ip']+'" target="_blank">').text(data[r]['ip']);
		
		del[0].slog_id = data[r]['id'];
		td1.text(months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear());
		td2.append(slink);
		td3.append(flag, ' ', ip);
		tr.append(td1, td2, td3, td4).appendTo(target);
	}
}

jQuery(document).ready(function($) {
	slog_get_queries(1);
	$('#searchlog').on('click','.slog-delete',function() {
		slog_delete(this.slog_id);
		return false;
	});
});

function break_long_words(term, max) {
	a = term.split(' ');
	for (var i in a) {
		l = a[i].length;
		if (l > max) {
			n = 0
			while (n+max < a[i].length) {
				n += max;
				a[i] = a[i].slice(0,n) + '&#8203;' + a[i].slice(n); //No-width space
				n += 7; //Account for the space
			}
		}
	}
	return a.join(' ');
}