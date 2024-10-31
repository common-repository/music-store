jQuery(
	function( $ )
	{
		// Menu header
		if(!$('.music-store-filters').is(':empty')) $('.music-store-header').prepend( '<span class="header-handle"></span>' );
		$(document).on(
			'click',
			'.music-store-header .header-handle',
			function()
			{
				$('.music-store-filters').toggle(300);
			}
		);

		$(document)
		.on(
			'mouseover',
			'.collection-payment-buttons input[type="image"],.song-payment-buttons input[type="image"],.track-button input[type="image"]',
			function()
			{
				var me = $(this);
				if( !me.hasClass('rotate-in-hor'))
				{
					$(this).addClass('rotate-in-hor');
					setTimeout(
						function()
						{
							me.removeClass('rotate-in-hor');
						},
						1000
					);
				}

			}
		);

		// Set buttons classes
		$('.ms-shopping-cart-list .button,.ms-shopping-cart-list .button,.ms-shopping-cart-resume .button').addClass('bttn-stretch bttn-sm bttn-primary').removeClass('button').wrap('<span class="bttn-stretch bttn-sm bttn-primary" style="margin-top: -6px !important;"></span>');

		$('.ms-shopping-cart').next('.music-store-song,.music-store-collection').find('.left-column.single').css('padding-top','36px');
	}
);