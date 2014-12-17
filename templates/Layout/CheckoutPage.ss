<div class="checkout sws content container typography row">
	<div class="small-12 columns">
		<h1>$Title</h1>
		$Content
	
		<% if CurrentMember && not CurrentMember.inGroup(Administrators) %>
			$OrderForm
		<% else %>
			<style>
				.fieldgroup-field .field{
					width: 45%;
					float:left;
					margin: 0 15px 0 0;
				}
			</style>
	
			<% if Message %>
				<p id="{$FormName}_error" class="message $MessageType">$Message</p>
			<% else %>
				<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
			<% end_if %>
			
			<div id="ordeform-wrapper">
				<div id="orderform-registration">
					<div class="orderform-box">
						<h2>Register</h2>
						$AccountPage.SmallRegisterAccountForm('checkout')
						<div class="clear clearing"></div>
					</div>
				</div>
				
				<div id="orderform-login">
					<div class="orderform-box">
						<h2>Login</h2>
						$Loginform
						<div class="clear clearing"></div>
					</div>
				</div>
				
				<div class="clear clearing"></div>
			</div>
		<% end_if %>
	</div>
</div>