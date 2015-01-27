<div class="product sws clearfix row">
	<div class="small-12 columns"> 
		<% if Images %>
			<div id="product-gallery">
				<div id="product-main-image">
					<% with Images.First %>
						<img src="$Image.SetWidth(400).URL" alt="$Image.Title" />
					<% end_with %>
				</div>
				
				<div id="product-images-small">
					<% loop Images %>
						<a href="$Image.SetRatioSize(800,600).URL" title="Click to enlarge">
							<img src="$Image.CroppedImage(100,100).URL" alt="$Image.Title" />
						</a>
					<% end_loop %>
				</div>
			</div>
		<% end_if %>
		
		<div class="product-meta">
			<h1>$Title</h1>
			<h3 class="product-price-js">
				<% if Product.OnSpecial() %>
					<span class="old-price">Was $OriginalAmount.Nice()</span><br />
					<span class="special-price">Now $Price.Nice()</span>
				<% else %>
					$Price.Nice()
				<% end_if %>
			</h3>
			<div id="product-message" class="message"></div>
			<div class="add-to-cart">
				$ProductForm(1, /cart)
			</div>
		</div>
	
		<div class="product-description">
			$Content
		</div>
		
		<div id="adding-to-cart" class="reveal-modal" data-reveal>
			<div class="spinning-wheel-large"></div>
			<h3>Adding products to your cart.</h3>
		</div>
	</div>
</div>