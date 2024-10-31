var codepeople_music_store = function(){
	var $ = jQuery;
	if('undefined' != typeof $.codepeople_music_store_flag) return;
	$.codepeople_music_store_flag = true;

	//------------------------ PRIVATE FUNCTIONS ------------------------

	/**
	 * Get the screen width
	 */
	function _getWidth()
	{
		var myWidth = 0;
		if( typeof( window.innerWidth ) == 'number' ) {
			//Non-IE
			myWidth = window.innerWidth;
		} else if( document.documentElement && document.documentElement.clientWidth ) {
			//IE 6+ in 'standards compliant mode'
			myWidth = document.documentElement.clientWidth;
		} else if( document.body && document.body.clientWidth ) {
			//IE 4 compatible
			myWidth = document.body.clientWidth;
		}

		/* if( typeof window.devicePixelRatio != 'undefined' && window.devicePixelRatio ) myWidth = myWidth/window.devicePixelRatio; */
		return ( typeof screen != 'undefined' ) ? Math.min( screen.width, myWidth ) : myWidth;
	};

	/**
	 * Increase the product's popularity
	 */
	function _increasePopularity( e )
	{
		e = $(e)
		var r = e.data('title'),
			p = e.closest('[data-productid]'),
			id = (p.length) ? p.data('productid') : 0,
			url = ( ms_global && ms_global['hurl'] ) ? ms_global['hurl'] : '/';
		if(id)
			$.post(
				url,
				{'ms-action':'popularity', 'id':id, 'review':r, '_msr' : Date.now()},
				(function(id){
					return function(data)
					{
						if(data && 'average' in data)
						{
							var r = Math.floor(data.average)*1, v = data.votes*1;
							$('[data-productid="'+id+'"]').each(function(){
								var e = $(this);
								e.find('.star').each(function(i,s){
									if(i+1 <= r) $(s).removeClass('star-inactive').addClass('star-active');
									else $(s).removeClass('star-active').addClass('star-inactive');
								});
								e.find('.votes').text(v);
							});
						}
					};
				})(id),
				'json'
			);
	};

	/**
	 * Increase the number of playbacks per song
	 */
	function _increasePlays( e )
	{
		var id = e.attr( 'data-product' );
		if( typeof id != 'undefined' )
		{
			var url = ( ms_global && ms_global['hurl'] ) ? ms_global['hurl'] : '/';
			$.post( url, { 'ms-action': 'plays', 'id' : id });
		}
	};

	/**
	 * Play next player
	 */
	function _playNext( playerNumber )
	{
		if( playerNumber+1 < player_counter )
		{
			var toPlay = playerNumber+1;
			if( players[ toPlay ] instanceof jQuery && players[ toPlay ].is( 'a' ) ) players[ toPlay ].trigger('click');
			else players[ toPlay ].play();
		}
	};
	//------------------------ PUBLIC FUNCTIONS ------------------------

	/**
	 * For compatible with the professional version of the plugin
	 */
	window['ms_buy_now'] = function(e, id){
		return true;
	};

	/**
	 * Countdown in the download page
	 */
	window['music_store_counting'] = function()
    {
        var loc = document.location.href;
        document.getElementById( "music_store_error_mssg" ).innerHTML = timeout_text+' '+timeout_counter;
        if( timeout_counter == 0 )
        {
            if(loc.indexOf('timeout') == -1)
                loc+=( ( loc.indexOf( '?' ) == -1 ) ? '?' : '&' )+'timeout=1';
            document.location.href = loc;
        }
        else
        {
            timeout_counter--;
            setTimeout( music_store_counting, 1000 );
        }
    };

	//------------------------ MAIN CODE ------------------------
	var min_screen_width = ('min_screen_width' in window) ? min_screen_width: 480,

		// Players
		loadMidiClass = false,
		players = [],
		player_counter = 0,
		s = $('.ms-player.single audio'),
		m = $('.ms-player.multiple audio'),
		c = {
				iPadUseNativeControls: false,
				iPhoneUseNativeControls: false,
				success: function( media, dom ){
					media.addEventListener( 'timeupdate', function( e ){
						e = e.detail.target;
						if( !isNaN( e.currentTime ) && !isNaN( e.duration ) && e.src.indexOf( 'ms-action=secure' ) != -1 )
						{
							if( e.duration - e.currentTime < 2 )
							{
								e.volume = e.volume - e.volume / 3;
							}
							else
							{
								if( typeof e[ 'bkVolume' ] == 'undefined' ) e[ 'bkVolume' ] = e.volume;
								e.volume = e.bkVolume;
							}

						}
					});
					media.addEventListener( 'volumechange', function( e ){
						e = e.detail.target;
						if( !isNaN( e.currentTime ) && !isNaN( e.duration ) && e.src.indexOf( 'ms-action=secure' ) != -1 )
						{
							if( ( e.duration - e.currentTime > 4 ) && e.currentTime )  e[ 'bkVolume' ] = e.volume;
						}
					});

					media.addEventListener( 'ended', function( e ){
						if(
							ms_global && ms_global[ 'play_all' ]*1
						)
						{
							var playerNumber = $(e.detail.target).attr('playerNumber')*1;
							_playNext( playerNumber );
						}
					});
				}
			};

	s.each(function(){
		var e 	= $(this),
			src = e.find( 'source' ).attr( 'src' );

		if( /\.mid$/i.test( src ) )
		{
			var replacement = $( '<a href="#" data-href="'+src+'" class="midiPlayer midiPlay" data-product="'+e.attr( 'data-product' )+'"><span></span></a>' );
			e.replaceWith( replacement );
			e = replacement;
			e.closest( '.ms-player' ).css( 'background', 'transparent' );
			players[ player_counter ] = e;
			loadMidiClass = true;
		}
		else
		{
			c['audioVolume'] = 'vertical';
			players[ player_counter ] = new MediaElementPlayer(e[0], c);
		}
		e.attr('playerNumber', player_counter);
		player_counter++;
	});


	m.each(function(){
		var e = $(this),
			src = e.find( 'source' ).attr( 'src' );

		if( /\.mid$/i.test( src ) )
		{
			var replacement = $( '<a href="#" data-href="'+src+'" class="midiPlayer midiPlay" data-product="'+e.attr( 'data-product' )+'"><span></span></a>' );
			e.replaceWith( replacement );
			e = replacement;
			players[ player_counter ] = e;
			loadMidiClass = true;
		}
		else
		{
			c['features'] = ['playpause'];
			players[ player_counter ] = new MediaElementPlayer(e[0], c);
		}
		e.attr('playerNumber', player_counter);
		player_counter++;
	});

	if( loadMidiClass )
	{
		$( 'body' ).append( '<script type="text/javascript" src="//www.midijs.net/lib/midi.js"></script>' );
		var MIDIjs_counter = 10,
			checkMIDIjsObj = setInterval(
				function()
				{
					MIDIjs_counter--;
					if( MIDIjs_counter < 0 ) clearInterval( checkMIDIjsObj );
					else if( typeof MIDIjs != 'undefined' )
					{
						clearInterval( checkMIDIjsObj );
						MIDIjs.player_callback = function( evt ){
							if( evt.time == 0 )
							{
								// Play next
								var e = $( '.midiStop' ),
									playerNumber = e.attr('playerNumber')*1;

								e.trigger('click');
								_playNext( playerNumber );
							}
						};

						$( document ).on(
							'click',
							'.midiPlay,.midiStop',
							function()
							{
								try
								{
									var e = $( this );
									if( e.hasClass( 'midiPlay' ) )
									{
										MIDIjs.play( e.attr( 'data-href' ) );
										$( '.midiStop' ).trigger('click');
										_increasePlays( e );
										e.attr( 'class', 'midiPlayer midiStop' );
									}
									else
									{
										e.attr( 'class', 'midiPlayer midiPlay' );
										MIDIjs.stop();
									}
								}catch( err ){}

								return false;
							}
						);
					}
				},
				1000
			);
	}

	// Increase popularity
	$( 'audio' ).on( 'play', function(){
		_increasePlays( $( this ) );
	} );

	// Replace the popularity texts with the stars
	$( '.collection-popularity,.song-popularity' ).each(
		function()
		{
			var e = $( this ),
				p = e.data('popularity'),
				v = e.data('votes'),
				str = '';

			for( var i = 1; i <= 5; i++ )
				str += '<div class="'+((i<=p) ? 'star-active' : 'star-inactive')+' star" data-title="'+i+'"></div>';
			str += '<span class="votes">'+v+'</span>';
			e.html( str );
		}
	);

	$(document).on('click', '[data-productid] .star', function(){_increasePopularity(this);});

	// Free downloads
	$( '.ms-download-link' ).on('click', function( evt ){
		var e = $( evt.target );
		if( typeof e.attr( 'data-id' ) != 'undefined' )
		{
			evt.preventDefault();
			$.ajax(
				ms_global[ 'hurl' ],
				{
					data: { 'id': e.attr( 'data-id' ), 'ms-action': 'registerfreedownload', '_msr': Date.now() }
				}
			).done(
				( function( _url ){
					return function(){ document.location.href = _url; };
				} )( $( evt.target ).attr( 'href' ) )
			);
		}
	});


	// Check the download links
	timeout_counter = 30;
    if( $( '[id="music_store_error_mssg"]' ).length )
    {
        music_store_counting();
    }

	// Browser's resize of Mobile orientation
	$( window ).on( 'orientationchange resize', function(){
		setTimeout(
			(function( minWidth, getWidth )
			{
				return function()
				{
					$( '.music-store-item' ).each( function(){
						var e = $( this ),
							c = e.find( '.collection-cover,.song-cover' );

						if( getWidth < minWidth )
						{
							if( c.length ) c.css( { 'height': 'auto' } );
							e.css( {'width': '100%', 'height': 'auto'} );
						}
						else
						{
							if( c.length ) c.css( { 'height': '' } );
							e.css( {'width': e.attr( 'data-width' ), 'height': '' } );
						}
					} );
					if( getWidth >= minWidth && typeof ms_correct_heights != 'undefined' )
						ms_correct_heights();
				}
			})( min_screen_width, _getWidth() ),
			100
		);
	} );
};

function music_store_force_init()
{
	delete jQuery.codepeople_music_store_flag;
	codepeople_music_store();
}

jQuery(codepeople_music_store);
jQuery(window).on('load',function(){
	var $ 		= jQuery,
		ua 		= window.navigator.userAgent,
		preload = ( 'ms_global' in window && 'preload' in ms_global && ms_global[ 'preload' ]*1 ) ? 1 : 0;

	codepeople_music_store();

	$(window).trigger('resize');

	if(ua.match(/iPad/i) || ua.match(/iPhone/i))
	{
		var p = (ms_global && ms_global[ 'play_all' ]*1);
		if(p) // Solution to the play all in Safari iOS
		{
			$('.ms-player .mejs-play button').one('click', function(){

				if('undefined' != typeof ms_preprocessed_players) return;
				ms_preprocessed_players = true;

				var e = $(this);
				$('.ms-player audio').each(function(){
					this.play();
					this.pause();
				});
				setTimeout(function(){e.trigger('click');}, 500);
			});
		}
	}
	else if( preload )
	{
		$('audio').attr('preload', 'auto');
	}
}).on('popstate', function(){
	if(jQuery('audio[data-product]:not([playerNumber])').length) music_store_force_init();
});