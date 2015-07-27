<?php
/**
 * Represents a {@link Customer}, a type of {@link Member}.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Customer extends Member {

	private static $db = array(
		'Code' => 'Int', //Just to trigger creating a Customer table
		'Activated' => 'Boolean',
		'SentActivation' => 'Boolean',
		'SentUserActivation' => 'Boolean'
	);

	/**
	 * Link customers to {@link Address}es and {@link Order}s.
	 *
	 * @var Array
	 */
	private static $has_many = array(
		'Orders' => 'Order'
	);

	private static $searchable_fields = array(
		'Surname',
		'Email'
	);

	/**
	 * Prevent customers from being deleted.
	 *
	 * @see Member::canDelete()
	 */
	public function canDelete($member = null) {

		$orders = $this->Orders();
		if ($orders && $orders->exists()) {
			return false;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		//Create a new group for customers
		$allGroups = DataObject::get('Group');
		$existingCustomerGroup = $allGroups->find('Title', 'Customers');
		if (!$existingCustomerGroup) {

			$customerGroup = new Group();
			$customerGroup->Title = 'Customers';
			$customerGroup->setCode($customerGroup->Title);
			$customerGroup->write();

			Permission::grant($customerGroup->ID, 'VIEW_ORDER');
		}
	}

	/**
	 * Add some fields for managing Members in the CMS.
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {

		$fields = new FieldList();

		$fields->push(new TabSet('Root',
			Tab::create('Customer')
		));

		$password = new ConfirmedPasswordField(
			'Password',
			null,
			null,
			null,
			true // showOnClick
		);
		$password->setCanBeEmpty(true);
		if(!$this->ID) $password->showOnClick = false;

		$fields->addFieldsToTab('Root.Customer', array(
			new TextField('FirstName'),
			new TextField('Surname'),
			new EmailField('Email'),
			new ConfirmedPasswordField('Password'),
			$password
		));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * Overload getter to return only non-cart orders
	 *
	 * @return ArrayList Set of previous orders for this member
	 */
	public function Orders() {
		return Order::get()
			->where("\"MemberID\" = " . $this->ID . " AND \"Order\".\"Status\" != 'Cart'")
			->sort("\"Created\" DESC");
	}

	/**
	 * Returns the current logged in customer
	 *
	 * @return bool|Member Returns the member object of the current logged in
	 *                     user or FALSE.
	 */
	static function currentUser() {
		$id = Member::currentUserID();
		if($id) {
			return DataObject::get_one("Customer", "\"Member\".\"ID\" = $id");
		}
	}

	public function onAfterWrite(){
		parent::onAfterWrite();
		$siteconfig = SiteConfig::current_site_config();

		// Check the user permsissions and send confirmation email
		$shopConfig = ShopConfig::current_shop_config();
		if($shopConfig->config()->RequireUserActivation){
			// Notify admin that a new customer has registered
			if(!$this->Activated && !$this->SentActivation){
				$siteconfig = SiteConfig::current_site_config();
				$shopConfig = ShopConfig::current_shop_config();

				$to = $shopConfig->NotificationTo;
				$from = $shopConfig->NotificationTo;
				$subject = $siteconfig->Title . ' - Customer Activation';

				$body = "<p>Hi,</p>";
				$body .= "<p>There has been a new customer registration on the " . $siteconfig->Title . " website.</p>";
				$body .= "<p><strong>Customer Details:</strong><br />";
				$body .= "Name: " . $this->FirstName . " " . $this->LastName . "<br />";
				$body .= "Email: " . $this->Email . "<br />";
				$body .= "Account Number: " . $this->AccountNumber . "</p>";
				$body .= "<p>Activate the user by clicking the following link.<br />";
				$body .= "<a href='" . Director::absoluteBaseURL() . "admin/shop/Customer/EditForm/field/Customer/item/" . $this->ID . "/edit' target='_blank'>Activate user</a></p>";

				$email = new Email($from, $to, $subject, $body);

				if($email->send()){
					$this->owner->SentActivation = 1;
					$this->owner->write();
				}
			}

			// Notify user that their account has been Activated
			if($this->Activated ){//&& !$this->SentUserActivation){
				$siteconfig = SiteConfig::current_site_config();
				$shopConfig = ShopConfig::current_shop_config();

				$to = $this->Email;
				$from = $shopConfig->ReceiptFrom;
				$subject = $siteconfig->Title . ' - Account Activation';

				$body = "<p>Hi " . $this->FirstName . ",</p>";
				$body .= "<p>Your account has been successfully activated.</p>";
				$body .= "<p>You can now access your account at the following URL.<br />";
				$body .= "<a href='" . Director::absoluteBaseURL() . "account/' target='_blank'>" . Director::absoluteBaseURL() . "account/</a></p>";
				$body .= "<p>Thanks,<br />The " . $siteconfig->Title . " team.</p>";

				$email = new Email($from, $to, $subject, $body);

				if($email->send()){
					$this->owner->SentUserActivation = 1;
					$this->owner->write();
				}
			}
		}
	}

	public function canLogIn() {
		$shopConfig = ShopConfig::current_shop_config();
		$result = Parent::canLogIn();

		if($shopConfig->config()->RequireUserActivation){
			if(!$this->Activated){
				$result->error(_t (
					'Member.NEEDSAPPROVALTOLOGIN',
					'An administrator must confirm your account before you can log in.'
				));
			}
		}

		$this->extend('canLogIn', $result);

		return $result;
	}
}
