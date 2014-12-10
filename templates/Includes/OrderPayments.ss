<table class="table table-bordered">
	<thead>     
		<tr>
			<th><% _t('OrderPayments.PAYMENT','Payment') %></th>
			<th><% _t('OrderPayments.DATE','Date') %></th>
			<th><% _t('OrderPayments.AMOUNT','Amount') %></th>
			<th><% _t('OrderPayments.PAYMENT_STATUS','Payment Status') %></th>
		</tr>
	</thead>
	<tbody>
		<% loop Payments %>  
			$OrderPaymentRow()
		<% end_loop %>
	</tbody>
</table>

<table class="table table-bordered">
	<tbody>
		<tr>
			<th><% _t('OrderPayments.TOTAL_OUTSTANDING','Total outstanding') %></th>
			<th>$TotalOutstanding.Nice</th>
		</tr>
	</tbody>
</table>
