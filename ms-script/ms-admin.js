jQuery(window).on('load', function(){
	var $ = jQuery;
	function strip_tags( str )
	{
		return (new String(str)).replace(/<[^>]*>/g,'');
	} // strip_tags

	var reports = []; //Array of reports used to hide or display items from reports

	// Sales Reports
	window[ 'ms_reload_report' ] = function( e ){
		var e  			  = $(e),
			report_id 	  = e.attr( 'report' ),
			report  	  = reports[ report_id ],
			datasets 	  = [],
			container_id  = '#'+e.attr( 'container' ),
			type 		  = e.attr( 'chart_type' ),
			checked_items = $( 'input[report="'+report_id+'"]:CHECKED' ),
			dataObj;

		checked_items.each( function(){
			var i = $(this).attr( 'item' );
			if( type == 'Pie' ) datasets.push( report[ i ] );
			else datasets.push( report.datasets[ i ] );
		} );

		if ( type == 'Pie' ) dataObj = datasets;
		else dataObj = { 'labels' : report.labels, 'datasets' : datasets };

		new Chart( $( container_id ).find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj, { scaleStartValue: 0 } );
	};

	window[ 'ms_load_report' ] = function( el, id, title, data, type, label, value ){
		function get_random_color() {
			var letters = '0123456789ABCDEF'.split('');
			var color = '#';
			for (var i = 0; i < 6; i++ ) {
				color += letters[Math.round(Math.random() * 15)];
			}
			return color;
		};

		if(el.checked){
			var container = $( '#'+id );

			if( container.html().length){
				container.show();
			}else{
				if( typeof ms_global != 'undefined' ){
					var from  = $( '[name="from_year"]' ).val()+'-'+$( '[name="from_month"]' ).val()+'-'+$( '[name="from_day"]' ).val(),
						to    = $( '[name="to_year"]' ).val()+'-'+$( '[name="to_month"]' ).val()+'-'+$( '[name="to_day"]' ).val();

					jQuery.getJSON( ms_global.aurl, { 'ms-action' : 'paypal-data', 'data' : data, 'from' : from, 'to' : to }, (function( id, title, type, label, value ){
							return function( data ){
										var datasets = [],
											dataObj,
											legend = '',
											color,
											tmp,
											index = reports.length;


										for( var i in data ){
											var v = (data[ i ][ value ]*1).toFixed(2);
											if( typeof tmp == 'undefined' || tmp == null || data[ i ][ label ] != tmp ){
												color 	= get_random_color();
												tmp 	= data[ i ][ label ];
												legend 	+= '<div style="float:left;padding-right:5px;"><input type="checkbox" CHECKED chart_type="'+type+'" container="'+id+'" report="'+index+'" item="'+i+'" onclick="ms_reload_report( this );" /></div><div class="ms-legend-color" style="background:'+color+'"></div><div class="ms-legend-text">'+tmp+'</div><br />';
												if( type == 'Pie' ) datasets.push( { 'value' : v, 'color' : color } );
												else datasets.push( { 'fillColor' : color, 'strokeColor' : color, data:[ v ] } );

											}else{
												datasets[ datasets.length - 1][ 'data' ].push( v );
											}
										}

										var e = $( '#'+id );
										e.html('<div class="ms-chart-title">'+title+'</div><div class="ms-chart-legend"></div><div style="float:left;"><canvas width="400" height="400" ></canvas></div><div style="clear:both;"></div>');

										// Create legend
										e.find( '.ms-chart-legend').html( legend );

										if( type == 'Pie' ) dataObj = datasets;
										else dataObj = { 'labels' : [ 'Currencies' ], 'datasets' : datasets };

										reports[index] = dataObj;
										var chartObj = new Chart( e.find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj );
										e.show();
									}
						})( id, title, type, label, value )
					);
				}
			}
		}else{
			$( '#'+id ).hide();
		}
	};

	// Methods definition

	window[ 'ms_filtering_products_list' ] = function( e ){
		$( e ).closest( 'form' ).submit();
	};

	window[ 'ms_display_more_info' ] = function( e ){
		e = $( e );
		e.parent().hide().siblings( '.ms_more_info' ).show();
	};

	window[ 'ms_hide_more_info' ] = function( e ){
		e = $( e );
		e.parent().hide().siblings( '.ms_more_info_hndl' ).show();
	};

	window['ms_remove'] = function(e){
		$(e).parents('.ms-property-container').remove();
	};

	window['ms_select_element'] = function(e, add_to, new_element_name){
		var v = e.options[e.selectedIndex].value,
			t = e.options[e.selectedIndex].text;
		if(v != 'none'){
			v = (new String(v)).replace(/"/g, '&quot;');
			t = (new String(t)).replace(/"/g, '&quot;');
			$('#'+add_to).append(
				'<li class="ms-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="ms_remove(this);" class="button" value="'+t+' [x]"></li>'
			);
		}
	};

	window['ms_add_element'] = function(input_id, add_to, new_element_name){
		var n = $('#'+input_id),
			v = strip_tags(n.val()).replace(/"/g, '&quot;');

		n.val('');
		if( !/^\s*$/.test(v)){
			$('#'+add_to).append(
				'<li class="ms-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="ms_remove(this);" class="button" value="'+v+' [x]"></li>'
			);
		}
	};

	window ['open_insertion_music_store_window'] = function(){
		var tags = music_store.tags,
			cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));

		cont.dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			closeOnEscape: true,
			close: function(){
				$(this).remove();
			},
			buttons: [
				{text: 'OK', click: function() {
					var a   = $('#artist'),
						b   = $('#album'),
						c 	= $('#columns'),
						g   = $('#genre'),
						l   = $('#load'),
						sc  = '[music_store';

					var v = c.val();
					if(/\d+/.test(v) && v > 1) sc += ' columns='+v;
					if(l[0].selectedIndex) sc += ' load="'+l[0].options[l[0].selectedIndex].value+'"';
					if(g[0].selectedIndex) sc += ' genre='+g[0].options[g[0].selectedIndex].value;
					if(a[0].selectedIndex) sc += ' artist='+a[0].options[a[0].selectedIndex].value;
					if(b[0].selectedIndex) sc += ' album='+b[0].options[b[0].selectedIndex].value;
					sc += ']';
					if(send_to_editor) send_to_editor(sc);
					$(this).dialog("close");
				}}
			]
		});
	};

    window ['open_insertion_music_store_product_window'] = function(){
		var tags = music_store.tags_p,
			cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));

		cont.dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			closeOnEscape: true,
			close: function(){
				$(this).remove();
			},
			buttons: [
				{text: 'OK', click: function() {
					var id  = $('#product_id').val(),
						sc  = '[music_store_product id="'+id+'"]';

					if(send_to_editor) send_to_editor(sc);
					$(this).dialog("close");
				}}
			]
		});
	};

    window ['open_insertion_music_store_product_list_window'] = function(){
		var tags = music_store.tags_l,
			cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));

		cont.dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			closeOnEscape: true,
			close: function(){
				$(this).remove();
			},
			buttons: [
				{text: 'OK', click: function() {
					var list_type  = $('#list_type').val(),
						columns    = $('#columns').val(),
						number     = $('#number');

					if( /^\s*$/.test( columns ) || isNaN( columns * 1 ) || (columns * 1) < 1 )	columns = 1;
					columns *= 1;

					if( /^\s*$/.test( number ) || isNaN( number * 1 ) || (number * 1) < 1 )	number = 3;
					number *= 1;

					if(send_to_editor) send_to_editor( '[music_store_product_list columns="'+columns+'" number="'+number+'" type="'+list_type+'"]');
					$(this).dialog("close");
				}}
			]
		});
	};

	window ['insert_music_store_sales_counter'] = function(){
		var tags = music_store.tags_c,
			cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));

		cont.dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			closeOnEscape: true,
			close: function(){
				$(this).remove();
			},
			buttons: [
				{text: 'OK', click: function() {
					var style  = $('[name="style"]:checked').val(),
						min_length = $('#min_length').val(),
						number     = $('#number');

					if( /^\s*$/.test( min_length ) || isNaN( min_length * 1 ) || (min_length * 1) < 1 )	min_length = 3;
					min_length *= 1;


					if(send_to_editor) send_to_editor( '[music_store_sales_counter min_length="'+min_length+'" style="'+style+'"]');
					$(this).dialog("close");
				}}
			]
		});
	};

	window['delete_purchase'] = function(id){
		if(confirm('Are you sure to delete the purchase record?')){
			var f = $('#purchase_form');
			f.append('<input type="hidden" name="delete_purchase_id" value="'+id+'" />');
			f[0].submit();
		}
	};

	window['resend_email'] = function(id){
		var f = $('#purchase_form');
		f.append('<input type="hidden" name="resend_purchase_id" value="'+id+'" />');
		f[0].submit();
	};

	window['reset_purchase'] = function(id){
		var f = $('#purchase_form');
		f.append('<input type="hidden" name="reset_purchase_id" value="'+id+'" />');
		f[0].submit();
	};

	window['show_purchase'] = function(id){
		var f = $('#purchase_form');
		f.append('<input type="hidden" name="show_purchase_id" value="'+id+'" />');
		f[0].submit();
	};

	function ms_display_alert() {
		alert( 'As long as the WooCommerce integration is enabled, this section will remain disabled.' );
	}

	// Main application
	jQuery('.product-data').on('click', function(evt){
		if($(evt.target).hasClass('button_for_upload')){
			var file_path_field = $(evt.target).parent().find('.file_path');
			var media = wp.media({
					title: 'Select Media File',
					button: {
					text: 'Select Item'
					},
					multiple: false
			}).on('select',
				(function( field ){
					return function() {
						var attachment = media.state().get('selection').first().toJSON(),
							size = ('cover' in music_store) ? music_store['cover'] : 'medium';

						if( typeof attachment[ 'sizes' ] != 'undefined' && typeof attachment[ 'sizes' ][ size ] != 'undefined' )
						{
							var url = attachment[ 'sizes' ][ size ].url;
						}
						else
						{
							var url = attachment.url;
						}

						field.val( url );
					};
				})( file_path_field )
			).open();
			return false;
		}
	});
	$( '#ms_layout' ).on('change',
		function()
		{
			var e = $( this ).find( ':selected' ),
				thumbnail_url = e.attr( 'thumbnail' );

			$( '#ms_layout_thumbnail' ).html( ( typeof thumbnail_url != 'undefined' ) ? '<img src="'+thumbnail_url+'" title="'+e.text()+'" />' : '' );
		}
	);
	$( document ).on( 'change', '[name="ms_woocommerce_integration"]', function(){
		if ( this.checked ) {
			$('.music-store-settings .postbox:not(.ms-woocommerce-integration)').css( {'opacity': 0.5, 'pointer-events': 'none'} ).find(':input').attr('readonly', true );
			$('.music-store-settings .postbox:not(.ms-woocommerce-integration)').wrap('<div class="ms-no-woocommerce-integration-wrapper"></div>');
			$(document).on('click', '.ms-no-woocommerce-integration-wrapper', ms_display_alert);
		} else {
			$(document).off('click', '.ms-no-woocommerce-integration-wrapper', ms_display_alert);
			$('.music-store-settings .postbox:not(.ms-woocommerce-integration)').unwrap('.ms-no-woocommerce-integration-wrapper');
			$('.music-store-settings .postbox:not(.ms-woocommerce-integration)').css( {'opacity': 1, 'pointer-events': 'auto'} ).find(':input').attr('readonly', false);
		}
	} );
	try
	{
		$( '#ms_artist_list' ).sortable({cancel:''});
		$( '#ms_album_list' ).sortable({cancel:''});
	}
	catch(err)
	{}
	$( '[name="ms_woocommerce_integration"]' ).trigger('change');
});