
jQuery.noConflict();
jQuery(document).ready(function($) {
	
	/* CART OVERVIEW */
	// Update cart function
	function AjaxUpdateCartOverview(show){
		show = show === undefined ? true : show;
		
		if(show){
			$('#cart-overview').addClass('show');
		}
		// Load the updated cart content
		$('#cart-overview-loader, #cart-overview-content').addClass('loading');
		$('#cart-overview-content').load(window.location.pathname + '/RefreshCartOverview', function(data, status, jqXHR){
			// Cart Drop Down
			$('#cart-overview-content, #cart-overview-loader').removeClass('loading');
			if(show){
				window.setTimeout(function(){
					$('#cart-overview').removeClass('show');
				}, 3000);
			}
			
			// Update count number
			if($('#cart-total-overview').length > 0){
				//var cartTotalUrl = window.location.hostname + window.location.pathname + '/TotalCartItems';
				var cartTotalUrl = window.location.pathname + '/TotalCartItems';
				$.get(
					cartTotalUrl,
					function(data){
						$('#cart-total-overview').html('Cart (' + data.Total + ')');
					},
					'json'
				);
			}
		});
	}
	
	$('#cart-overview').entwine({
		onmatch : function(){			
			// Remove items from the cart
			$('body').on('click', 'a.cart-overview-remove', function(e){
				e.preventDefault();
				var removeItem = $(this).attr('data-item');
				
				$.ajax({
					type : "POST",
					url : $(this).attr('href') + '/' + removeItem,
					cache : false,
					dataType : 'json',
					success : function(data){
						// Refresh the cart overview
						AjaxUpdateCartOverview();
					}
				});
			});
		}
	});
	
	/* CART PAGE */
	$('#CartForm_CartForm').entwine({
		onmatch : function(){
			// Refresh the cart table
			function AjaxRefreshCartPage(){
				$('#CartForm-Holder, #cart-summary-loader').addClass('loading');
				$('#CartForm-Holder').load(window.location.href + ' #CartForm_CartForm', function(){
					$('#CartForm-Holder, #cart-summary-loader').removeClass('loading');
				});
			}
			
			// Remove from cart function
			$('.cart-summary-remove').attr('href', window.location.href + '/RemoveItem');
			$('body').on('click', 'a.cart-summary-remove', function(e){
				e.preventDefault();
				var removeItem = $(this).attr('data-item');
				
				$.ajax({
					type : "POST",
					url : $(this).attr('href') + '/' + removeItem,
					cache : false,
					dataType : 'json',
					success : function(data){
						// Refresh the main cart
						AjaxRefreshCartPage();
						
						// Refresh the cart overview
						AjaxUpdateCartOverview(false);
					}
				});
			});
		}
	});
	
	/* PRODUCT PAGE */		
	$('.product-form').entwine({
		onmatch : function() {
			var self = this;
			this.find('.attribute_option select').on('change', function(e) {
				self._updatePrice(e);
			});
			self._updatePrice();

			this._super();
		},
		onunmatch: function() {
			this._super();
		},		
		_updatePrice: function(e) {
			var self = this,
				form = this.closest('form');

			//Get selected options
			var options = [];
			$('.attribute_option select', form).each(function(){
				options.push($(this).val());
			});

			//Find the matching variation
			var variations = form.data('map');
			for (var i = 0; i < variations.length; i++){
				var variationOptions = variations[i]['options'];
				//If options arrays match update price
				if ($(variationOptions).not(options).length == 0 && $(options).not(variationOptions).length == 0) {
					$(this).parents('.product.sws').find('.product-price-js').html(variations[i]['price']);
				}
			}
		}
	}).submit(function(e){
		e.preventDefault();
		
		if(jQuery().foundation){
			$('#adding-to-cart').foundation('reveal', 'open');
		}
		
		// Add the product to the cart
		$.ajax({
			type : "POST",
			url : $(this).attr('action'),
			data : $(this).serialize(),
			cache : false,
			dataType : 'json',
			success : function(data){				// Check if the product was added and display message to user
				if(jQuery().foundation){
					$('#adding-to-cart').foundation('reveal', 'close');
				}
				var result = data.result ? 'good' : 'bad';
				$('#product-message').html("<p>" + data.message + "</p>").css({'opacity' : 0}).removeClass('good bad').addClass(result).css({'opacity' : 1});
				
				// Refresh the cart overview
				AjaxUpdateCartOverview();
				
			}
		}).fail(function(){
			if(jQuery().foundation){
				$('#adding-to-cart').foundation('reveal', 'close');
			}
			$('#product-message').html("<p>Sorry there was an error adding this item to your cart. Please try again.</p>").css({'opacity' : 0}).removeClass('good bad').addClass('bad').css({'opacity' : 1});
		});
	});
	
	/* CHECKOUT PAGE */
	$('.CheckoutPage').entwine({
		onmatch : function(){
			// Post small register form
			$('#Form_SmallRegisterAccountForm').submit(function(e){
				e.preventDefault();
				
				var redirect = '/checkout';
				
				// Add the product to the cart
				$.ajax({
					type : "GET",
					url : $(this).attr('action'),
					data : $(this).serialize() + '&Redirect=' + redirect,
					cache : false,
					success : function(data){
						$('#ordeform-wrapper').stop().animate({'opacity' : 0}, 500, function(){
							$(this).html(data).stop().animate({'opacity' : 1}, 500);
							$('body').on('submit', '#Form_RegisterAccountForm', function(e){
								e.preventDefault();
								$('#ordeform-wrapper').stop().animate({'opacity' : 0}, 500);
								$.ajax({
									type : "POST",
									url : $(this).attr('action'),
									data : $(this).serialize(),
									cache : false,
									success : function(data){
										if(data == redirect){
											window.location = window.location.origin + redirect;
										} else {
											$('#ordeform-wrapper').html(data).stop().animate({'opacity' : 1}, 500);
										}
									}
								});
							});
						});
					}
				});
			});
		}
	});
});