<div id="cart-summary-loader"></div>
<div id="CartForm-Holder">
	<% if IncludeFormTag %>
		<form $FormAttributes>
	<% end_if %>

			<% if Message %>
				<p id="{$FormName}_error" class="message $MessageType">$Message</p>
			<% else %>
				<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
			<% end_if %>

			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><%t CartForm.PRODUCT 'Product' %></th>
						<th><%t CartForm.PRICE 'Price' %> ($Cart.CartTotalPrice.Currency)</th>
						<th><%t CartForm.QUANTITY 'Quantity' %></th>
						<th><%t CartForm.TOTAL 'Total' %> ($Cart.CartTotalPrice.Currency)</th>
					</tr>
				</thead>
				<tbody>

					<% if Cart.Items %>

						<% loop Fields %>
							$FieldHolder
						<% end_loop %>

						<% with Cart %>
							<tr>
								<td colspan="4">&nbsp;</td>
								<td><strong>$CartTotalPrice.Nice</strong></td>
							</tr>
						<% end_with %>

					<% else %>
						<tr>

							<td colspan="6">
								<p class="alert alert-info">
									<strong class="alert-heading"><%t CartForm.NOTE 'Note:' %></strong>
									<%t CartForm.NO_ITEMS_IN_CART 'There are no items in your cart.' %>
								</p>
							</td>

						</tr>
					<% end_if %>

				</tbody>
			</table>

			<div class="Actions">
				<% if CategoryLink() %>
					<a href="$CategoryLink.Link" class="btn">Continue Shopping</a>
				<% end_if %>
				<% if Cart.Items %>
					<% if Actions %>
						<% loop Actions %>
							$Field
						<% end_loop %>
					<% end_if %>
				<% end_if %>
			</div>

	<% if IncludeFormTag %>
		</form>
	<% end_if %>
</div>
