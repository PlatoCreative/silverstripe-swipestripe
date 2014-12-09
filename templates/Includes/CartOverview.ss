<% if not ajax %>
	<div id="cart-overview" class="clearfix">
		<% if Cart && Cart.Items %>
			<a href="$CartLink()" id="cart-overview-icon" alt="View full cart summary"></a>
		<% else %>
			<div id="cart-overview-icon"></div>
		<% end_if %>
		
		<div id="cart-overview-popout">		
			<span class="subheader">Currently in your cart:</span>
<% end_if %>
			<div id="cart-overview-loader"></div>
			<div id="cart-overview-content">
			
				<% if Cart && Cart.Items %>
					<div id="cart-overview-info">			
						<% with Cart %>
							<% loop Items.limit(3) %>
								<div class="cart-overview-item clearfix">
									<% if Product.Images %>
										<img src="$Product.Images.First().Image.CroppedImage(100,100).URL" alt="$Title" />
									<% end_if %>
									
									<div class="cart-overview-item-text">
										<a href="$Product.Link()" alt="$Produc.Title" class="cart-overview-item-title">
											$Product.Title
										</a>
										<p class="cart-overview-qty">Qty: $Quantity</p>
										<p class="cart-overview-price">$Total.Nice()</p>
										<% if Variation() %>
											<ul>
												<% loop Variation.Options %>
													<li>$Attribute.Title: $Title</li>
												<% end_loop %>
											</ul>
										<% end_if %>
									</div>
									
									<div class="cart-overview-item-functions">
										<a href="$Top.CartLink()RemoveItem" title="Remove from cart" class="cart-overview-remove small-cross" data-item="$ID"></a>
									</div>
								</div>
							<% end_loop %>
							
							<div class="cart-overview-btns">
								<a href="$Top.CartLink()" class="left btn" alt="Full cart summary">Full Cart Summary</a>
								<a href="$Top.CartLink(Checkout)" class="right btn" alt="Checkout now">Checkout Now</a>
							</div>
						<% end_with %>
					</div>
					
				<% else %>
					<div id="cart-overview-noitems">
						<p>Your shopping cart is currently empty.</p>
					</div>			
				<% end_if %>
				
			</div>
			
<% if not ajax %>
		</div>
	</div>
<% end_if %>