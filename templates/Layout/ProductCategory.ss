<div class="product-category sws row">
	<div class="small-12 columns">
		<h1>$Title</h1>
		$Content
		
		<% if Children %>
			<div id="children-categories-wrap">
				<h3>Categories</h3>
				
				<div id="children-categories">
					<% loop Children %>
						<a href="$Link" class="child-category" title="$MenuTitle">
							<h4>$MenuTitle</h4>
						</a>
					<% end_loop %>
				</div>
			</div>
		<% end_if %>
		
		<% if getProductsList %>
			<% if Children %>
				<hr />
			<% end_if %>
			
			<div id="products-list-wrap">
				<h3>Products</h3>
				
				<div id="products-list">
					<% loop getProductsList %>
						<div class="product-list-item">
							<a href="$Link" title="$MenuTitle">
								<h4>$MenuTitle</h4>
								<% if ShortDescription %>
									<p>$ShortDescription</p>
								<% end_if %>
							</a>
						</div>
					<% end_loop %>
				</div>
				
				<% if $getProductsList.MoreThanOnePage %>
					<ul class="pagination">
						<% if $getProductsList.NotFirstPage %>
							<li class="arrow"><a href="$getProductsList.PrevLink">&laquo;</a></li>
						<% else %>
							<li class="arrow unavailable"><a>&laquo;</a></li>
						<% end_if %>
						
						<% loop $getProductsList.PaginationSummary(4) %>
							<% if $CurrentBool %>
								<li class="current"><a>$PageNum</a></li>
							<% else %>
								<% if $Link %>
									<li><a href="$Link">$PageNum</a></li>
								<% else %>
									<li class="unavailable"><a href="">&hellip;</a></li>
								<% end_if %>
							<% end_if %>
						<% end_loop %>
						
						<% if $getProductsList.NotLastPage %>
							<li class="arrow"><a href="$getProductsList.NextLink">&raquo;</a></li>
						<% else %>
							<li class="arrow unavailable"><a>&raquo;</a></li>
						<% end_if %>
					</ul>
				<% end_if %>
			</div>
		<% end_if %>
	</div>
</div>