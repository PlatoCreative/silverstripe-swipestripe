<?php
/**
 * Extends {@link Page_Controller} adding some functions to retrieve the current cart, 
 * and link to the cart.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Cart extends DataExtension {
	private static $allowed_actions = array(
		'RefreshCartOverview',
		'TotalCartItems'
	);
	
	/**
	 * Updates timestamp LastActive on the order, called on every page request. 
	 */
	public function onBeforeInit() {
		$orderID = Session::get('Cart.OrderID');
		if ($orderID && $order = DataObject::get_by_id('Order', $orderID)) {
			$order->LastActive = SS_Datetime::now()->getValue();
			$order->write();
		}
	}
	
	// TODO - Shift these to be better included
	public function onAfterInit() {		
		// CSS & JS
		Requirements::css('swipestripe/css/Shop.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe/javascript/Shop.js');	
	}
	
	// Refresh content in the Cart Overview include
	public function RefreshCartOverview(){
		$this->owner->customise(array(
			'Cart' => $this->Cart(),
			'ajax' => Director::is_ajax()
		));
		return $this->owner->renderWith('CartOverview');
	}
	
	/**
	 * Retrieve the current cart for display in the template.
	 * 
	 * @return Order The current order (cart)
	 */
	public function Cart() {
		$order = self::get_current_order();
		$order->Items();
		$order->Total;

		//HTTP::set_cache_age(0);
		return $order;
	}
	
	// Return the total cart count
	public function TotalCartItems(){
		$order = self::get_current_order();
		$items = $order->Items();
		$total = 0;
		
		foreach($items as $item){
			$total += $item->Quantity;	
		}
		
		if(Director::is_ajax()){
			return Convert::array2json(array('Total' => $total));
		} else {
			return $total;
		}
	}
	
	/**
	 * Convenience method to return links to cart related page.
	 * 
	 * @param String $type The type of cart page a link is needed for
	 * @return String The URL to the particular page
	 */
	public function CartLink($type = 'Cart') {
		switch ($type) {
			case 'Account':
				if ($page = DataObject::get_one('AccountPage')) return $page->Link();
				else break;
			case 'Checkout':
				if ($page = DataObject::get_one('CheckoutPage')) return $page->Link();
				else break;
			case 'Login':
				return Director::absoluteBaseURL() . 'Security/login';
				break;
			case 'Logout':
				return Director::absoluteBaseURL() . 'Security/logout?BackURL=%2F';
				break;
			case 'Cart':
			default:
				if ($page = DataObject::get_one('CartPage')) return $page->Link();
				else break;
		}
	}
	
	/**
	 * Get the current order from the session, if order does not exist create a new one.
	 * 
	 * @return Order The current order (cart)
	 */
	public static function get_current_order($persist = false) {
		$orderID = Session::get('Cart.OrderID');
		$order = null;
		
		if ($orderID) {
			$order = DataObject::get_by_id('Order', $orderID);
		}
		
		if (!$orderID || !$order || !$order->exists()) {
			$order = Order::create();

			if ($persist) {
				$order->write();
				Session::set('Cart', array(
					'OrderID' => $order->ID
				));
				Session::save();
			}
		}
		return $order;
	}
	
	public static function getCustomer(){
		return Customer::currentUser();
	}
	
	public function AccountPage(){
		return AccountPage::get()->first();	
	}
}