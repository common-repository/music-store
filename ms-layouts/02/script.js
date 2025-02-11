jQuery( function( $ )
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
							e.css( 'width', $( '.music-store-header' ).outerWidth( ) );
						}
					}
				);
			};

		correct_header();

		// Correct the images heights
		window[	'ms_correct_heights' ] = function()
		{
			var min_height = Number.MAX_VALUE;
			$( '.music-store-items .song-cover img, .music-store-items .colllection-cover img' ).each(
				function()
				{
					var e = $( this );
					min_height = Math.min( e.height(), min_height );
				}
			);

			if( min_height != Number.MAX_VALUE )
			{
				$( '.music-store-items .song-cover, .music-store-items .collection-cover' ).css( { 'height': min_height+'px', 'overflow': 'hidden' } );
			}

			$( '.song-cover, .collection-cover' ).append( $( '<div class="ms-inner-shadow"></div>' ) );

			// Correct the item heights
			var	height_arr = [],
				max_height = 0;
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

		$( window ).on( 'load', function(){ correct_header(); ms_correct_heights(); } );
		$( window ).bind( 'orientationchange resize', correct_header );

		// Modify the price box
		$( '.song-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span.label,span.invalid' ).remove();
			}
		);

		$( '.collection-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span.label,span.invalid' ).remove();
			}
		);

		// Modify the shopping cart design
		$( '.ms-shopping-cart-list,.ms-shopping-cart-resume' ).wrap( '<div class="ms-shopping-cart-wrapper" style="position:relative;"></div>' );
		$( '.ms-shopping-cart-wrapper' ).prepend( '<div style="clear:both;"></div>' ).append( '<div style="clear:both;"></div>' );
	}
);