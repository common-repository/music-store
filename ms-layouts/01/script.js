jQuery(
	function( $ )
	{
		// Correct the header and items width
		var correct_header = function()
			{
				$( '.music-store-items,.music-store-pagination' ).each(
					function()
					{
						var e = $( this );
						if( e.parents( '.widget' ).length == 0 && e.siblings( '.music-store-filtering-result' ).length != 0 )
						{
							e.css( 'min-width', $( '.music-store-header' ).outerWidth() );
						}
					}
				);
			};

		correct_header();
		$( window ).on( 'load', correct_header );
		$( window ).bind( 'orientationchange resize', correct_header );

		// Correct the item heights
		var height_arr = [],
			max_height = 0;

		window[	'ms_correct_heights' ] = function()
			{
				$( '.music-store-items' ).children( 'div' ).each(
					function()
					{
						var e = $( this );
						if( e.hasClass( 'music-store-item' ) )
						{
							max_height = Math.max( e.height(), max_height );
						}
						else
						{
							height_arr.push( max_height );
							max_height = 0;
						}
					}
				);

				if( height_arr.length )
				{
					$( '.music-store-items' ).children( 'div' ).each(
						function()
						{
							var e = $( this );
							if( e.hasClass( 'music-store-item' ) )
							{
								e.height( height_arr[ 0 ] );
							}
							else
							{
								height_arr.splice( 0, 1 );
							}
						}
					);
				}
			};

		$( window ).on( 'load', function(){ ms_correct_heights(); } );

		// Modify the price box
		$( '.song-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span.invalid,span.label' ).remove();
			}
		);

		$( '.collection-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span.label,span.invalid' ).remove();
			}
		);

		// Indicate the active tab

		$( '.music-store-tabs' ).children( 'li' ).click(
			function()
			{
				var e = $( this ),
					p = e.position(),
					w = e.width()/2,
					container = e.closest( '.music-store-tabs' ).siblings( '.music-store-tabs-container' );

				if( container.find( '.music-store-corner' ).length == 0 )
				container.prepend( $( '<div class="music-store-corner"></div>' ) );
				container.find( '.music-store-corner' ).css( 'margin-left', ( p.left + w ) + 'px' );
			}
		);
		$( 'li.active-tab' ).click();
	}
);